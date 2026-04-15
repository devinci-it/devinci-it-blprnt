<?php

namespace DevinciIT\Blprnt\Support;

use Composer\IO\IOInterface;

class IOHelper
{
    public function __construct(
        private ?IOInterface $io = null
    ) {}

    /**
     * Core writer
     */
    public function write(string $message): void
    {
        $this->io?->write($message);
    }

    /**
     * Generic styled output
     */
    public function line(string $message, string $type = 'info'): void
    {
        $prefix = match ($type) {
            'success' => '<info>[✓]</info>',
            'warn'    => '<comment>[!]</comment>',
            'error'   => '<error>[✗]</error>',
            default   => '<info>[•]</info>',
        };

        $this->write("  {$prefix} {$message}");
    }

        /**
     * Returns the underlying IOInterface instance (if any)
     */
    public function getIo(): ?IOInterface
    {
        return $this->io;
    }

    /**
     * Convenience shortcuts
     */
    public function success(string $message): void
    {
        $this->line($message, 'success');
    }

    public function warn(string $message): void
    {
        $this->line($message, 'warn');
    }

    public function error(string $message): void
    {
        $this->line($message, 'error');
    }

    public function info(string $message): void
    {
        $this->line($message, 'info');
    }
}