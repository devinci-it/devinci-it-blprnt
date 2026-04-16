<?php

namespace DevinciIT\Blprnt\Middleware;

use DevinciIT\Blprnt\Auth\Auth;

class GuestGuard
{
    public function handle($request, $next)
    {
        $auth = Auth::make();
        if ($auth->check()) {
            // Already authenticated, redirect to home or intended
            redirect('/');
        }
        return $next($request);
    }
}
