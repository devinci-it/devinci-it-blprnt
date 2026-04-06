<?php
namespace DevinciIT\Blprnt\Console\Commands;

use DevinciIT\Blprnt\Console\Command;

class HelloWorldCommand extends Command
{
    protected string $signature = 'hello:world';
    protected string $description = 'Prints Hello, World!';

    public function handle(array $args = [])
    {
        echo "Hello, World!\n";
    }
}
