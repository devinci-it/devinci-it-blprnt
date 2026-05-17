<?php
namespace DevinciIT\Blprnt\Console\Commands\Styles;

use DevinciIT\Blprnt\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Throwable;

class BuildStylesCommand extends Command
{
    protected string $signature = 'build:styles';
    protected string $description = 'Compile all SCSS files into public/vendor/devinci-it/blprnt/css';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('source', null, true, true, '')
            ->addOption('output', 'o', true, true, 'public/vendor/devinci-it/blprnt/css')
            ->addOption('style', 's', true, true, 'compressed')
            ->addOption('force', 'f', false, false)
            ->addOption('clean', 'c', false, false);
    }

    public function handle(array $args = [])
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

        $style = strtolower((string) ($this->getOption('style') ?: 'compressed'));
        $force = (bool) $this->getOption('force', false);
        $clean = (bool) $this->getOption('clean', false);

        if (!in_array($style, ['compressed', 'expanded'], true)) {
            fwrite(STDERR, "Invalid style. Use 'compressed' or 'expanded'.\n");
            return;
        }

        $outputRoot = $this->resolvePath((string) ($this->getOption('output') ?: 'public/vendor/devinci-it/blprnt/css'));

        if ($clean && is_dir($outputRoot)) {
            if ($this->isUnsafeDeletePath($outputRoot)) {
                fwrite(STDERR, "Refusing to clean unsafe output path: {$outputRoot}\n");
                return;
            }

            if (!$this->removeDirectoryRecursive($outputRoot)) {
                fwrite(STDERR, "Unable to clean output directory: {$outputRoot}\n");
                return;
            }

            echo "Cleaned output directory: {$outputRoot}\n";
        }

        if (!is_dir($outputRoot)) {
            @mkdir($outputRoot, 0755, true);
        }

        $sourceRoot = $this->resolveSourceRoot((string) ($this->getOption('source') ?: ''));
        if ($sourceRoot === null) {
            fwrite(STDERR, "No SCSS source directory found.\n");
            fwrite(STDERR, "Checked: resources/scss, vendor/devinci-it/resources/scss, vendor/devinci-it/blprnt/resources/scss, resources/sassy-css/scss\n");
            return;
        }

        $scssFiles = $this->discoverScssFiles($sourceRoot);
        if (empty($scssFiles)) {
            fwrite(STDERR, "No SCSS files found under: {$sourceRoot}\n");
            return;
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($scssFiles as $scssFile) {
            $relative = ltrim(substr($scssFile, strlen($sourceRoot)), '/');
            $targetCssRelative = preg_replace('/\\.scss$/', '.css', $relative) ?: $relative . '.css';
            $targetCss = rtrim($outputRoot, '/') . '/' . $targetCssRelative;

            if (!$force && is_file($targetCss)) {
                $skipped++;
                echo "Skipped existing CSS (use --force to overwrite): {$targetCss}\n";
                continue;
            }

            $targetDir = dirname($targetCss);
            if (!is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            $source = file_get_contents($scssFile);
            if ($source === false) {
                $failed++;
                fwrite(STDERR, "Unable to read input file: {$scssFile}\n");
                continue;
            }

            try {
                $compiler = new Compiler();
                $compiler->setOutputStyle(OutputStyle::fromString($style));
                $compiler->setImportPaths([dirname($scssFile), $sourceRoot]);
                $css = $compiler->compileString($source, $scssFile)->getCss();

                if (file_put_contents($targetCss, $css) === false) {
                    $failed++;
                    fwrite(STDERR, "Unable to write CSS file: {$targetCss}\n");
                    continue;
                }

                $success++;
                echo "Compiled SCSS: {$scssFile} -> {$targetCss}\n";
            } catch (Throwable $e) {
                $failed++;
                fwrite(STDERR, "SCSS compile failed ({$scssFile}): " . $e->getMessage() . "\n");
            }
        }

        echo sprintf("Build complete. Success: %d, Skipped: %d, Failed: %d\n", $success, $skipped, $failed);
    }

    private function isUnsafeDeletePath(string $path): bool
    {
        $normalized = rtrim($path, '/');

        return $normalized === '' || $normalized === '/';
    }

    private function removeDirectoryRecursive(string $directory): bool
    {
        if (!is_dir($directory)) {
            return true;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $entry) {
            if ($entry->isDir()) {
                if (!@rmdir($entry->getPathname())) {
                    return false;
                }
            } else {
                if (!@unlink($entry->getPathname())) {
                    return false;
                }
            }
        }

        return @rmdir($directory);
    }

    private function resolveSourceRoot(string $explicitSource): ?string
    {
        if ($explicitSource !== '') {
            $resolved = $this->resolvePath($explicitSource);
            return is_dir($resolved) ? $resolved : null;
        }

        $candidates = [
            'resources/scss',
            'vendor/devinci-it/resources/scss',
            'vendor/devinci-it/blprnt/resources/scss',
            'resources/sassy-css/scss',
        ];

        foreach ($candidates as $candidate) {
            $resolved = $this->resolvePath($candidate);
            if (is_dir($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    private function discoverScssFiles(string $sourceRoot): array
    {
        $files = [];

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceRoot, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $entry) {
            if (!$entry->isFile()) {
                continue;
            }

            $path = $entry->getPathname();
            if (!str_ends_with($path, '.scss')) {
                continue;
            }

            if (str_starts_with(basename($path), '_')) {
                continue;
            }

            $files[] = $path;
        }

        sort($files);

        return $files;
    }

    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return getcwd();
        }

        if (str_starts_with($path, '/')) {
            return $path;
        }

        return getcwd() . '/' . ltrim($path, '/');
    }

    private function printHelp(): void
    {
        echo "Usage:\n";
        echo "  blprnt build:styles [--source=resources/scss] [--output=public/vendor/devinci-it/blprnt/css] [--style=compressed] [--force] [--clean]\n\n";
        echo "Options:\n";
        echo "  --source    SCSS source root (auto-detect by default)\n";
        echo "  --output    CSS output root (default: public/vendor/devinci-it/blprnt/css)\n";
        echo "  --style     Output style: compressed|expanded (default: compressed)\n";
        echo "  --force     Overwrite existing CSS outputs\n";
        echo "  --clean     Remove output directory before compiling\n";
    }
}
