<?php
namespace DevinciIT\Blprnt\Console;

class Kernel
{
    public function handle(array $argv)
    {
        $signature = $argv[1] ?? null;

        if ($signature === null || in_array($signature, ['help', '--help', '-h'], true)) {
            $this->list();
            return;
        }

        $args = array_slice($argv, 2);
        $command = CommandRegistry::get($signature);
        if ($command) {
            $command->run($args);
        } else {
            echo "Command not found: $signature\n";
            $this->list();
        }
    }

    public function list()
    {
        echo "Available commands:\n";
        $groups = CommandRegistry::grouped();

        foreach ($groups as $group => $commands) {
            echo "\n[" . $group . "]\n";

            foreach ($commands as $signature => $command) {
                echo $signature . "\t" . $command->getDescription() . "\n";
            }
        }
    }
}
