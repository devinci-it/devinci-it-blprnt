<?php

namespace DevinciIT\Blprnt\Http;

interface Middleware
{
    public function handle(array $request, callable $next);
}