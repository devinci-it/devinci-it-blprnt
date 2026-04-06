<?php
namespace DevinciIT\Blprnt\Core;

class App
{
    protected array $bindings = [];

    public function bind($key, $resolver)
    {
        $this->bindings[$key] = $resolver;
    }

    public function make($key)
    {
        return $this->bindings[$key]();
    }
}
