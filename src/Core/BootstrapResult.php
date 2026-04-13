<?php

namespace DevinciIT\Blprnt\Core;

use DevinciIT\Blprnt\Http\Kernel;

class BootstrapResult
{
    public function __construct(
        public App $app,
        public Router $router,
        public Kernel $kernel
    ) {}
}
