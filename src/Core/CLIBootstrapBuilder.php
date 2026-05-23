<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Core;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Console\CommandRegistry;
use DevinciIT\Blprnt\Console\Kernel;
use DevinciIT\Blprnt\Console\CLI;
use DevinciIT\Blprnt\Support\IOHelper;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use ReflectionClass;

class CLIBootstrapBuilder
{
    protected string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = realpath($basePath) ?: $basePath;
    }

    public function build(): CLIBootstrap
    {
        $this->loadEnvironment();

        // You should eventually replace this with your container
        $app = new App($this->basePath);
        $io = new IOHelper();
        CLI::boot($io);

        $this->registerCommands();

        $kernel = new Kernel($app);

        return new CLIBootstrap($app, $kernel);
    }

    protected function loadEnvironment(): void
    {
        if (class_exists('Dotenv\\Dotenv') && is_file(getcwd() . '/.env')) {
            \Dotenv\Dotenv::createImmutable(getcwd())->safeLoad();
        }
    }

    protected function registerCommands(): void
    {
        $isFrameworkContext = $this->isFrameworkContext();

        if ($isFrameworkContext) {
            $this->registerPath(
                $this->basePath . '/src/Console/Commands',
                'DevinciIT\\Blprnt\\Console\\Commands'
            );
            return;
        }

        // Project commands
        $this->registerPath(
            getcwd() . '/src/Console/Commands',
            'App\\Console\\Commands'
        );

        // Framework commands
        $this->registerPath(
            $this->basePath . '/vendor/devinci-it/blprnt/src/Console/Commands',
            'DevinciIT\\Blprnt\\Console\\Commands'
        );
    }

    protected function isFrameworkContext(): bool
    {
        $composerPath = $this->basePath . '/composer.json';

        if (!file_exists($composerPath)) {
            return false;
        }

        $json = json_decode(file_get_contents($composerPath), true);

        return isset($json['name']) && $json['name'] === 'devinci-it/blprnt';
    }

    protected function registerPath(string $path, string $namespace): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $fullPath = $file->getPathname();
            require_once $fullPath;

            $relative = substr($fullPath, strlen($path) + 1);
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relative);
            $fqcn = $namespace . '\\' . $classPath;

            if (!class_exists($fqcn)) {
                continue;
            }

            if (!is_subclass_of($fqcn, Command::class)) {
                continue;
            }

            $reflection = new ReflectionClass($fqcn);

            if ($reflection->isAbstract()) {
                continue;
            }

            CommandRegistry::register(new $fqcn());
        }
    }
}