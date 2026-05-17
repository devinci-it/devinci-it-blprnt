<?php
namespace DevinciIT\Blprnt\Console;

interface CommandHandler
{
    public function handle(array $args, Command $command);
}
