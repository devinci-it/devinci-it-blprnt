<?php
namespace DevinciIT\Blprnt\Core;

class Router
{
    protected $routes = [];
    protected $groupMiddleware = [];

    public function group($opts, $callback)
    {
        $this->groupMiddleware = $opts['middleware'] ?? [];
        $callback($this);
        $this->groupMiddleware = [];
    }

    public function get($uri, $action, $middleware = [])
    {
        $this->routes['GET'][$uri] = [
            'action' => $action,
            'middleware' => array_merge($this->groupMiddleware, $middleware)
        ];
    }

    public function dispatch($uri, $method)
    {
        $route = $this->routes[$method][$uri] ?? null;
        if (!$route) {
            throw new \Exception("Route not found");
        }
        foreach ($route['middleware'] as $mw) {
            (new $mw)->handle();
        }
        [$controller, $method] = $route['action'];
        return (new $controller)->$method();
    }
}
