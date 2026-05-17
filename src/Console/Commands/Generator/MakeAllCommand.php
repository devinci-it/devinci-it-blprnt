<?php
namespace DevinciIT\Blprnt\Console\Commands\Generator;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Console\CommandRegistry;

class MakeAllCommand extends Command
{
    protected string $signature = 'make:all';
    protected string $description = 'Scaffold controller, view, and service in one command';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('force', 'f', false, false)
            ->addOption('uri', 'u', true, false, null)
            ->addOption('file', null, true, false, 'web');
    }

    public function handle(array $args = [])
    {
        if ((bool)$this->getOption('help', false)) {
            $this->printHelp();
            return;
        }

        $name = trim((string)($args[0] ?? ''));
        if ($name === '') {
            fwrite(STDERR, "Missing base name.\n\n");
            $this->printHelp();
            return;
        }

        $force = (bool)$this->getOption('force', false);

        $controller = CommandRegistry::get('make:controller');
        $view = CommandRegistry::get('make:view');
        $service = CommandRegistry::get('make:service');

        if ($controller === null || $view === null || $service === null) {
            fwrite(STDERR, "Required generator commands are not registered.\n");
            return;
        }

        $controllerArgs = [$name];
        $viewArgs = [$this->toViewName($name)];
        $serviceArgs = [$name];

        if ($force) {
            $controllerArgs[] = '--force';
            $viewArgs[] = '--force';
            $serviceArgs[] = '--force';
        }

        $controller->run($controllerArgs);
        $view->run($viewArgs);
        $service->run($serviceArgs);

        $route = CommandRegistry::get('make:route');
        if ($route !== null) {
            $uri = trim((string)($this->getOption('uri') ?? ''));
            if ($uri === '') {
                $uri = '/' . $this->toViewName($name);
            }

            $routeFile = strtolower(trim((string)($this->getOption('file') ?? 'web')));
            $controllerClass = $this->toControllerClass($name);

            $routeArgs = [
                '--method=get',
                '--uri=' . $uri,
                '--controller=' . $controllerClass,
                '--action=index',
                '--file=' . $routeFile,
            ];

            if ($force) {
                $routeArgs[] = '--force';
            }

            $route->run($routeArgs);
            $this->appendCommentedPostRoute($routeFile, $uri, $controllerClass, $force);
        }
    }

    private function toViewName(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $parts = explode('/', trim($name, '/'));
        $result = [];

        foreach ($parts as $part) {
            $kebab = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '-$0', preg_replace('/[^A-Za-z0-9]+/', '', $part) ?? $part));
            if ($kebab !== '') {
                $result[] = $kebab;
            }
        }

        return implode('/', $result);
    }

    private function toControllerClass(string $name): string
    {
        $name = str_replace('/', '\\', trim($name));
        $parts = explode('\\', trim($name, '\\'));
        $studly = [];

        foreach ($parts as $part) {
            $built = $this->toStudlyCase($part);
            if ($built !== '') {
                $studly[] = $built;
            }
        }

        if (empty($studly)) {
            return 'App\\Controllers\\GeneratedController';
        }

        $last = array_pop($studly);
        $last = (string)preg_replace('/Controller$/i', '', $last) . 'Controller';
        $studly[] = $last;

        return 'App\\Controllers\\' . implode('\\', $studly);
    }

    private function appendCommentedPostRoute(string $routeFileKey, string $uri, string $controllerClass, bool $force): void
    {
        $routePath = $this->resolveRoutePath($routeFileKey);
        if (!is_file($routePath)) {
            return;
        }

        $line = sprintf(
            "// %s",
            sprintf(
                "\$router->post('%s', [%s::class, 'store']);",
                $uri,
                $controllerClass
            )
        );

        $contents = file_get_contents($routePath);
        if ($contents === false) {
            return;
        }

        if (strpos($contents, $line) !== false && !$force) {
            return;
        }

        $updated = rtrim($contents) . "\n" . $line . "\n";
        if (file_put_contents($routePath, $updated) !== false) {
            echo "Appended commented POST example to {$routePath}: {$line}\n";
        }
    }

    private function resolveRoutePath(string $key): string
    {
        if ($key === 'api') {
            return $this->resolvePath('routes/api.php');
        }

        if (str_ends_with($key, '.php')) {
            return $this->resolvePath($key);
        }

        return $this->resolvePath('routes/web.php');
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return getcwd();
        }

        if (str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim(getcwd() . '/' . ltrim($path, '/'), '/');
    }

    private function toStudlyCase(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^A-Za-z0-9]+/', ' ', $value) ?? '';
        $parts = preg_split('/\s+/', $value) ?: [];
        $result = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (!preg_match('/[a-z]/', $part)) {
                $part = strtolower($part);
            }

            $result .= ucfirst($part);
        }

        return $result;
    }

    private function printHelp(): void
    {
        echo "Usage:\n";
        echo "  blprnt make:all User\n";
        echo "  blprnt make:all Admin/UserProfile --uri=/admin/users/profile\n\n";
        echo "Options:\n";
        echo "  --force         Overwrite generated files\n";
        echo "  --uri           Route URI for default GET index route\n";
        echo "  --file          Route file key: web|api or relative path ending in .php\n";
    }
}
