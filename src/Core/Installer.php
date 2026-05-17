<?php

namespace DevinciIT\Blprnt\Core;

use DevinciIT\Blprnt\Support\Publisher;
use DevinciIT\Blprnt\Support\IOHelper;

abstract class Installer
{
    protected string $projectRoot;
    protected string $packageRoot;

    protected IOHelper $io;
    protected Publisher $publisher;

    public function __construct(
        string $projectRoot,
        string $packageRoot,
        ?IOHelper $io = null
    ) {
        $this->projectRoot = rtrim($projectRoot, '/');
        $this->packageRoot = rtrim($packageRoot, '/');

        $this->io = $io ?? new IOHelper();
        $this->publisher = new Publisher($this->io);
    }

    /*
    |--------------------------------------------------------------------------
    | ENTRY
    |--------------------------------------------------------------------------
    */
    public function runInstall(): void
    {
        $this->before();
        $this->install();
        $this->after();
    }

    public static function publishForProject(string $projectRoot, string $packageRoot, ?IOHelper $io = null, bool $force = false): void
    {
        $installer = new class($projectRoot, $packageRoot, $io) extends Installer {
            protected function install(): void {}
        };

        $output = $installer->io();

        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/bootstrap')),
            $installer->resolveProject($installer->normalizeProjectPath('bootstrap')),
            $force,
            $output
        );
        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/routes')),
            $installer->resolveProject($installer->normalizeProjectPath('routes')),
            $force,
            $output
        );
        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/config')),
            $installer->resolveProject($installer->normalizeProjectPath('config')),
            $force,
            $output
        );

        self::publishFile(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/.env.tmp')),
            $installer->resolveProject($installer->normalizeProjectPath('.env')),
            $force,
            $output
        );
        self::publishFile(
            $installer->resolvePackage($installer->normalizePackagePath('blprnt')),
            $installer->resolveProject($installer->normalizeProjectPath('blprnt')),
            $force,
            $output
        );

        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/views')),
            $installer->resolveProject($installer->normalizeProjectPath('app/Views')),
            $force,
            $output
        );
        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/app/Controllers')),
            $installer->resolveProject($installer->normalizeProjectPath('app/Controllers')),
            $force,
            $output
        );
        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/app/Views/auth')),
            $installer->resolveProject($installer->normalizeProjectPath('app/Views/auth')),
            $force,
            $output
        );
        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/app/Views/errors')),
            $installer->resolveProject($installer->normalizeProjectPath('app/Views/errors')),
            $force,
            $output
        );
        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/app/Views/layouts')),
            $installer->resolveProject($installer->normalizeProjectPath('app/Views/layouts')),
            $force,
            $output
        );

        self::recurseCopy(
            $installer->resolvePackage($installer->normalizePackagePath('resources/skel/public')),
            $installer->resolveProject($installer->normalizeProjectPath('public')),
            $force,
            $output
        );

        self::publishFile(
            $installer->resolvePackage($installer->normalizePackagePath('resources/logo.svg')),
            $installer->resolveProject($installer->normalizeProjectPath('public/logo.svg')),
            $force,
            $output
        );
        self::publishFile(
            $installer->resolvePackage($installer->normalizePackagePath('resources/favicon.svg')),
            $installer->resolveProject($installer->normalizeProjectPath('public/favicon.svg')),
            $force,
            $output
        );
        self::publishFile(
            $installer->resolvePackage($installer->normalizePackagePath('resources/img/graphics.svg')),
            $installer->resolveProject($installer->normalizeProjectPath('public/graphics.svg')),
            $force,
            $output
        );
    }

    public static function publishFile(string $src, string $dst, bool $force = false, ?IOHelper $io = null): void
    {
        $io ??= new IOHelper();

        if (!is_file($src)) {
            $io->error("Missing file: {$src}");
            return;
        }

        if (file_exists($dst) && !$force) {
            $io->warn("Skipped (exists): {$dst}");
            return;
        }

        $exists = file_exists($dst);

        @mkdir(dirname($dst), 0755, true);

        if (!copy($src, $dst)) {
            $io->error("Failed copy: {$dst}");
            return;
        }

        if ($exists) {
            $io->warn("Overwritten: {$dst}");
            return;
        }

        $io->success("Copied: {$dst}");
    }

    public static function recurseCopy(string $src, string $dst, bool $force = false, ?IOHelper $io = null): void
    {
        $io ??= new IOHelper();

        if (!is_dir($src)) {
            $io->error("Missing directory: {$src}");
            return;
        }

        @mkdir($dst, 0755, true);

        foreach (scandir($src) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $from = "{$src}/{$file}";
            $to = "{$dst}/{$file}";

            is_dir($from)
                ? self::recurseCopy($from, $to, $force, $io)
                : self::publishFile($from, $to, $force, $io);
        }
    }

    abstract protected function install(): void;

    /*
    |--------------------------------------------------------------------------
    | PATH NORMALIZATION (🔥 NEW CORE FEATURE)
    |--------------------------------------------------------------------------
    |
    | This ensures all inputs are SAFE + RELATIVE
    |
    */

    /**
     * Normalize package path (source)
     *
     * Accepts:
     * - resources/logo.svg
     * - vendor/devinci-it/blprnt/resources/logo.svg
     * - /resources/logo.svg
     *
     * Always returns:
     * - resources/logo.svg
     */
    protected function normalizePackagePath(string $path): string
    {
        $path = ltrim($path, '/');

        // Strip vendor prefix if accidentally included
        $vendorPrefix = basename($this->packageRoot) . '/';
        if (str_contains($path, $vendorPrefix)) {
            $parts = explode($vendorPrefix, $path, 2);
            $path = $parts[1] ?? $path;
        }

        return ltrim($path, '/');
    }

    /**
     * Normalize project path (destination)
     *
     * Accepts:
     * - public/logo.svg
     * - project/public/logo.svg (if mistakenly passed)
     *
     * Always returns:
     * - public/logo.svg
     */
    protected function normalizeProjectPath(string $path): string
    {
        $path = ltrim($path, '/');

        // Remove accidental project root duplication
        $rootName = basename($this->projectRoot) . '/';
        if (str_contains($path, $rootName)) {
            $parts = explode($rootName, $path, 2);
            $path = $parts[1] ?? $path;
        }

        return ltrim($path, '/');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS (NOW SAFE)
    |--------------------------------------------------------------------------
    */

    protected function file(string $src, string $dest): void
    {
        $this->publisher->file(
            $this->resolvePackage($this->normalizePackagePath($src)),
            $this->resolveProject($this->normalizeProjectPath($dest))
        );
    }

    protected function copy(string $src, string $dest): void
    {
        $this->publisher->copy(
            $this->resolvePackage($this->normalizePackagePath($src)),
            $this->resolveProject($this->normalizeProjectPath($dest))
        );
    }

    protected function dir(string $src, string $dest): void
    {
        $this->publisher->dir(
            $this->resolvePackage($this->normalizePackagePath($src)),
            $this->resolveProject($this->normalizeProjectPath($dest))
        );
    }

    protected function make(string $dir): void
    {
        $this->publisher->make(
            $this->resolveProject($this->normalizeProjectPath($dir))
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PATH RESOLUTION
    |--------------------------------------------------------------------------
    */

    protected function resolvePackage(string $path): string
    {
        return $this->packageRoot . '/' . ltrim($path, '/');
    }

    protected function resolveProject(string $path): string
    {
        return $this->projectRoot . '/' . ltrim($path, '/');
    }

    /*
    |--------------------------------------------------------------------------
    | HOOKS
    |--------------------------------------------------------------------------
    */

    protected function before(): void
    {
        $this->io->info('Starting installation...');
    }

    protected function after(): void
    {
        $this->io->success('Installation complete.');
    }

    public function io(): IOHelper
    {
        return $this->io;
    }
}
