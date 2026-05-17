<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Support;

use Composer\IO\IOInterface;

class IOHelper
{
    public function __construct(
        private ?IOInterface $io = null,
        private string $accentColor = 'cyan'
    ) {}

    /**
     * Write a message to output.
     */
    public function write(string $message): void
    {
        if ($this->io instanceof IOInterface) {
            $this->io->write($message);
            return;
        }

        fwrite(STDOUT, $message . PHP_EOL);
    }

    /**
     * Styled line output.
     */
    public function line(string $message, string $type = 'info'): void
    {
        $prefix = match ($type) {
            'success' => '[✓]',
            'warn'    => '[!]',
            'error'   => '[✗]',
            default   => '[•]',
        };

        $this->write("  {$prefix} {$message}");
    }

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

    /**
     * Blank line
     */
    public function newLine(): void
    {
        $this->write('');
    }

    /**
     * Horizontal rule
     */
    public function hr(): void
    {
        $width = $this->getTerminalWidth();
        $this->write(str_repeat('━', $width));
    }

    /**
     * Accent header
     */
    public function accent(string $message): void
    {
        $this->newLine();
        $this->write(strtoupper($message));
        $this->newLine();
    }

    /**
     * Terminal width detection (safe)
     */
    private function getTerminalWidth(): int
    {
        // Unix-like systems
        if (DIRECTORY_SEPARATOR !== '\\') {
            $cols = shell_exec('tput cols 2>/dev/null');
            if ($cols) {
                return (int) trim($cols);
            }
        }

        // Windows fallback
        if (DIRECTORY_SEPARATOR === '\\') {
            $output = shell_exec('mode con');
            if (preg_match('/Columns:\s+(\d+)/i', (string) $output, $m)) {
                return (int) $m[1];
            }
        }

        return 80; // safe fallback
    }

    /**
     * Access underlying IO (optional)
     */
    public function getIo(): ?IOInterface
    {
        return $this->io;
    }

    public function setAccentColor(string $color): self
    {
        $this->accentColor = $color;
        return $this;
    }

    public function getAccentColor(): string
    {
        return $this->accentColor;
    }
}