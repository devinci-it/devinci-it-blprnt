<?php
namespace DevinciIT\Blprnt\Core;

class Pipeline
{
    public static function run($middlewares, $destination)
    {
        foreach ($middlewares as $mw) {
            (new $mw)->handle();
        }
        return $destination();
    }
}
