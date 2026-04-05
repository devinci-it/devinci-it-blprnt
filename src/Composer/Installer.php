<?php
namespace DevinciIT\Blprnt\Composer;

use DevinciIT\Blprnt\Console\Commands\Styles\BuildStylesCommand;
use Composer\IO\IOInterface;
use Composer\Script\Event;
use Throwable;

/**
 * Handles project scaffolding publication during Composer install/update flows.
 */
class Installer
{
    private const SKELETON_DIRS = ['app', 'bootstrap', 'routes', 'config'];
    private const SKELETON_FILES = ['.env', 'blprnt'];

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
        foreach (self::SKELETON_DIRS as $dir) {
            $src = rtrim($packageRoot, '/') . '/' . $dir;
            $dest = rtrim($projectRoot, '/') . '/' . $dir;

            if (file_exists($src) && !file_exists($dest)) {
                self::recurseCopy($src, $dest);

                if ($io !== null) {
                    $io->write(sprintf('<info>[blprnt]</info> Published %s', $dir));
                }
            }
        }

        foreach (self::SKELETON_FILES as $file) {
            $src = rtrim($packageRoot, '/') . '/' . $file;
            $dest = rtrim($projectRoot, '/') . '/' . $file;

            if (is_file($src) && !file_exists($dest)) {
                @copy($src, $dest);

                if ($io !== null) {
                    $io->write(sprintf('<info>[blprnt]</info> Published %s', $file));
                }
            }
        }

        self::publishResourceDirectories($projectRoot, $packageRoot, $io);
        self::publishResourceFiles($projectRoot, $packageRoot, $io);
        self::publishPublicEntryPoint($projectRoot, $packageRoot, $io);
        self::buildStyles($projectRoot, $io);
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
     * Publishes resource-backed directories used by the generated project.
     */
    private static function publishResourceDirectories(string $projectRoot, string $packageRoot, ?IOInterface $io = null): void
    {
        $resourceMap = [
            'resources/views' => 'app/Views',
            'resources/scss' => 'resources/scss',
        ];

        foreach ($resourceMap as $source => $destination) {
            $src = rtrim($packageRoot, '/') . '/' . $source;
            $dest = rtrim($projectRoot, '/') . '/' . $destination;

            if (!is_dir($src) || file_exists($dest)) {
                continue;
            }

            self::recurseCopy($src, $dest);

            if ($io !== null) {
                $io->write(sprintf('<info>[blprnt]</info> Published %s from %s', $destination, $source));
            }
        }
    }

    /**
     * Publishes static resource files into the public web root.
     */
    private static function publishResourceFiles(string $projectRoot, string $packageRoot, ?IOInterface $io = null): void
    {
        $resourceFileMap = [
            'resources/logo.svg' => 'public/logo.svg',
            'resources/favicon.svg' => 'public/favicon.svg',
        ];

        foreach ($resourceFileMap as $source => $destination) {
            $src = rtrim($packageRoot, '/') . '/' . $source;
            $dest = rtrim($projectRoot, '/') . '/' . $destination;

            if (!is_file($src) || file_exists($dest)) {
                continue;
            }

            @mkdir(dirname($dest), 0755, true);
            @copy($src, $dest);

            if ($io !== null) {
                $io->write(sprintf('<info>[blprnt]</info> Published %s from %s', $destination, $source));
            }
        }
    }

    /**
     * Publishes the public front controller when absent.
     */
    private static function publishPublicEntryPoint(string $projectRoot, string $packageRoot, ?IOInterface $io = null): void
    {
        $src = rtrim($packageRoot, '/') . '/public/index.php';
        $dest = rtrim($projectRoot, '/') . '/public/index.php';

        if (!is_file($src) || file_exists($dest)) {
            return;
        }

        @mkdir(dirname($dest), 0755, true);
        @copy($src, $dest);

        if ($io !== null) {
            $io->write('<info>[blprnt]</info> Published public/index.php');
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

            $command = new BuildStylesCommand();
            $command->run([
                '--source=resources/scss',
                '--output=public/vendor/devinci-it/blprnt/css',
                '--style=compressed',
                '--force',
            ]);

            if ($io !== null) {
                $io->write('<info>[blprnt]</info> Built CSS from resources/scss');
            }
        } catch (Throwable $e) {
            if ($io !== null) {
                $io->write(sprintf('<comment>[blprnt]</comment> Style build skipped: %s', $e->getMessage()));
            }
        } finally {
            if (is_string($cwd) && $cwd !== '') {
                chdir($cwd);
            }
        }
    }
}
