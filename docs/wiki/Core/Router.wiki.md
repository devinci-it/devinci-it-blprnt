# Router

`DevinciIT\Blprnt\Core\Router` matches an incoming URI + HTTP method to a
handler, runs middleware around it, and executes it. One router instance per
application, bound into the container as `router` and loaded with the routes
files during bootstrap (see
[Bootstrap & Request Lifecycle](Bootstrap-Lifecycle.wiki.md)).

> This page replaces an earlier version that documented a `dispatch()`
> implementation the code no longer has (plain `\Exception`, no-arg
> middleware). Everything below is checked against `src/Core/Router.php` as it
> exists today.

## Registering routes

```php
$router->get('/posts', [PostController::class, 'index']);
$router->post('/posts', [PostController::class, 'store']);
$router->put('/posts/{id}', [PostController::class, 'update']);
$router->patch('/posts/{id}', [PostController::class, 'patch']);
$router->delete('/posts/{id}', [PostController::class, 'destroy']);
```

Each of `get`/`post`/`put`/`patch`/`delete` is a thin wrapper over
`addRoute($method, $uri, $action, $middleware)` and returns the `Route` object,
so you can chain `->middleware([...])` on an individual route:

```php
$router->get('/admin', [AdminController::class, 'index'])
    ->middleware([AuthGuard::class]);
```

An action is either:

- a **closure**: `function ($request) { ... }`
- a **`[Controller::class, 'method']`** pair — the controller is instantiated
  fresh (`new $controller()`) for every request, no constructor arguments are
  passed

## Route parameters

`{name}` segments become named capture groups (`[^/]+` — matches anything but
a `/`) and are passed to the handler **positionally, after `$request`**, in
the order they appear in the URI:

```php
$router->get('/posts/{id}', [PostController::class, 'show']);

// PostController
public function show($request, $id) { ... }
```

```php
$router->get('/posts/{id}/comments/{commentId}', [PostController::class, 'comment']);

public function comment($request, $id, $commentId) { ... }
```

There's no type coercion or constraint syntax (no `{id:int}`) — every param
arrives as a string.

## Groups

```php
$router->group(['prefix' => '/admin', 'middleware' => [AuthGuard::class]], function ($router) {
    $router->get('/', [AdminController::class, 'index']);       // -> /admin
    $router->get('/settings', [AdminController::class, 'settings']); // -> /admin/settings
});
```

`group()` saves the current prefix/middleware, applies the new ones for the
duration of the callback, then restores what was there before — so groups
nest cleanly. Middleware from an outer group, an inner group, and the route
itself are all merged (in that order) before dispatch.

## Global middleware

```php
$router->middleware([SomeMiddleware::class]);
```

Runs before every route's own middleware, on every route, regardless of
group. There's no way to exempt a specific route from it.

## Dispatch

```php
$router->dispatch($uri, $method, $request);
```

This is what `Http\Kernel` calls per-request. In order:

1. **Match.** Exact `[method][uri]` lookup first; if that misses, every
   registered pattern for that method is tried as a regex
   (`{name}` → `(?P<name>[^/]+)`) until one matches.
2. **Not found.** If nothing matches, throws
   `Core\Exceptions\RouteNotFoundException` — carries the requested
   method/URI *and* the list of methods that do have routes registered, so the
   resulting error page/message tells you what's actually reachable instead of
   just "404."
3. **Build the controller executor** — a closure that calls the closure or
   controller action with `$request` plus the matched params.
4. **Build the middleware stack** — global middleware + group middleware +
   route middleware, merged in that order — and wrap the controller executor
   in a [`MiddlewarePipeline`](Middleware.wiki.md).
5. **Run the pipeline** with `$request`, return whatever it returns.

## Gotchas

- **No 405.** A `POST` to a URI that only has a `GET` route registered
  produces the same `RouteNotFoundException` as a URI that doesn't exist at
  all. If you need to tell "wrong method" apart from "no such route," you
  currently have to inspect `getRegisteredMethods()` on the caught exception
  yourself.
- **`load(null)` uses `findProjectRoot()`**, which walks up from `__DIR__`
  looking for a `vendor` directory. Every real bootstrap path
  (`HttpBootstrapBuilder`, `CLIBootstrapBuilder`) calls `load()` with an
  explicit path, so this fallback is not exercised in normal use — don't rely
  on it, and don't assume it resolves correctly inside `test/sandbox` or any
  other nested-`vendor` layout.
- **Route matching is exact-string-then-regex, not radix/trie-based** — fine
  at the route counts a micro-framework app is likely to have, but every
  unmatched exact lookup falls through to a full linear scan of that method's
  patterns.

## `Route`

The value object each registration produces (`src/Core/Route.php`):

```php
class Route {
    public string $method;
    public string $uri;
    public $action;              // closure or [Controller::class, 'method']
    public array $middleware = [];
}
```

`$route->middleware([...])` appends more middleware and returns `$this`, used
internally for the fluent `->middleware()` chaining shown above.
