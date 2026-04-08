<?php
namespace DevinciIT\Blprnt\Http;

use DevinciIT\Blprnt\Core\Router;

class Kernel
{
    protected Router $router;
    protected string $requestType = 'web';

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * Handle incoming request
     *
     * Architecture: Request → Kernel (decides type) → Router → Controller → Response
     *
     * The Kernel is the decision point for:
     * - Detecting request type (API, Web, Console)
     * - Routing the request
     * - Formatting the response appropriately
     *
     * Controllers should NOT know about request type - they just return data
     */
    public function handle($request)
    {
        // Kernel detects the request type based on headers, route, and environment
        $this->detectRequestType($request);

        try {
            $response = $this->router->dispatch(
                $request->uri(),
                $request->method(),
                $request
            );

            // Kernel formats response based on detected type
            return $this->formatResponse($response);
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Detect request type: 'api', 'web', or 'console'
     * 
     * Detection logic (in order):
     * 1. CLI requests → 'console'
     * 2. /api routes → 'api'
     * 3. application/json Accept/Content-Type → 'api'
     * 4. Everything else → 'web'
     */
    protected function detectRequestType($request): void
    {
        // Console request
        if ($request::isCliRequest()) {
            $this->requestType = 'console';
            return;
        }

        // API request indicators
        $uri = $request->uri();
        $isApiRoute = strpos($uri, '/api') === 0 || strpos($uri, '/api/') === 0;
        $acceptsJson = strpos($request->accept(), 'application/json') !== false;
        $isJsonContent = strpos($request->contentType(), 'application/json') !== false;

        if ($isApiRoute || $acceptsJson || $isJsonContent) {
            $this->requestType = 'api';
        } else {
            $this->requestType = 'web';
        }
    }

    /**
     * Get the detected request type
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

    /**
     * Format response based on request type
     * 
     * Kernel handles ALL type-specific logic here - controllers never know the type
     */
    protected function formatResponse($response)
    {
        // Console requests pass through as-is
        if ($this->requestType === 'console') {
            return $response;
        }

        // API requests return JSON with wrapper
        if ($this->requestType === 'api') {
            header('Content-Type: application/json');
            if (is_array($response) || is_object($response)) {
                return json_encode([
                    'success' => true,
                    'data' => $response
                ]);
            }
            return $response;
        }

        // Web requests return response as-is (HTML/view rendering)
        return $response;
    }

    /**
     * Handle exceptions - Kernel decides how to respond based on type
     * 
     * Controllers never throw type-specific responses - Kernel handles it
     */
    protected function handleException(\Throwable $e)
    {
        $statusCode = 500;
        $message = $e->getMessage();

        // Console requests - write to STDERR
        if ($this->requestType === 'console') {
            fwrite(STDERR, "Error: {$message}\n");
            return null;
        }

        // API requests - return JSON error
        if ($this->requestType === 'api') {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            return json_encode([
                'success' => false,
                'error' => $message
            ]);
        }

        // Web requests - throw or render error page
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        throw $e;
    }
}
