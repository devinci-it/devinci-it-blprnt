<?php
namespace DevinciIT\Blprnt\Core;

use DevinciIT\Blprnt\Console\Kernel;
use DevinciIT\Blprnt\Console\CommandRegistry;

class CLIBootstrap
{
    public function __construct(
        public App $app,
        public Kernel $kernel
    ) {}

    public static function builder(string $basePath): CLIBootstrapBuilder
    {
        return new CLIBootstrapBuilder($basePath);
    }
}