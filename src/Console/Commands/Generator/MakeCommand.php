<?php
namespace DevinciIT\Blprnt\Console\Commands\Generator;

use DevinciIT\Blprnt\Console\Command;

class MakeCommand extends Command
{
    protected string $signature = 'make:command';
    protected string $description = 'Generate a command class from the command stub';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('handler', null, false, false)
            ->addOption('force', 'f', false, false)
            ->addOption('signature', 's', true, false, null)
            ->addOption('description', 'd', true, false, null)
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
            fwrite(STDERR, "Missing command name.\n\n");
            $this->printHelp();
            return;
        }

        $segments = $this->parseNameSegments($name);
        if ($segments === null) {
            fwrite(STDERR, "Invalid command name. Provide a command name (supports nesting with / or \\).\n");
            return;
        }

        $className = array_pop($segments);
        $relativeNamespace = implode('\\', $segments);

        $className = (string) preg_replace('/Command$/i', '', $className);
        $className .= 'Command';

        $baseNamespace = 'DevinciIT\\Blprnt\\Console\\Commands';
        $fullNamespace = $relativeNamespace !== '' ? $baseNamespace . '\\' . $relativeNamespace : $baseNamespace;

        $outputDir = $this->resolveCommandOutputDir($relativeNamespace);
        $customOutput = (string)($this->getOption('output') ?? '');
        if ($customOutput !== '') {
            $outputDir = $this->resolvePath($customOutput);
        }

        $commandFile = rtrim($outputDir, '/') . '/' . $className . '.php';
        $force = (bool)$this->getOption('force', false);

        if (is_file($commandFile) && !$force) {
            fwrite(STDERR, "Command already exists: {$commandFile}\nUse --force to overwrite.\n");
            return;
        }

        $handlerEnabled = (bool)$this->getOption('handler', false);
        $handlerClassName = $this->deriveHandlerClassName($className);
        $handlerNamespace = $this->buildHandlerNamespace($relativeNamespace);
        $handlerFqcn = $handlerNamespace . '\\' . $handlerClassName;
        $handlerLiteral = $handlerEnabled ? '\\\\' . $handlerFqcn . '::class' : 'null';
        $handlerOutputDir = $this->resolveHandlerOutputDir($relativeNamespace);
        $handlerFile = rtrim($handlerOutputDir, '/') . '/' . $handlerClassName . '.php';

        if ($handlerEnabled && is_file($handlerFile) && !$force) {
            fwrite(STDERR, "Handler already exists: {$handlerFile}\nUse --force to overwrite.\n");
            return;
        }

        $signature = trim((string)($this->getOption('signature') ?? ''));
        if ($signature === '') {
            $signature = $this->deriveSignatureFromClass($className);
        }

        $description = trim((string)($this->getOption('description') ?? ''));
        if ($description === '') {
            $description = 'Run the ' . $className . ' command';
        }

        $commandStub = $this->loadStub('Command.php.tmp');
        if ($commandStub === null) {
            fwrite(STDERR, "Unable to locate Command.php.tmp stub.\n");
            return;
        }

        $commandContents = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ signature }}', '{{ description }}', '{{ handler_class }}', '{{ output }}'],
            [$fullNamespace, $className, $signature, $description, $handlerLiteral, $className . ' executed.'],
            $commandStub
        );

        if (!is_dir($outputDir) && !@mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
            fwrite(STDERR, "Unable to create output directory: {$outputDir}\n");
            return;
        }

        if (file_put_contents($commandFile, $commandContents) === false) {
            fwrite(STDERR, "Unable to write command file: {$commandFile}\n");
            return;
        }

        echo "Created command: {$commandFile}\n";

        if ($handlerEnabled) {
            $handlerStub = $this->loadStub('Handler.php.tmp');
            if ($handlerStub === null) {
                fwrite(STDERR, "Unable to locate Handler.php.tmp stub.\n");
                return;
            }

            $handlerContents = str_replace(
                ['{{ namespace }}', '{{ class }}', '{{ output }}'],
                [$handlerNamespace, $handlerClassName, $handlerClassName . ' handled command.'],
                $handlerStub
            );

            if (!is_dir($handlerOutputDir) && !@mkdir($handlerOutputDir, 0755, true) && !is_dir($handlerOutputDir)) {
                fwrite(STDERR, "Unable to create handler output directory: {$handlerOutputDir}\n");
                return;
            }

            if (file_put_contents($handlerFile, $handlerContents) === false) {
                fwrite(STDERR, "Unable to write handler file: {$handlerFile}\n");
                return;
            }

            echo "Created handler: {$handlerFile}\n";
        }
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
            if ($segment === '') {
                return null;
            }

            if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $segment)) {
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

    private function deriveSignatureFromClass(string $className): string
    {
        $base = preg_replace('/Command$/', '', $className) ?? $className;
        $kebab = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '-$0', $base));

        if (strpos($kebab, '-') === false) {
            return $kebab;
        }

        $parts = explode('-', $kebab);
        $first = array_shift($parts);

        return $first . ':' . implode('-', $parts);
    }

    private function deriveHandlerClassName(string $commandClassName): string
    {
        $base = preg_replace('/Command$/', '', $commandClassName) ?? $commandClassName;

        return $base . 'Handler';
    }

    private function buildHandlerNamespace(string $relativeNamespace): string
    {
        $base = 'DevinciIT\\Blprnt\\Console\\Handlers';

        return $relativeNamespace !== '' ? $base . '\\' . $relativeNamespace : $base;
    }

    private function resolveCommandOutputDir(string $relativeNamespace): string
    {
        $base = $this->resolvePath('src/Console/Commands');

        return $relativeNamespace !== ''
            ? $base . '/' . str_replace('\\', '/', $relativeNamespace)
            : $base;
    }

    private function resolveHandlerOutputDir(string $relativeNamespace): string
    {
        $base = $this->resolvePath('src/Console/Handlers');

        return $relativeNamespace !== ''
            ? $base . '/' . str_replace('\\', '/', $relativeNamespace)
            : $base;
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
        echo "  blprnt make:command Name\n";
        echo "  blprnt make:command Admin/UserReport\n\n";
        echo "Options:\n";
        echo "  --handler       Also generate a matching command handler\n";
        echo "  --force         Overwrite existing files\n";
        echo "  --signature     Explicit command signature (default inferred from class name)\n";
        echo "  --description   Explicit command description\n";
        echo "  --output        Custom output directory for command class\n";
    }
}
