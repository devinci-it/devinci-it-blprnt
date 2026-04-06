<?php
namespace DevinciIT\Blprnt\Console\Commands\Generator;

use DevinciIT\Blprnt\Console\Command;

class MakeViewCommand extends Command
{
    protected string $signature = 'make:view';
    protected string $description = 'Generate a view file from the view stub';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('force', 'f', false, false)
            ->addOption('title', 't', true, false, null)
            ->addOption('message', 'm', true, false, null)
            ->addOption('output', 'o', true, false, null);
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

        $name = trim((string)($args[0] ?? ''));
        if ($name === '') {
            fwrite(STDERR, "Missing view name.\n\n");
            $this->printHelp();
            return;
        }

        $segments = $this->parseViewSegments($name);
        if ($segments === null) {
            fwrite(STDERR, "Invalid view name. Use letters, numbers, '-', '_', and optional nesting via / .\n");
            return;
        }

        $outputRoot = $this->resolvePath('app/Views');
        $customOutput = trim((string)($this->getOption('output') ?? ''));
        if ($customOutput !== '') {
            $outputRoot = $this->resolvePath($customOutput);
        }

        $targetFile = rtrim($outputRoot, '/') . '/' . implode('/', $segments) . '.php';
        $targetDir = dirname($targetFile);
        $force = (bool)$this->getOption('force', false);

        if (is_file($targetFile) && !$force) {
            fwrite(STDERR, "View already exists: {$targetFile}\nUse --force to overwrite.\n");
            return;
        }

        $title = trim((string)($this->getOption('title') ?? ''));
        if ($title === '') {
            $title = ucwords(str_replace(['-', '_'], ' ', basename($targetFile, '.php')));
        }

        $message = trim((string)($this->getOption('message') ?? ''));
        if ($message === '') {
            $message = 'Welcome to ' . $title;
        }

        $stub = $this->loadStub('View.php.tmp');
        if ($stub === null) {
            fwrite(STDERR, "Unable to locate View.php.tmp stub.\n");
            return;
        }

        $contents = str_replace(
            ['{{ title }}', '{{ message }}'],
            [$title, $message],
            $stub
        );

        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            fwrite(STDERR, "Unable to create output directory: {$targetDir}\n");
            return;
        }

        if (file_put_contents($targetFile, $contents) === false) {
            fwrite(STDERR, "Unable to write view file: {$targetFile}\n");
            return;
        }

        echo "Created view: {$targetFile}\n";
    }

    private function parseViewSegments(string $name): ?array
    {
        $normalized = trim(str_replace(['\\', '.'], '/', $name), "/ \t\n\r\0\x0B");
        if ($normalized === '') {
            return null;
        }

        $segments = explode('/', $normalized);
        $cleaned = [];

        foreach ($segments as $segment) {
            $segment = strtolower(trim($segment));
            if ($segment === '' || !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $segment)) {
                return null;
            }
            $cleaned[] = $segment;
        }

        return $cleaned;
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
        echo "  blprnt make:view splash\n";
        echo "  blprnt make:view admin/users/index [--title=Users]\n\n";
        echo "Options:\n";
        echo "  --force         Overwrite existing file\n";
        echo "  --title         Default title in stub\n";
        echo "  --message       Default message in stub\n";
        echo "  --output        Custom output root for views\n";
    }
}
