<?php

namespace DevinciIT\Blprnt\Middleware;

use DevinciIT\Blprnt\Auth\Auth;

class AuthGuard
{
    public function handle($request, $next)
    {
        $auth = Auth::make();

        // Debug: log session and auth state
        error_log('AuthGuard: session id=' . session_id());
        error_log('AuthGuard: auth->check()=' . var_export($auth->check(), true));
        error_log('AuthGuard: session data=' . var_export($_SESSION, true));

        if (!$auth->check()) {
            // Store intended URL for redirect after login
            $intended = method_exists($request, 'uri') ? $request->uri() : ($request['path'] ?? '/');
            $auth->session->set('intended_url', $intended);
            redirect('/login');
        }

        return $next($request);
    }
}
