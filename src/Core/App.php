<?php
namespace DevinciIT\Blprnt\Core;

/**
 * Application Service Container
 *
 * Centralized container for managing application services and resolving dependencies.
 * Uses singleton pattern to ensure only one instance exists throughout the application.
 *
 * @example
 * // Register a service
 * app()->bind('router', fn() => new Router());
 *
 * // Retrieve a service
 * $router = app()->make('router');
 *
 * // Check if service exists
 * if (app()->has('router')) { ... }
 *
 * // Get all registered bindings
 * $services = app()->bindings();
 */
class App
{
    protected array $bindings = [];
    protected static ?self $instance = null;

    /**
     * Get the singleton application instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Prevent cloning to maintain singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization to maintain singleton
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize singleton instance');
    }

    /**
     * Register a service binding
     *
     * @param string $key The service key
     * @param callable $resolver A closure that resolves the service
     */
    public function bind(string $key, callable $resolver): self
    {
        $this->bindings[$key] = $resolver;

        return $this;
    }

    /**
     * Resolve a service from the container
     *
     * @param string $key The service key
     * @return mixed The resolved service instance
     * @throws \RuntimeException if service not found
     */
    public function make(string $key)
    {
        if (!isset($this->bindings[$key])) {
            throw new \RuntimeException("Service '{$key}' not found in container");
        }

        return $this->bindings[$key]($this);
    }

    /**
     * Check if a service is registered
     *
     * @param string $key The service key
     */
    public function has(string $key): bool
    {
        return isset($this->bindings[$key]);
    }

    /**
     * Get all registered service bindings (keys only)
     *
     * @return array Array of registered service keys
     */
    public function bindings(): array
    {
        return array_keys($this->bindings);
    }
}
