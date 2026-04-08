<?php
namespace DevinciIT\Blprnt\Http;

use DevinciIT\Blprnt\Core\Router;

class Kernel
{
    protected Router $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Handle incoming request and route it based on type
     *
     * Differentiates between API, Web, and Console requests:
     * - API requests: JSON responses, content negotiation
     * - Web requests: HTML responses, traditional HTTP
     * - Console requests: CLI interactions
     */
    public function handle($request)
    {
        try {
            $response = $this->router->dispatch(
                $request->uri(),
                $request->method(),
                $request
            );

            // Format response based on request type
            return $this->formatResponse($response, $request);
        } catch (\Throwable $e) {
            return $this->handleException($e, $request);
        }
    }

    /**
     * Format the response based on request type
     */
    protected function formatResponse($response, $request)
    {
        // Console requests pass through as-is
        if ($request->isConsole()) {
            return $response;
        }

        // API requests return JSON
        if ($request->isApi()) {
            // Already a response object or array, wrap if needed
            if (is_array($response) || is_object($response)) {
                return json_encode([
                    'success' => true,
                    'data' => $response,
                    'type' => 'api'
                ]);
            }
            return $response;
        }

        // Web requests return as-is (HTML/view rendering)
        return $response;
    }

    /**
     * Handle exceptions based on request type
     */
    protected function handleException(\Throwable $e, $request)
    {
        $statusCode = 500;
        $message = $e->getMessage();

        // Console requests
        if ($request->isConsole()) {
            fwrite(STDERR, "Error: {$message}\n");
            return null;
        }

        // API requests return JSON error
        if ($request->isApi()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            return json_encode([
                'success' => false,
                'error' => $message,
                'type' => 'api'
            ]);
        }

        // Web requests - render error page or throw
        http_response_code($statusCode);
        header('Content-Type: text/html');
        throw $e;
    }
}
