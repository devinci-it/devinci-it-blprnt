<?php
namespace DevinciIT\Blprnt\Middleware;

class AuthTokenMiddleware
{
    /**
     * Bearer-token check for API routes.
     *
     * Was previously handle() with no parameters and no $next($request)
     * call — MiddlewarePipeline calls every middleware as
     * handle($request, $next), so the missing params were silently ignored
     * (PHP doesn't enforce arg count against a looser signature), but never
     * calling $next() meant a *valid* token still dead-ended the pipeline:
     * the controller after it never ran, and the response was empty even
     * on success. See docs/wiki/Core/Middleware.wiki.md.
     */
    public function handle($request, $next)
    {
        $expectedToken = $_ENV['API_TOKEN'] ?? null;

        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if ($token !== 'Bearer ' . $expectedToken) {
            http_response_code(401);
            exit(json_encode(['error' => 'Unauthorized']));
        }

        return $next($request);
    }
}
