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