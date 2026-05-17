<?php

namespace DevinciIT\Blprnt\Core;

class MiddlewarePipeline
{
    protected array $middleware = [];

    public function __construct(array $middleware = [])
    {
        $this->middleware = $middleware;
    }

    public function then(callable $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            function ($next, $middleware) {
                return function ($request) use ($middleware, $next) {
                    $instance = is_string($middleware) ? new $middleware : $middleware;
                    return $instance->handle($request, $next);
                };
            },
            $destination
        );
        return $pipeline;
    }
}
