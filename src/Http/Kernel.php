<?php
namespace DevinciIT\Blprnt\Http;

use DevinciIT\Blprnt\Core\Router;

class Kernel
{
    protected Router $router;
    public function __construct(Router $router)
    {
        $this->router = $router;
    }
    public function handle($request)
    {
        return $this->router->dispatch(
            $request->uri(),
            $request->method()
        );
    }
}
