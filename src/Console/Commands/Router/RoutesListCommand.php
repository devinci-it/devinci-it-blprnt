<?php

namespace DevinciIT\Blprnt\Console\Commands\Router;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Core\Router;

/**
 * List all registered routes in the application
 *
 * Shows:
 * - HTTP method (GET, POST, PUT, PATCH, DELETE)
 * - Route URI (with dynamic parameters like {id})
 * - Route handler (controller/method or closure)
 * - Associated middleware
 *
 * Usage:
 *   php blprnt routes:list
 *   php blprnt routes:list --filter=/api
 */
class RoutesListCommand extends Command
{
    protected string $signature = 'routes:list';
    protected string $description = 'List all registered application routes';

    public function handle(array $args = []): void
    {
        // Load the consumer application context (bootstrap/app.php)
        // This initializes the Router and registers all consumer routes
        $this->loadApplication();

        // Get the router from container (or create new one if bootstrap didn't initialize it)
        if (function_exists('router')) {
            $router = router();
        } else {
            $router = new Router();
        }

        $allRoutes = $router->getRoutes();

        if (empty($allRoutes)) {
            echo "No routes registered.\n";
            return;
        }

        echo "\n";
        echo str_repeat('=', 120) . "\n";
        echo "Registered Routes\n";
        echo str_repeat('=', 120) . "\n\n";

        // Prepare table headers
        $headers = ['METHOD', 'URI', 'HANDLER', 'MIDDLEWARE'];
        $columnWidths = [12, 35, 40, 30];

        // Print headers
        echo $this->formatRow($headers, $columnWidths);
        echo str_repeat('-', 120) . "\n";

        // Sort routes by method then URI for better readability
        $sortedRoutes = $this->sortRoutes($allRoutes);

        $routeCount = 0;
        foreach ($sortedRoutes as $method => $uris) {
            foreach ($uris as $uri => $route) {
                $handler = $this->formatHandler($route['action']);
                $middleware = !empty($route['middleware']) 
                    ? implode(', ', array_map(fn($m) => $this->getShortClass($m), $route['middleware']))
                    : 'none';

                $row = [$method, $uri, $handler, $middleware];
                echo $this->formatRow($row, $columnWidths);
                $routeCount++;
            }
        }

        echo "\n" . str_repeat('=', 120) . "\n";
        echo "Total: $routeCount route(s) registered\n";
        echo str_repeat('=', 120) . "\n\n";
    }

    /**
     * Sort routes by method and URI for better readability
     */
    private function sortRoutes(array $routes): array
    {
        $methodOrder = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
        $sorted = [];

        // Initialize sorted structure
        foreach ($methodOrder as $method) {
            if (isset($routes[$method])) {
                $sorted[$method] = $routes[$method];
            }
        }

        // Add any remaining methods
        foreach ($routes as $method => $uris) {
            if (!isset($sorted[$method])) {
                $sorted[$method] = $uris;
            }
        }

        // Sort URIs alphabetically within each method
        foreach ($sorted as &$uris) {
            ksort($uris);
        }

        return $sorted;
    }

    /**
     * Format route handler for display
     */
    private function formatHandler($action): string
    {
        if ($action instanceof \Closure) {
            return 'Closure';
        }

        if (is_array($action) && count($action) === 2) {
            [$controller, $method] = $action;
            $shortController = $this->getShortClass($controller);
            return "{$shortController}@{$method}";
        }

        if (is_callable($action)) {
            return 'Callable';
        }

        return 'Unknown';
    }

    /**
     * Get short class name (basename)
     */
    private function getShortClass($class): string
    {
        if (is_string($class)) {
            return substr(strrchr($class, '\\'), 1) ?: $class;
        }
        return 'Unknown';
    }

    /**
     * Format a table row with column widths
     */
    private function formatRow(array $columns, array $widths): string
    {
        $row = '';
        foreach ($columns as $i => $col) {
            $width = $widths[$i];
            $padded = str_pad(substr($col, 0, $width), $width);
            $row .= $padded . ' | ';
        }
        return rtrim($row, ' | ') . "\n";
    }

    /**
     * Load the consumer application context (bootstrap/app.php)
     * This initializes the Router and registers all consumer routes
     */
    private function loadApplication(): void
    {
        $projectRoot = getcwd();
        $bootstrapFile = $projectRoot . '/bootstrap/app.php';

        // Check if running from framework directory
        if (!file_exists($bootstrapFile)) {
            $bootstrapFile = __DIR__ . '/../../../../bootstrap/app.php';
        }

        if (file_exists($bootstrapFile)) {
            require_once $bootstrapFile;
        }
    }
}
