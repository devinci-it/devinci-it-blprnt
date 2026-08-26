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

                    if (!method_exists($instance, 'handle')) {
                        throw new \RuntimeException(
                            "Invalid middleware: " . get_class($instance)
                        );
                    }

                    return $instance->handle($request, $next);
                };
            },
            $destination
        );
        return $pipeline;
    }
}
