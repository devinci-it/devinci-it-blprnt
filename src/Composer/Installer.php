<?php
namespace DevinciIT\Blprnt\Composer;

use DevinciIT\Blprnt\Console\Commands\Styles\BuildStylesCommand;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Throwable;

/**
 * Handles project scaffolding publication during Composer install/update flows.
 *
 * Publish configuration is centralized in getPublishConfig() for easy maintenance.
 */
class Installer
{
/**
 * Get the centralized publish configuration.
 *
 * Define all files and directories to publish here:
 * - 'directories': Copy entire directory trees from package to project
 * - 'files': Copy individual files from package to project
 * - 'resources': Map resource sources to project destinations (with recursion)
 * - 'create': Create empty directories in project if they don't exist
 *
 * GUIDELINES FOR ADDING PUBLISHING RULES:
 * ========================================
 *
 * 1. To publish a directory tree (non-destructive):
 *    Add to 'directories': 'src/my-dir' => 'my-dir'
 *    Only copies if destination doesn't exist.
 *
 * 2. To publish a single file:
 *    Add to 'files': 'my-file.example' => 'my-file.example'
 *    Only copies if destination doesn't exist.
 *
 * 3. To publish a resource with recursion:
 *    Add to 'resources': 'resources/my-resource' => 'resources/my-resource'
 *    Recursively copies entire directory.
 *
 * 4. To create empty scaffold directory:
 *    Add to 'create': 'my/empty/dir'
 *    Created if doesn't exist (useful for user-created content).
 *
 * 5. To publish public/web-accessible files:
 *    Add to 'public': 'resources/my-asset.svg' => 'public/my-asset.svg'
 *    Copies single files to public directory.
 *
 * EXAMPLE:
 * --------
 * To add a new config file 'webpack.config.js':
 *     'files' => [
 *         '.env' => '.env',
 *         'blprnt' => 'blprnt',
 *         'webpack.config.js' => 'webpack.config.js',  // ← Add here
 *     ],
 *
 * To add an assets directory 'resources/fonts':
 *     'resources' => [
 *         'resources/scss' => 'resources/scss',
 *         'resources/fonts' => 'resources/fonts',      // ← Add here
 *     ],
 *
 * @return array Publish configuration mapping
 */
    private static function getPublishConfig(): array
    {
        return [
            // Core skeleton directories (root-level)
            'directories' => [
                'bootstrap' => 'bootstrap',
                'routes' => 'routes',
                'config' => 'config',
            ],
            // Core skeleton files (root-level)
            'files' => [
                '.env' => '.env',
                'blprnt' => 'blprnt',
            ],
            // Resource mappings (recursive copy)
            'resources' => [
                'resources/scss' => 'resources/scss',
            ],
            // Directories to create if missing (empty scaffolding)
            'create' => [
                'resources/views',
                'resources/js',
            ],
            // Public entry point (special handling)
            'public' => [
                'resources/img/graphics.svg' => 'public/graphics.svg',
                'public/index.php' => 'public/index.php',
                'resources/logo.svg' => 'public/logo.svg',
                'resources/favicon.svg' => 'public/favicon.svg',
            ],
        ];
    }

    /**
     * Composer post-install hook.
     */
    public static function postInstall(Event $event)
    {
        self::publishForProject(getcwd(), self::packageRootFrom(__DIR__), $event->getIO());
    }

    /**
     * Composer post-update hook.
     */
    public static function postUpdate(Event $event)
    {
        self::publishForProject(getcwd(), self::packageRootFrom(__DIR__), $event->getIO());
    }

    /**
     * Publishes project skeleton, resources, and compiled CSS outputs.
     */
    public static function publishForProject(string $projectRoot, string $packageRoot, ?IOInterface $io = null): void
    {
        if ($io !== null) {
            $io->write('');
            $io ->write('
    ▄▄▄▄  ▄▄    ▄▄▄▄  ▄▄▄▄  ▄▄  ▄▄ ▄▄▄▄▄▄ 
    ██▄██ ██    ██▄█▀ ██▄█▄ ███▄██   ██   
    ██▄█▀ ██▄▄▄ ██    ██ ██ ██ ▀██   ██   ');
            $io->write('<info>  ════════════════════════════════════════</info>');
            $io->write('<info>      Blprnt Framework - Publishing Assets</info>');
            $io->write('<info>  ════════════════════════════════════════</info>');
            $io->write('');
        }

        $config = self::getPublishConfig();

        // Publish core directories
        foreach ($config['directories'] as $dirName => $destination) {
            $src = rtrim($packageRoot, '/') . '/' . $dirName;
            $dest = rtrim($projectRoot, '/') . '/' . $destination;

            if (file_exists($src) && !file_exists($dest)) {
                self::recurseCopy($src, $dest);
                if ($io !== null) {
                    $io->write(sprintf('  <info>[blprnt]</info> Published %s', $destination));
                }
            }
        }

        // Publish core files
        foreach ($config['files'] as $fileName => $destination) {
            $src = rtrim($packageRoot, '/') . '/' . $fileName;
            $dest = rtrim($projectRoot, '/') . '/' . $destination;

            if (is_file($src) && !file_exists($dest)) {
                @copy($src, $dest);
                if ($io !== null) {
                    $io->write(sprintf('  <info>[blprnt]</info> Published %s', $destination));
                }
            }
        }

        // Publish resource directories
        foreach ($config['resources'] as $source => $destination) {
            self::publishResourceDirectory($projectRoot, $packageRoot, $source, $destination, $io);
        }

        // Create empty scaffold directories
        foreach ($config['create'] as $dir) {
            self::ensureDirectoryExists($projectRoot, $dir, $io);
        }

        // Publish app skeleton (non-destructive)
        self::publishAppFromSkeleton($projectRoot, $packageRoot, $io);

        // Publish public files
        foreach ($config['public'] as $source => $destination) {
            self::publishPublicFile($projectRoot, $packageRoot, $source, $destination, $io);
        }

        // Build styles
        self::buildStyles($projectRoot, $io);

        if ($io !== null) {
            $io->write('');
            $io->write('<info>  ════════════════════════════════════════</info>');
            $io->write('<info>      Publishing Complete!</info>');
            $io->write('<info>  ════════════════════════════════════════</info>');
            $io->write('');
        }
    }

    /**
     * Resolves the package root from a nested source directory.
     */
    public static function packageRootFrom(string $directory): string
    {
        return dirname($directory, 2);
    }

    /**
     * Recursively copies a directory tree.
     */
    protected static function recurseCopy(string $src, string $dst): void
    {
        $dir = opendir($src);

        if ($dir === false) {
            return;
        }

        @mkdir($dst, 0755, true);

        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    self::recurseCopy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);
    }

    /**
     * Publishes missing app files from resources/skel/app without replacing existing files.
     */
    private static function publishAppFromSkeleton(string $projectRoot, string $packageRoot, ?IOInterface $io = null): void
    {
        $srcRoot = rtrim($packageRoot, '/') . '/resources/skel/app';
        $dstRoot = rtrim($projectRoot, '/') . '/app';

        if (!is_dir($srcRoot)) {
            return;
        }

        $publishedFiles = self::copyMissingFilesRecursive($srcRoot, $dstRoot);

        if ($io !== null && $publishedFiles > 0) {
            $io->write(sprintf('  <info>[blprnt]</info> Published %d app skeleton file(s) from resources/skel/app', $publishedFiles));
        }
    }

    /**
     * Recursively copies files only when target files do not exist.
     */
    private static function copyMissingFilesRecursive(string $src, string $dst): int
    {
        $dir = opendir($src);

        if ($dir === false) {
            return 0;
        }

        if (!is_dir($dst)) {
            @mkdir($dst, 0755, true);
        }

        $copied = 0;

        while (false !== ($entry = readdir($dir))) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $sourcePath = $src . '/' . $entry;
            $destPath = $dst . '/' . $entry;

            if (is_dir($sourcePath)) {
                $copied += self::copyMissingFilesRecursive($sourcePath, $destPath);
                continue;
            }

            if (!file_exists($destPath) && @copy($sourcePath, $destPath)) {
                $copied++;
            }
        }

        closedir($dir);

        return $copied;
    }

    /**
     * Publishes resource-backed directories used by the generated project.
     *
     * Note: app/Views is sourced from resources/skel/app/Views/ via publishAppFromSkeleton()
     * to ensure the layout template is the single source of truth.
     */
    private static function publishResourceDirectory(string $projectRoot, string $packageRoot, string $source, string $destination, ?IOInterface $io = null): void
    {
        $src = rtrim($packageRoot, '/') . '/' . $source;
        $dest = rtrim($projectRoot, '/') . '/' . $destination;

        if (is_dir($src) && !file_exists($dest)) {
            self::recurseCopy($src, $dest);
            if ($io !== null) {
                $io->write(sprintf('  <info>[blprnt]</info> Published %s from %s', $destination, $source));
            }
        }
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     */
    private static function ensureDirectoryExists(string $projectRoot, string $dir, ?IOInterface $io = null): void
    {
        $path = rtrim($projectRoot, '/') . '/' . $dir;
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
            if ($io !== null) {
                $io->write(sprintf('  <info>[blprnt]</info> Created %s directory', $dir));
            }
        }
    }

    /**
     * Publish a single public file (config-driven).
     */
    private static function publishPublicFile(string $projectRoot, string $packageRoot, string $source, string $destination, ?IOInterface $io = null): void
    {
        $src = rtrim($packageRoot, '/') . '/' . $source;
        $dest = rtrim($projectRoot, '/') . '/' . $destination;

        if (is_file($src) && !file_exists($dest)) {
            @mkdir(dirname($dest), 0755, true);
            @copy($src, $dest);
            if ($io !== null) {
                $io->write(sprintf('  <info>[blprnt]</info> Published %s from %s', $destination, $source));
            }
        }
    }

    /**
     * Compiles SCSS sources into distributable CSS assets for public serving.
     */
    private static function buildStyles(string $projectRoot, ?IOInterface $io = null): void
    {
        $cwd = getcwd();

        try {
            chdir($projectRoot);

            // Suppress command output
            ob_start();
            $command = new BuildStylesCommand();
            $command->run([
                '--source=resources/scss',
                '--output=public/vendor/devinci-it/blprnt/css',
                '--style=compressed',
                '--force',
            ]);
            ob_end_clean();

            if ($io !== null) {
                $io->write(sprintf('  <info>[blprnt]</info> Built CSS to public/vendor/devinci-it/blprnt/css from resources/scss'));
            }
        } catch (Throwable $e) {
            if ($io !== null) {
                $io->write(sprintf('  <comment>[blprnt]</comment> CSS build failed: %s', $e->getMessage()));
            }
        } finally {
            if (is_string($cwd) && $cwd !== '') {
                chdir($cwd);
            }
        }
    }
}
