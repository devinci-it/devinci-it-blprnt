<?php
namespace DevinciIT\Blprnt\Console\Commands\Generator;

use DevinciIT\Blprnt\Console\Command;

class MakeRouteCommand extends Command
{
    protected string $signature = 'make:route';
    protected string $description = 'Append a route definition using the route stub';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('method', 'm', true, false, 'get')
            ->addOption('uri', 'u', true, false, null)
            ->addOption('controller', 'c', true, false, null)
            ->addOption('action', 'a', true, false, 'index')
            ->addOption('file', 'f', true, false, 'web')
            ->addOption('force', null, false, false);
    }

    public function handle(array $args = [])
    {
        if ((bool)$this->getOption('help', false)) {
            $this->printHelp();
            return;
        }

        $unknown = $this->getUnknownOptions();
        if (!empty($unknown)) {
            fwrite(STDERR, 'Unknown option(s): ' . implode(', ', $unknown) . "\n");
            $this->printHelp();
            return;
        }

        $method = strtolower(trim((string)($this->getOption('method') ?? 'get')));
        $uri = trim((string)($this->getOption('uri') ?? ''));
        $controller = trim((string)($this->getOption('controller') ?? ''));
        $action = trim((string)($this->getOption('action') ?? 'index'));
        $routeFileKey = strtolower(trim((string)($this->getOption('file') ?? 'web')));

        if ($uri === '' && isset($args[0])) {
            $uri = trim((string)$args[0]);
        }
        if ($controller === '' && isset($args[1])) {
            $controller = trim((string)$args[1]);
        }
        if ($action === 'index' && isset($args[2])) {
            $action = trim((string)$args[2]);
        }

        if (!in_array($method, ['get', 'post', 'put', 'patch', 'delete'], true)) {
            fwrite(STDERR, "Invalid method: {$method}. Allowed: get, post, put, patch, delete.\n");
            return;
        }

        if ($uri === '' || $controller === '') {
            fwrite(STDERR, "Route URI and controller are required.\n\n");
            $this->printHelp();
            return;
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $action)) {
            fwrite(STDERR, "Invalid action name: {$action}\n");
            return;
        }

        $controllerClass = $this->normalizeControllerClass($controller);
        $routeFile = $this->resolveRouteFile($routeFileKey);

        if (!is_file($routeFile)) {
            fwrite(STDERR, "Route file not found: {$routeFile}\n");
            return;
        }

        $stub = $this->loadStub('Route.php.tmp');
        if ($stub === null) {
            fwrite(STDERR, "Unable to locate Route.php.tmp stub.\n");
            return;
        }

        $line = str_replace(
            ['{{ method }}', '{{ uri }}', '{{ controller }}', '{{ action }}'],
            [$method, $uri, $controllerClass, $action],
            $stub
        );

        $line = rtrim($line);

        $existing = file_get_contents($routeFile);
        if ($existing === false) {
            fwrite(STDERR, "Unable to read route file: {$routeFile}\n");
            return;
        }

        $force = (bool)$this->getOption('force', false);
        if (strpos($existing, $line) !== false && !$force) {
            fwrite(STDERR, "Route already exists in {$routeFile}. Use --force to append duplicate.\n");
            return;
        }

        $updated = rtrim($existing) . "\n" . $line . "\n";
        if (file_put_contents($routeFile, $updated) === false) {
            fwrite(STDERR, "Unable to write route file: {$routeFile}\n");
            return;
        }

        echo "Appended route to {$routeFile}: {$line}\n";
    }

    private function normalizeControllerClass(string $controller): string
    {
        $controller = trim(str_replace('/', '\\', $controller), "\\ \t\n\r\0\x0B");

        if (str_contains($controller, '::class')) {
            return $controller;
        }

        if (str_starts_with($controller, 'App\\')) {
            return $controller;
        }

        if (str_starts_with($controller, 'Controllers\\')) {
            return 'App\\' . $controller;
        }

        if (str_starts_with($controller, 'App\\Controllers\\')) {
            return $controller;
        }

        return 'App\\Controllers\\' . $controller;
    }

    private function resolveRouteFile(string $key): string
    {
        if ($key === 'api') {
            return $this->resolvePath('routes/api.php');
        }

        if (str_ends_with($key, '.php')) {
            return $this->resolvePath($key);
        }

        return $this->resolvePath('routes/web.php');
    }

    private function loadStub(string $stubName): ?string
    {
        $packageRoot = dirname(__DIR__, 4);
        $candidates = [
            getcwd() . '/resources/stub/' . $stubName,
            $packageRoot . '/resources/stub/' . $stubName,
        ];

        foreach ($candidates as $path) {
            if (!is_file($path)) {
                continue;
            }
            $contents = file_get_contents($path);
            if ($contents !== false) {
                return $contents;
            }
        }

        return null;
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

    private function printHelp(): void
    {
        echo "Usage:\n";
        echo "  blprnt make:route --uri=/users --controller=UserController\n";
        echo "  blprnt make:route --method=post --uri=/users --controller=Admin/UserController --action=store --file=api\n\n";
        echo "Options:\n";
        echo "  --method        HTTP method (get, post, put, patch, delete)\n";
        echo "  --uri           Route URI (example: /users/{id})\n";
        echo "  --controller    Controller class (UserController or App\\Controllers\\UserController)\n";
        echo "  --action        Controller method (default: index)\n";
        echo "  --file          Route file key: web|api or explicit relative path ending in .php\n";
        echo "  --force         Append duplicate route entry if already present\n";
    }
}
