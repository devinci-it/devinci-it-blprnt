<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Console\Commands;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Console\CommandRegistry;
use DevinciIT\Blprnt\Core\App;
use DevinciIT\Blprnt\Core\Router;
use DevinciIT\Blprnt\Core\View;

class HealthCheckCommand extends Command
{
    protected string $signature = 'health:check';
    protected string $description = 'Display application health summary (commands, services, routes, directories, env)';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('no-load', null, false, false)
            ->addOption('full-env', null, false, false);
    }

    public function handle(array $args = []): void
    {
        if ((bool) $this->getOption('help', false)) {
            $this->printHelp();
            return;
        }

        $unknown = $this->getUnknownOptions();
        if (!empty($unknown)) {
            fwrite(STDERR, 'Unknown option(s): ' . implode(', ', $unknown) . "\n");
            $this->printHelp();
            return;
        }

        $projectRoot = rtrim((string) getcwd(), '/');
        $skipLoad = (bool) $this->getOption('no-load', false);
        $showFullEnv = (bool) $this->getOption('full-env', false);

        if (!$skipLoad) {
            $this->loadApplication($projectRoot);
        }

        $header = [
            ['Item', 'Value'],
            ['Project root', $projectRoot],
            ['PHP version', PHP_VERSION],
            ['Environment', $this->env('APP_ENV', 'unknown')],
            ['Debug', $this->truthy($this->env('APP_DEBUG', 'false')) ? 'true' : 'false'],
            ['Application loaded', $skipLoad ? 'false (--no-load)' : 'true'],
        ];

        $paths = $this->resolvePaths($projectRoot);

        $dirsTable = [['Directory', 'Path', 'Exists']];
        foreach ($paths as $name => $path) {
            $dirsTable[] = [
                $name,
                $path,
                is_dir($path) ? 'yes' : 'no',
            ];
        }

        $serviceRows = [['Service key', 'Registered']];
        $app = App::getInstance();
        $bindings = $app->bindings();
        sort($bindings);

        $probeKeys = ['router'];
        $allServiceKeys = array_values(array_unique(array_merge($probeKeys, $bindings)));

        foreach ($allServiceKeys as $key) {
            $serviceRows[] = [$key, $app->has($key) ? 'yes' : 'no'];
        }

        $routesTable = [['Metric', 'Value']];
        $router = $this->resolveRouter();

        if ($router !== null) {
            $routeStats = $this->routeStats($router);
            foreach ($routeStats as $metric => $value) {
                $routesTable[] = [$metric, (string) $value];
            }
        } else {
            $routesTable[] = ['status', 'router unavailable'];
        }

        $commands = CommandRegistry::all();
        ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);

        $commandRows = [['Command', 'Description']];
        foreach ($commands as $signature => $command) {
            $commandRows[] = [$signature, $command->getDescription()];
        }

        $envRows = [['Key', 'Value']];
        foreach ($this->baseEnvKeys() as $key) {
            $envRows[] = [$key, $this->maskIfSensitive($key, $this->env($key, ''))];
        }

        if ($showFullEnv) {
            $seen = [];
            foreach (array_merge($_ENV, $_SERVER) as $key => $value) {
                if (!is_string($key) || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                if (!is_scalar($value)) {
                    continue;
                }

                $envRows[] = [$key, $this->maskIfSensitive($key, (string) $value)];
            }
        }

        echo "\n";
        $this->printSection('Health Check', $header);
        $this->printSection('Directories', $dirsTable);
        $this->printSection('Services', $serviceRows);
        $this->printSection('Routes', $routesTable);
        $this->printSection('Commands', $commandRows);
        $this->printSection('Environment', $envRows);
    }

    /**
     * @return array<string, string>
     */
    private function resolvePaths(string $projectRoot): array
    {
        $devSource = $this->truthy($this->env('BLPRNT_DEV_SOURCE', 'false'));
        $devRoot = trim((string) $this->env('BLPRNT_DEV_ROOT', 'src'), '/');

        $default = [
            'app' => $projectRoot . '/app',
            'bootstrap' => $projectRoot . '/bootstrap',
            'routes' => $projectRoot . '/routes',
            'public' => $projectRoot . '/public',
            'config' => $projectRoot . '/config',
        ];

        if (!$devSource) {
            return $default;
        }

        $resolved = [];
        foreach ($default as $name => $fallbackPath) {
            $candidate = $projectRoot . '/' . $devRoot . '/' . $name;
            $resolved[$name] = is_dir($candidate) ? $candidate : $fallbackPath;
        }

        return $resolved;
    }

    private function resolveRouter(): ?Router
    {
        try {
            if (function_exists('router')) {
                $router = router();
                if ($router instanceof Router) {
                    return $router;
                }
            }
        } catch (\Throwable) {
        }

        $app = App::getInstance();
        if ($app->has('router')) {
            try {
                $router = $app->make('router');
                if ($router instanceof Router) {
                    return $router;
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /**
     * @return array<string, int>
     */
    private function routeStats(Router $router): array
    {
        $routes = $router->getRoutes();
        $total = 0;

        foreach ($routes as $method => $byUri) {
            if (!is_array($byUri)) {
                continue;
            }

            $total += count($byUri);
        }

        $stats = [
            'total routes' => $total,
            'methods' => count($routes),
        ];

        ksort($routes);
        foreach ($routes as $method => $byUri) {
            $stats['method ' . strtoupper((string) $method)] = is_array($byUri) ? count($byUri) : 0;
        }

        return $stats;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function printSection(string $title, array $rows): void
    {
        echo $title . "\n";
        echo str_repeat('-', strlen($title)) . "\n";
        echo $this->renderTable($rows);
        echo "\n";
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function renderTable(array $rows): string
    {
        if (empty($rows)) {
            return "(empty)\n";
        }

        $columnCount = 0;
        foreach ($rows as $row) {
            $columnCount = max($columnCount, count($row));
        }

        $widths = array_fill(0, $columnCount, 0);

        foreach ($rows as $row) {
            for ($i = 0; $i < $columnCount; $i++) {
                $cell = isset($row[$i]) ? (string) $row[$i] : '';
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        $separator = '+';
        foreach ($widths as $width) {
            $separator .= str_repeat('-', $width + 2) . '+';
        }

        $out = $separator . "\n";

        foreach ($rows as $index => $row) {
            $line = '|';
            for ($i = 0; $i < $columnCount; $i++) {
                $cell = isset($row[$i]) ? (string) $row[$i] : '';
                $line .= ' ' . str_pad($cell, $widths[$i]) . ' |';
            }
            $out .= $line . "\n";

            if ($index === 0) {
                $out .= $separator . "\n";
            }
        }

        $out .= $separator . "\n";

        return $out;
    }

    /**
     * @return string[]
     */
    private function baseEnvKeys(): array
    {
        return [
            'APP_ENV',
            'APP_DEBUG',
            'APP_URL',
            'SERVE_HOST',
            'SERVE_PORT',
            'SERVE_WEBROOT',
            'BLPRNT_DEV_SOURCE',
            'BLPRNT_DEV_ROOT',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'API_KEY',
            'API_TOKEN',
        ];
    }

    private function loadApplication(string $projectRoot): void
    {
        $bootstrapFile = $projectRoot . '/bootstrap/app.php';

        if (!is_file($bootstrapFile)) {
            return;
        }

        require_once $bootstrapFile;
    }

    private function env(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return (string) $value;
        }

        return $default;
    }

    private function truthy(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function maskIfSensitive(string $key, ?string $value): string
    {
        $value ??= '';

        if ($value === '') {
            return '';
        }

        $upper = strtoupper($key);
        if (
            str_contains($upper, 'PASSWORD')
            || str_contains($upper, 'TOKEN')
            || str_contains($upper, 'KEY')
            || str_contains($upper, 'SECRET')
        ) {
            return str_repeat('*', min(12, max(4, strlen($value))));
        }

        return $value;
    }

    private function printHelp(): void
    {
        echo "\n";
        echo "Description:\n";
        echo "  Display a health snapshot for commands, services, routes, directories, and env values.\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php blprnt health:check [options]\n";
        echo "\n";
        echo "Options:\n";
        echo "  -h, --help      Show this help message\n";
        echo "      --no-load   Skip loading bootstrap/app.php\n";
        echo "      --full-env  Include all available env/server variables\n";
        echo "\n";
        echo "Examples:\n";
        echo "  php blprnt health:check\n";
        echo "  php blprnt health:check --no-load\n";
        echo "  php blprnt health:check --full-env\n";
        echo "\n";
    }
}
