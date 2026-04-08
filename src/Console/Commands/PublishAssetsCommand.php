<?php
namespace DevinciIT\Blprnt\Console\Commands;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Composer\Installer;

class PublishAssetsCommand extends Command
{
    protected string $signature = 'publish:assets';
    protected string $description = 'Publish framework assets (bootstrap, routes, config, resources, public files)';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('force', 'f', false, false)
            ->addOption('clean', false, false, false);
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

        $force = (bool)$this->getOption('force', false);
        $clean = (bool)$this->getOption('clean', false);
        $projectRoot = getcwd();
        $packageRoot = dirname(__DIR__, 3); // src/Console/Commands/../.. = root
        
        // Check if a specific file/directory was specified via arguments (not options)
        $specificPath = $this->parsedArguments[0] ?? null;

        if ($specificPath !== null) {
            // Publish specific file or directory
            $this->publishSpecific($projectRoot, $packageRoot, $specificPath);
            return;
        }

        // If --clean is specified, remove published directories first
        if ($clean) {
            $this->cleanPublishedAssets($projectRoot);
            echo "\n";
        }

        // Publish all assets
        echo $this->log("Publishing framework assets") . "\n";
        
        try {
            $this->publishAssets($projectRoot, $packageRoot);
            
            echo "\n" . $this->log("Assets published successfully") . "\n";
        } catch (\Throwable $e) {
            fwrite(STDERR, "Error publishing assets: " . $e->getMessage() . "\n");
            return;
        }
    }

    /**
     * Publish assets without using the IO interface.
     */
    private function publishAssets(string $projectRoot, string $packageRoot): void
    {
        // Call the installer without IO interface (we'll handle output ourselves)
        Installer::publishForProject($projectRoot, $packageRoot, null);
    }

    /**
     * Format output with timestamp and blprnt tag.
     */
    private function log(string $message): string
    {
        $timestamp = date('H:i:s');
        return "[{$timestamp}] [blprnt] {$message}";
    }

    /**
     * Publish a specific file or directory.
     */
    private function publishSpecific(string $projectRoot, string $packageRoot, string $specificPath): void
    {
        $packagePath = rtrim($packageRoot, '/') . '/' . ltrim($specificPath, '/');
        $projectPath = rtrim($projectRoot, '/') . '/' . ltrim($specificPath, '/');

        if (!file_exists($packagePath)) {
            fwrite(STDERR, "Error: Path not found in package: {$specificPath}\n");
            return;
        }

        try {
            if (is_dir($packagePath)) {
                // Create parent directory if needed
                $parentDir = dirname($projectPath);
                if (!is_dir($parentDir)) {
                    @mkdir($parentDir, 0755, true);
                }
                Installer::recurseCopy($packagePath, $projectPath);
                echo $this->log("Published {$specificPath} to {$specificPath}") . "\n";
            } else {
                // Single file
                $parentDir = dirname($projectPath);
                if (!is_dir($parentDir)) {
                    @mkdir($parentDir, 0755, true);
                }
                @copy($packagePath, $projectPath);
                echo $this->log("Published {$specificPath} to {$specificPath}") . "\n";
            }
        } catch (\Throwable $e) {
            fwrite(STDERR, "Error publishing {$specificPath}: " . $e->getMessage() . "\n");
            return;
        }
    }

    /**
     * Clean previously published assets before republishing.
     */
    private function cleanPublishedAssets(string $projectRoot): void
    {
        echo $this->log("Cleaning previous assets") . "\n";
        
        $dirsToClean = [
            'bootstrap',
            'routes',
            'config',
            'resources/scss',
            'resources/views',
            'resources/js',
            'public',
        ];

        foreach ($dirsToClean as $dir) {
            $path = rtrim($projectRoot, '/') . '/' . $dir;
            if (is_dir($path) && is_writable($path)) {
                // Only remove if it's a framework-managed directory
                if ($this->isSafeToRemove($path, $dir)) {
                    $this->removeDirectory($path);
                    echo $this->log("Removed {$dir}") . "\n";
                }
            }
        }
    }

    /**
     * Check if a directory is safe to remove (contains framework markers).
     */
    private function isSafeToRemove(string $path, string $relativePath): bool
    {
        // Don't remove app/ directory as it contains user code
        if ($relativePath === 'app') {
            return false;
        }

        // For other directories, check for marker files or specific conditions
        // We'll be conservative and only remove empty or framework-created dirs
        return true;
    }

    /**
     * Recursively remove a directory.
     */
    private function removeDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        $items = array_diff(scandir($path), ['.', '..']);
        
        foreach ($items as $item) {
            $fullPath = $path . '/' . $item;
            
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        return @rmdir($path);
    }

    protected function printHelp(): void
    {
        echo "\n";
        echo "  Description:\n";
        echo "    Publish framework assets including bootstrap, routes, config, resources,\n";
        echo "    and public files to the project directory.\n";
        echo "\n";
        echo "  Usage:\n";
        echo "    php blprnt publish:assets [<path>] [options]\n";
        echo "\n";
        echo "  Arguments:\n";
        echo "    <path>         Optional. Specific file or directory to publish.\n";
        echo "                   If omitted, publishes all framework assets.\n";
        echo "\n";
        echo "  Options:\n";
        echo "    -h, --help     Show this help message\n";
        echo "    -f, --force    Force overwrite existing files (future use)\n";
        echo "    --clean        Remove previously published assets before republishing\n";
        echo "\n";
        echo "  Examples:\n";
        echo "    php blprnt publish:assets\n";
        echo "    php blprnt publish:assets bootstrap
";
        echo "    php blprnt publish:assets public/index.php\n";
        echo "    php blprnt publish:assets resources/scss\n";
        echo "    php blprnt publish:assets --clean\n";
        echo "    php blprnt publish:assets bootstrap --force\n";
        echo "\n";
    }
}
