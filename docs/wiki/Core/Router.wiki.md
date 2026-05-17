# 🧭 Router Class Documentation

## 📦 Overview

The `Router` class is responsible for managing application routes and dispatching HTTP requests to the appropriate handlers.

It supports:

* HTTP method-based routing (GET, POST, PUT, PATCH, DELETE)
* Closure and controller-based actions
* Middleware execution
* Route grouping with shared middleware

---

## 🏗️ Class: `Router`

### Purpose

Provides a simple routing system that maps HTTP requests to handlers while supporting middleware and grouping.

---

## 🔐 Properties

### `$routes`

```php
protected $routes = [];
```

* Stores all registered routes
* Organized by:

  ```
  [HTTP_METHOD][URI] => [action, middleware]
  ```

---

### `$groupMiddleware`

```php
protected $groupMiddleware = [];
```

* Stores middleware applied to a route group
* Automatically merged into routes defined within a group

---

## ⚙️ Methods

---

### 🔹 `group(array $opts, callable $callback): void`

#### Description

Creates a route group with shared middleware.

#### Parameters

* `array $opts`
  Group configuration options

  * `middleware` (array) → middleware applied to all routes in the group

* `callable $callback`
  Function that receives the router instance and defines routes

#### Example

```php
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/dashboard', [DashboardController::class, 'index']);
});
```

---

### 🔹 `addRoute(string $method, string $uri, callable|array $action, array $middleware = []): void`

#### Description

Registers a route internally.

#### Parameters

* `string $method`
  HTTP method (GET, POST, PUT, PATCH, DELETE)

* `string $uri`
  Route URI path

* `callable|array $action`
  Route handler:

  * Closure (`function () {}`)
  * Controller action (`[ControllerClass::class, 'method']`)

* `array $middleware` *(optional)*
  Middleware specific to this route

#### Behavior

* Merges group middleware with route middleware
* Stores route definition in `$routes`

---

## 🌐 HTTP Method Helpers

These methods are convenience wrappers around `addRoute()`.

---

### 🔹 `get(string $uri, callable|array $action, array $middleware = []): void`

Registers a **GET** route.

---

### 🔹 `post(string $uri, callable|array $action, array $middleware = []): void`

Registers a **POST** route.

---

### 🔹 `put(string $uri, callable|array $action, array $middleware = []): void`

Registers a **PUT** route (full resource update).

---

### 🔹 `patch(string $uri, callable|array $action, array $middleware = []): void`

Registers a **PATCH** route (partial update).

---

### 🔹 `delete(string $uri, callable|array $action, array $middleware = []): void`

Registers a **DELETE** route.

---

## 🧠 Internal Methods

---

### 🔹 `isClosure(mixed $action): bool`

#### Description

Determines whether the provided action is callable.

#### Returns

* `true` → closure or callable
* `false` → assumed controller action

---

## 🚀 Request Handling

---

### 🔹 `dispatch(string $uri, string $method): mixed`

#### Description

Resolves and executes the route for a given request.

---

### 🔄 Execution Flow

1. **Find route**

   ```php
   $route = $this->routes[$method][$uri] ?? null;
   ```

2. **Throw exception if not found**

   ```php
   throw new \Exception("Route not found");
   ```

3. **Execute middleware**

   ```php
   foreach ($route['middleware'] as $mw) {
       (new $mw)->handle();
   }
   ```

4. **Execute handler**

   * If closure:

     ```php
     return call_user_func($route['action']);
     ```
   * If controller:

     ```php
     [$controller, $method] = $route['action'];
     return (new $controller)->$method();
     ```

---

## 🧩 Route Action Types

### 1. Closure-based

```php
$router->get('/hello', function () {
    return "Hello World";
});
```

---

### 2. Controller-based

```php
$router->get('/users', [UserController::class, 'index']);
```

---

## 🛡️ Middleware

### Description

Middleware are executed **before** the route handler.

### Requirements

* Must be a class
* Must implement:

```php
public function handle()
```

---

### Example

```php
class AuthMiddleware {
    public function handle() {
        // authentication logic
    }
}
```

---

### Usage

```php
$router->get('/dashboard', [DashboardController::class, 'index'], [
    AuthMiddleware::class
]);
```

---

## 🧱 Route Groups with Middleware

```php
$router->group(['middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/profile', [UserController::class, 'profile']);
});
```

---

## ⚠️ Error Handling

* Throws `\Exception` if route is not found:

```php
throw new \Exception("Route not found");
```

---

## 🧠 Design Notes

* Middleware is executed in order
* Supports both functional and OOP handlers
* Keeps routing logic simple and extensible
* Uses method-based routing structure for clarity

---

## 🚀 Example Usage

```php
$router = new Router();

$router->get('/', function () {
    return "Home";
});

$router->post('/login', [AuthController::class, 'login']);

echo $router->dispatch('/', 'GET');
```

---

## 📌 Summary

The `Router` class provides:

* Clean HTTP method routing
* Flexible handler support
* Middleware integration
* Group-based route organization

It serves as a lightweight foundation for building custom PHP frameworks or APIs.
