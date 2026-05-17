<?php

namespace DevinciIT\Blprnt\Middleware;

use DevinciIT\Blprnt\Auth\Auth;

class AuthMiddleware
{
    /**
     * @var Auth
     */
    protected Auth $auth;

    /**
     * Inject Auth instance
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle request, protect route
     * Checks session or token
     */
    public function handle($request, callable $next)
    {
        // Check session
        if ($this->auth->check()) {
            return $next($request);
        }

        // Check token (e.g., from header or query)
        if ($this->auth->validateToken($request)) {
            return $next($request);
        }

        // Not authenticated
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }
}
