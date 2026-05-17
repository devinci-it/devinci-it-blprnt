<?php

namespace DevinciIT\Blprnt\Core;

class Route
{
    public function __construct(
        public string $method,
        public string $uri,
        public $action,
        public array $middleware = []
    ) {}

    public function middleware(array $mw): self
    {
        $this->middleware = array_merge($this->middleware, $mw);
        return $this;
    }
}