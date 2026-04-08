<?php
namespace DevinciIT\Blprnt\Core;

/**
 * Router class for managing application routes
 *
 * Supports both controller/method actions and closures for route handlers.
 * Includes middleware support and route grouping capabilities.
 */
class Router
{
    /**
     * @var array Registered routes organized by HTTP method and URI
     */
    protected $routes = [];

    /**
     * @var array Middleware to apply to all routes in a group
     */
    protected $groupMiddleware = [];

    /**
     * @var string URI prefix to apply to all routes in a group
     */
    protected $groupPrefix = '';

    /**
     * Create a route group with shared middleware and/or prefix
     *
     * @param array $opts Group options including 'middleware' and 'prefix' keys
     * @param callable $callback Callback function to register routes within the group
     * @return void
     *
     * @example
     * $router->group(['prefix' => '/api', 'middleware' => [ApiMiddleware::class]], function ($router) {
     *     $router->get('/posts', [PostController::class, 'index']);
     * });
     */
    public function group($opts, $callback)
    {
        $this->groupMiddleware = $opts['middleware'] ?? [];
        $this->groupPrefix = $opts['prefix'] ?? '';
        $callback($this);
        $this->groupMiddleware = [];
        $this->groupPrefix = '';
    }

    /**
     * Add a route to the routes collection
     *
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $uri The URI path for the route
     * @param callable|array $action Either a closure or array [controller, method]
     * @param array $middleware Middleware to apply to this route
     * @return void
     */
    protected function addRoute($method, $uri, $action, $middleware = [])
    {
        // Apply group prefix if present
        $fullUri = $this->groupPrefix . $uri;

        $this->routes[$method][$fullUri] = [
            'action' => $action,
            'middleware' => array_merge($this->groupMiddleware, $middleware)
        ];
    }

    /**
     * Register a GET route
     *
     * @param string $uri The URI path for the route
     * @param callable|array $action Either a closure or array [controller, method]
     * @param array $middleware Middleware to apply to this route
     * @return void
     */
    public function get($uri, $action, $middleware = [])
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    /**
     * Register a POST route
     *
     * @param string $uri The URI path for the route
     * @param callable|array $action Either a closure or array [controller, method]
     * @param array $middleware Middleware to apply to this route
     * @return void
     */
    public function post($uri, $action, $middleware = [])
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    /**
     * Register a PUT route
     *
     * @param string $uri The URI path for the route
     * @param callable|array $action Either a closure or array [controller, method]
     * @param array $middleware Middleware to apply to this route
     * @return void
     */
    public function put($uri, $action, $middleware = [])
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    /**
     * Register a PATCH route
     *
     * @param string $uri The URI path for the route
     * @param callable|array $action Either a closure or array [controller, method]
     * @param array $middleware Middleware to apply to this route
     * @return void
     */
    public function patch($uri, $action, $middleware = [])
    {
        $this->addRoute('PATCH', $uri, $action, $middleware);
    }

    /**
     * Register a DELETE route
     *
     * @param string $uri The URI path for the route
     * @param callable|array $action Either a closure or array [controller, method]
     * @param array $middleware Middleware to apply to this route
     * @return void
     */
    public function delete($uri, $action, $middleware = [])
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    /**
     * Load routes from a file
     *
     * The route file is loaded in a context where $router (this Router) is available,
     * allowing routes to be registered via $router->get(), $router->post(), etc.
     *
     * Default path: routes/web.php relative to project root
     *
     * @param string|null $path Optional custom path to route file
     *                           If null, defaults to PROJECT_ROOT/routes/web.php
     *
     * @example
     * // Load default routes/web.php
     * $router->load();
     *
     * // Load specific route file
     * $router->load(__DIR__ . '/../routes/api.php');
     * $router->load(__DIR__ . '/../routes/admin.php');
     *
     * @return $this For method chaining
     */
    public function load(?string $path = null): self
    {
        // If no path provided, default to routes/web.php relative to project root
        if ($path === null) {
            // Find project root by looking for vendor directory
            $projectRoot = $this->findProjectRoot();
            $path = $projectRoot . '/routes/web.php';
        }

        // Make $router available to the route file as both local and global variable
        $router = $this;
        $GLOBALS['router'] = $this;

        // Load the route file in router context
        if (file_exists($path)) {
            require $path;
        } else {
            throw new \RuntimeException("Route file not found: {$path}");
        }

        return $this;
    }

    /**
     * Find the project root directory
     *
     * Traverses up the directory tree looking for vendor directory
     * or uses __DIR__ as fallback if in a different context
     *
     * @return string Absolute path to project root
     */
    protected function findProjectRoot(): string
    {
        $dir = __DIR__;
        
        // Traverse up looking for vendor directory
        while ($dir !== '/') {
            if (is_dir($dir . '/vendor')) {
                return $dir;
            }
            $dir = dirname($dir);
        }
        
        // Fallback: return two levels up from src/Core
        return dirname(dirname(__DIR__));
    }

    /**
     * Check if an action is a closure or callable function
     *
     * @param mixed $action The action to check
     * @return bool True if the action is a closure or callable
     */
    protected function isClosure($action): bool
    {
        return $action instanceof \Closure || is_callable($action);
    }

    /**
     * Dispatch a request to the appropriate route handler
     *
     * Supports both closure-based and controller-based actions.
     * Executes all registered middleware before calling the handler.
     *
     * @param string $uri The URI to dispatch
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return mixed The result from the route handler
     * @throws \Exception If the route is not found
     */
    public function dispatch($uri, $method, $request = null)
    {
        $route = null;
        $params = [];

        // Try exact match first
        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
        } else {
            // Try dynamic parameter matching
            foreach ($this->routes[$method] ?? [] as $routePattern => $routeData) {
                $pattern = preg_replace('/\{([^}]+)\}/', '(?P<\1>[^/]+)', $routePattern);
                $pattern = "#^{$pattern}$#";
                
                if (preg_match($pattern, $uri, $matches)) {
                    $route = $routeData;
                    // Extract named parameters
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
            // Debug: show what routes are available
            $availableRoutes = [];
            foreach ($this->routes as $methodKey => $uriRoutes) {
                foreach ($uriRoutes as $uriKey => $routeData) {
                    $availableRoutes[] = "$methodKey $uriKey";
                }
            }
            $message = "Route not found: $method $uri\n";
            if ($availableRoutes) {
                $message .= "Available routes: " . implode(", ", $availableRoutes);
            } else {
                $message .= "No routes registered";
            }
            throw new \Exception($message);
        }
        
        // Execute middleware - pass Request object to each middleware handler
        foreach ($route['middleware'] as $mw) {
            (new $mw)->handle($request);
        }
        
        // Handle closure-based action
        if ($this->isClosure($route['action'])) {
            return call_user_func($route['action'], $request, ...$params);
        }
        
        // Handle controller/method action
        [$controller, $actionMethod] = $route['action'];
        $controller_instance = new $controller();
        
        // Pass Request object and route parameters
        $paramValues = array_values($params);
        return $controller_instance->$actionMethod($request, ...$paramValues);
    }
}
