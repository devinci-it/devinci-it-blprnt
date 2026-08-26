<?php
namespace DevinciIT\Blprnt\Http;

use DevinciIT\Blprnt\Core\Router;
use DevinciIT\Blprnt\Core\Request;
use DevinciIT\Blprnt\Core\ErrorHandler;

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
     * 1. Detect request type (web/api)
     * 2. Route to appropriate handler
     * 3. Format response based on type
     *
     * Router doesn't care about type - just matches and dispatches
     * Controllers don't care about type - just return data
     *
     * CLI requests are handled by a separate stack (Console\Kernel via
     * CLIBootstrapBuilder) — see handleException() for why isCli() still
     * matters here.
     */
    public function handle(Request $request)
    {
        try {
            // Route based on request type
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
     * Handle exceptions with appropriate response format
     *
     * Note: the CLI branch below is a defensive fallback, not a real CLI entry
     * point — `php blprnt ...` goes through Console\Kernel via
     * CLIBootstrapBuilder, never through this Http\Kernel. This only fires if
     * Http\Kernel::handle() is ever invoked while running under the CLI SAPI.
     */
    protected function handleException(\Throwable $e, Request $request)
    {
        $statusCode = (int) $e->getCode();
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        $safeMessage = ErrorHandler::isLocalDevelopment()
            ? $e->getMessage()
            : ErrorHandler::getHttpStatusText($statusCode);

        // CLI exceptions - write to STDERR
        if ($request->isCli()) {
            fwrite(STDERR, "Error ({$statusCode}): {$safeMessage}\n");
            return null;
        }

        // API exceptions - return JSON error
        if ($request->isApi()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            return json_encode([
                'success' => false,
                'error' => $safeMessage
            ]);
        }

        // Web exceptions - local shows debug trace, production shows HTTP error page.
        if (ErrorHandler::isLocalDevelopment()) {
            throw $e;
        }

        ErrorHandler::renderHttpErrorPage($statusCode);
        return null;
    }
}
