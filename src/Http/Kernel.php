<?php
namespace DevinciIT\Blprnt\Http;

use DevinciIT\Blprnt\Core\Router;
use DevinciIT\Blprnt\Core\Request;

class Kernel
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Handle incoming request
     *
     * ARCHITECTURE: Request → Kernel → Router → Controller → Response
     *
     * Kernel's role:
     * 1. Detect request type (web/api/cli)
     * 2. Route to appropriate handler
     * 3. Format response based on type
     *
     * Router doesn't care about type - just matches and dispatches
     * Controllers don't care about type - just return data
     */
    public function handle(Request $request)
    {
        try {
            // Route based on request type
            if ($request->isCli()) {
                return $this->handleCli($request);
            }

            if ($request->isApi()) {
                return $this->handleApi($request);
            }

            return $this->handleWeb($request);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Handle web requests (HTML responses)
     *
     * Traditional HTTP requests from browsers.
     * Response flows through normally without JSON wrapping.
     */
    protected function handleWeb(Request $request)
    {
        return $this->router->dispatch(
            $request->uri(),
            $request->method(),
            $request
        );
    }

    /**
     * Handle API requests (JSON responses)
     *
     * API requests identified by:
     * - /api route prefix
     * - application/json Accept header
     * - application/json Content-Type
     *
     * Responses are wrapped in JSON envelope with success/data structure.
     */
    protected function handleApi(Request $request)
    {
        header('Content-Type: application/json');

        $response = $this->router->dispatch(
            $request->uri(),
            $request->method(),
            $request
        );

        // Wrap response in API envelope
        if (is_array($response) || is_object($response)) {
            return json_encode([
                'success' => true,
                'data' => $response
            ]);
        }

        // Already JSON string or other response
        return $response;
    }

    /**
     * Handle CLI requests (console/command-line)
     *
     * CLI requests detected via php_sapi_name() === 'cli'
     *
     * Example usage:
     * - php app route:list
     * - php app command:name
     */
    protected function handleCli(Request $request)
    {
        // Get CLI arguments
        $args = $_SERVER['argv'] ?? [];

        // Skip script name
        array_shift($args);

        if (empty($args)) {
            return $this->listAvailableCommands();
        }

        $command = $args[0];

        // Route CLI commands
        if ($command === 'route:list') {
            return $this->listRoutes();
        }

        if ($command === 'route:check') {
            $uri = $args[1] ?? null;
            $method = $args[2] ?? 'GET';
            return $this->checkRoute($uri, $method);
        }

        return "Unknown CLI command: {$command}\n";
    }

    /**
     * List all registered routes
     */
    protected function listRoutes(): string
    {
        $output = "\n=== Registered Routes ===\n";

        // Access protected routes via reflection if needed, or store in public method
        // For now, just show a message - you might want to extend Router with a getRoutes() method
        $output .= "Use router()->getRoutes() to display all routes\n";

        return $output;
    }

    /**
     * Check if a specific route exists
     */
    protected function checkRoute(?string $uri, string $method): string
    {
        if ($uri === null) {
            return "Usage: php app route:check <uri> [method]\n";
        }

        return "Route check: {$method} {$uri}\n";
    }

    /**
     * List available CLI commands
     */
    protected function listAvailableCommands(): string
    {
        return <<<'COMMANDS'

=== Blprnt CLI Commands ===

  route:list              List all registered routes
  route:check <uri>       Check if a route exists

COMMANDS;
    }

    /**
     * Handle exceptions with appropriate response format
     */
    protected function handleException(\Throwable $e, Request $request)
    {
        $statusCode = 500;
        $message = $e->getMessage();

        // CLI exceptions - write to STDERR
        if ($request->isCli()) {
            fwrite(STDERR, "Error: {$message}\n");
            return null;
        }

        // API exceptions - return JSON error
        if ($request->isApi()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            return json_encode([
                'success' => false,
                'error' => $message
            ]);
        }

        // Web exceptions - throw and let error handler deal with it
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        throw $e;
    }
}
