<?php

namespace DevinciIT\Blprnt\Support;

class Publisher
{
    public function __construct(
        protected IOHelper $io
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Core Operations (no config knowledge)
    |--------------------------------------------------------------------------
    */

    public function file(string $src, string $dest): void
    {
        if (!is_file($src)) {
            $this->io->error("Missing file: {$src}");
            return;
        }

        if (file_exists($dest)) {
            $this->io->warn("Skipped (exists): {$dest}");
            return;
        }

        @mkdir(dirname($dest), 0755, true);

        @copy($src, $dest)
            ? $this->io->success("Created file: {$dest}")
            : $this->io->error("Failed file: {$dest}");
    }

    public function copy(string $src, string $dest): void
    {
        if (!is_file($src)) {
            $this->io->error("Missing file: {$src}");
            return;
        }

        $exists = file_exists($dest);

        @mkdir(dirname($dest), 0755, true);

        if (@copy($src, $dest)) {
            $exists
                ? $this->io->warn("Overwritten: {$dest}")
                : $this->io->success("Copied: {$dest}");
        } else {
            $this->io->error("Failed copy: {$dest}");
        }
    }

    public function dir(string $src, string $dest): void
    {
        if (!is_dir($src)) {
            $this->io->error("Missing directory: {$src}");
            return;
        }

        if (file_exists($dest)) {
            $this->io->warn("Skipped directory: {$dest}");
            return;
        }

        $this->recurseCopy($src, $dest);

        $this->io->success("Created directory: {$dest}");
    }

    public function make(string $dir): void
    {
        if (is_dir($dir)) {
            $this->io->warn("Exists: {$dir}");
            return;
        }

        @mkdir($dir, 0755, true)
            ? $this->io->success("Created: {$dir}")
            : $this->io->error("Failed: {$dir}");
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    protected function recurseCopy(string $src, string $dst): void
    {
        @mkdir($dst, 0755, true);

        foreach (scandir($src) as $file) {
            if ($file === '.' || $file === '..') continue;

            $from = "{$src}/{$file}";
            $to = "{$dst}/{$file}";

            is_dir($from)
                ? $this->recurseCopy($from, $to)
                : copy($from, $to);
        }
    }
}