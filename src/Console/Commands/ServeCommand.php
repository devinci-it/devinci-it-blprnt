<?php
namespace DevinciIT\Blprnt\Console\Commands;

use DevinciIT\Blprnt\Console\Command;

class ServeCommand extends Command
{
    protected string $signature = 'serve';
    protected string $description = 'Serve the app using PHP built-in server';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('host', null, true, false, null)
            ->addOption('port', null, true, false, null)
            ->addOption('webroot', null, true, false, null);
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

        $defaults = $this->resolveDefaults();

        $host = (string)($this->getOption('host') ?? $defaults['host']);
        $port = (int)($this->getOption('port') ?? $defaults['port']);
        $webroot = (string)($this->getOption('webroot') ?? $defaults['webroot']);

        if ($port < 1 || $port > 65535) {
            fwrite(STDERR, "Invalid port: {$port}. Must be between 1 and 65535.\n");
            return;
        }

        $webrootPath = $this->normalizeWebroot($webroot);
        if (!is_dir($webrootPath)) {
            fwrite(STDERR, "Webroot directory does not exist: {$webrootPath}\n");
            return;
        }

        echo "Starting server at http://{$host}:{$port}\n";
        echo "Document root: {$webrootPath}\n";

        $command = sprintf(
            '%s -S %s -t %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg($host . ':' . (string)$port),
            escapeshellarg($webrootPath)
        );

        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            fwrite(STDERR, "Server exited with code {$exitCode}.\n");
        }
    }

    private function resolveDefaults(): array
    {
        $defaultHost = $this->env('SERVE_HOST');
        $defaultPort = $this->env('SERVE_PORT');
        $defaultWebroot = $this->env('SERVE_WEBROOT');

        return [
            'host' => (string) ($defaultHost ?: '127.0.0.1'),
            'port' => (int) ($defaultPort ?: 8000),
            'webroot' => (string) ($defaultWebroot ?: 'public'),
        ];
    }

    private function env(string $key, $default = null)
    {
        if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return $value;
        }

        return $default;
    }

    private function normalizeWebroot(string $webroot): string
    {
        if ($webroot === '') {
            return getcwd() . '/public';
        }

        if (strpos($webroot, '/') === 0) {
            return rtrim($webroot, '/');
        }

        return rtrim(getcwd() . '/' . ltrim($webroot, '/'), '/');
    }

    private function printHelp(): void
    {
        $host = $this->env('SERVE_HOST') ?: '127.0.0.1';
        $port = $this->env('SERVE_PORT') ?: '8000';
        $webroot = $this->env('SERVE_WEBROOT') ?: 'public';

        echo "Usage:\n";
        echo "  blprnt serve [--host=HOST] [--port=PORT] [--webroot=DIR]\n\n";

        echo "Options:\n";
        echo "  --host       Bind host (default: {$host})\n";
        echo "  --port       Bind port (default: {$port})\n";
        echo "  --webroot    Document root (default: {$webroot})\n";
    }
}
