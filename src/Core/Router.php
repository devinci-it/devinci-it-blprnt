<?php

namespace DevinciIT\Blprnt\Core;

use DevinciIT\Blprnt\Core\Route;

class Router
{
    protected array $routes = [];

    protected array $groupMiddleware = [];

    protected string $groupPrefix = '';

    protected array $globalMiddleware = [];

    /*
    |--------------------------------------------------------------------------
    | GLOBAL MIDDLEWARE (NEW)
    |--------------------------------------------------------------------------
    */
    public function middleware(array $middleware): self
    {
        $this->globalMiddleware = array_merge(
            $this->globalMiddleware,
            $middleware
        );

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTE GROUPS
    |--------------------------------------------------------------------------
    */
    public function group(array $opts, callable $callback): void
    {
        $previousMiddleware = $this->groupMiddleware;
        $previousPrefix = $this->groupPrefix;

        $this->groupMiddleware = $opts['middleware'] ?? [];
        $this->groupPrefix = $opts['prefix'] ?? '';

        $callback($this);

        $this->groupMiddleware = $previousMiddleware;
        $this->groupPrefix = $previousPrefix;
    }

    /*
    |--------------------------------------------------------------------------
    | ROUTE REGISTRATION
    |--------------------------------------------------------------------------
    */
    protected function addRoute(string $method, string $uri, $action, array $middleware = []): Route
    {
        $fullUri = $this->groupPrefix . $uri;

        $route = new Route(
            $method,
            $fullUri,
            $action,
            array_merge($this->groupMiddleware, $middleware)
        );

        $this->routes[$method][$fullUri] = $route;

        return $route;
    }

    public function get(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function patch(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('PATCH', $uri, $action, $middleware);
    }

    public function delete(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD ROUTES FILE
    |--------------------------------------------------------------------------
    */
    public function load(?string $path = null): self
    {
        if ($path === null) {
            $root = $this->findProjectRoot();
            $path = $root . '/routes/web.php';
        }

        if (!file_exists($path)) {
            throw new \RuntimeException("Route file not found: {$path}");
        }

        $router = $this;
        $GLOBALS['router'] = $this;

        require $path;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | DISPATCH (FULL FIXED PIPELINE)
    |--------------------------------------------------------------------------
    */
    public function dispatch(string $uri, string $method, $request = null)
    {
        $route = null;
        $params = [];

        /*
        |--------------------------------------------------------------------------
        | 1. MATCH ROUTE
        |--------------------------------------------------------------------------
        */

        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
        } else {
            foreach ($this->routes[$method] ?? [] as $pattern => $r) {

                $regex = preg_replace(
                    '/\{([^}]+)\}/',
                    '(?P<\1>[^/]+)',
                    $pattern
                );

                $regex = "#^{$regex}$#";

                if (preg_match($regex, $uri, $matches)) {
                    $route = $r;

                    foreach ($matches as $key => $value) {
                        if (!is_numeric($key)) {
                            $params[$key] = $value;
                        }
                    }

                    break;
                }
            }
        }

        if (!$route) {
            throw new \RuntimeException("Route not found: {$method} {$uri}", 404);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. BUILD CONTROLLER EXECUTOR
        |--------------------------------------------------------------------------
        */

        $controllerExecutor = function ($request) use ($route, $params) {

            if ($route->action instanceof \Closure) {
                return ($route->action)($request, ...array_values($params));
            }

            if (is_array($route->action)) {
                [$controller, $methodName] = $route->action;

                $instance = new $controller();

                return $instance->$methodName(
                    $request,
                    ...array_values($params)
                );
            }

            throw new \RuntimeException("Invalid route action type");
        };

        /*
        |--------------------------------------------------------------------------
        | 3. BUILD FULL MIDDLEWARE STACK
        |--------------------------------------------------------------------------
        */

        $middlewareStack = array_merge(
            $this->globalMiddleware,
            $route->middleware ?? []
        );

        $middlewareStack = array_reverse($middlewareStack);

        $pipeline = array_reduce(
            $middlewareStack,
            function ($next, $middleware) {

                return function ($request) use ($next, $middleware) {

                    $instance = is_string($middleware)
                        ? new $middleware()
                        : $middleware;

                    if (!method_exists($instance, 'handle')) {
                        throw new \RuntimeException(
                            "Invalid middleware: " . get_class($instance)
                        );
                    }

                    return $instance->handle($request, $next);
                };
            },
            $controllerExecutor
        );

        /*
        |--------------------------------------------------------------------------
        | 4. EXECUTE PIPELINE
        |--------------------------------------------------------------------------
        */

        return $pipeline($request);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    protected function findProjectRoot(): string
    {
        $dir = __DIR__;

        while ($dir !== '/') {
            if (is_dir($dir . '/vendor')) {
                return $dir;
            }
            $dir = dirname($dir);
        }

        return dirname(dirname(__DIR__));
    }
}