<?php
namespace DevinciIT\Blprnt\Console\Commands\Generator;

use DevinciIT\Blprnt\Console\Command;

class MakeServiceCommand extends Command
{
    protected string $signature = 'make:service';
    protected string $description = 'Generate a service class from the service stub';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('force', 'f', false, false)
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
            fwrite(STDERR, "Missing service name.\n\n");
            $this->printHelp();
            return;
        }

        $segments = $this->parseNameSegments($name);
        if ($segments === null) {
            fwrite(STDERR, "Invalid service name. Use letters/numbers and optional nesting with / or \\\\.\n");
            return;
        }

        $className = array_pop($segments);
        $relativeNamespace = implode('\\', $segments);
        $className = (string)preg_replace('/Service$/i', '', $className) . 'Service';

        $namespace = 'App\\Services' . ($relativeNamespace !== '' ? '\\' . $relativeNamespace : '');

        $outputDir = $this->resolvePath('app/Services');
        if ($relativeNamespace !== '') {
            $outputDir .= '/' . str_replace('\\', '/', $relativeNamespace);
        }

        $customOutput = trim((string)($this->getOption('output') ?? ''));
        if ($customOutput !== '') {
            $outputDir = $this->resolvePath($customOutput);
        }

        $targetFile = rtrim($outputDir, '/') . '/' . $className . '.php';
        $force = (bool)$this->getOption('force', false);

        if (is_file($targetFile) && !$force) {
            fwrite(STDERR, "Service already exists: {$targetFile}\nUse --force to overwrite.\n");
            return;
        }

        $stub = $this->loadStub('Service.php.tmp');
        if ($stub === null) {
            fwrite(STDERR, "Unable to locate Service.php.tmp stub.\n");
            return;
        }

        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $className],
            $stub
        );

        if (!is_dir($outputDir) && !@mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
            fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
            return;
        }

        if (file_put_contents($targetFile, $contents) === false) {
            fwrite(STDERR, "Unable to write service file: {$targetFile}\n");
            return;
        }

        echo "Created service: {$targetFile}\n";
    }

    private function parseNameSegments(string $name): ?array
    {
        $normalized = trim(str_replace('/', '\\', $name), "\\ \t\n\r\0\x0B");
        if ($normalized === '') {
            return null;
        }

        $segments = explode('\\', $normalized);
        $cleaned = [];

        foreach ($segments as $segment) {
            $segment = $this->toStudlyCase($segment);
            if ($segment === '' || !preg_match('/^[A-Z][A-Za-z0-9]*$/', $segment)) {
                return null;
            }
            $cleaned[] = $segment;
        }

        return $cleaned;
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
        echo "  blprnt make:service User\n";
        echo "  blprnt make:service Admin/ReportBuilder\n\n";
        echo "Options:\n";
        echo "  --force         Overwrite existing file\n";
        echo "  --output        Custom output directory for services\n";
    }
}
