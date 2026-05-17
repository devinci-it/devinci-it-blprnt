<?php

namespace DevinciIT\Blprnt\Support;

use RuntimeException;

class Console
{
    // ─────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────

    public static function line(string $text, string $type = 'info'): void
    {
        $prefix = match ($type) {
            'success' => "\e[32m[✓]\e[0m",
            'warn'    => "\e[33m[!]\e[0m",
            'error'   => "\e[31m[✗]\e[0m",
            default   => "\e[36m[•]\e[0m",
        };

        echo "  {$prefix} " . static::sanitize($text) . PHP_EOL;
    }

    public static function success(string $text): void { static::line($text, 'success'); }
    public static function warn(string $text): void    { static::line($text, 'warn'); }
    public static function error(string $text): void   { static::line($text, 'error'); }
    public static function info(string $text): void    { static::line($text, 'info'); }

    public static function input(string $prompt): string
    {
        echo "  {$prompt}";

        $value = static::readInput();
        static::assertInputSize($value);

        return static::sanitize($value);
    }

    public static function secret(string $prompt): string
    {
        echo "  {$prompt}";

        return static::isWindows()
            ? static::handleWindowsSecret()
            : static::handleUnixSecret();
    }

    // ─────────────────────────────────────────────
    // INTERNAL HELPERS
    // ─────────────────────────────────────────────

    protected static function isWindows(): bool
    {
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }

    protected static function readInput(): string
    {
        $value = fgets(STDIN);

        if ($value === false) {
            throw new RuntimeException("Failed to read input.");
        }

        return trim($value);
    }

    protected static function assertInputSize(string $value): void
    {
        if (strlen($value) > 2048) {
            throw new RuntimeException("Input too large.");
        }
    }

    public static function sanitize(string $text): string
    {
        $clean = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
        return $clean ?? '';
    }

    protected static function handleWindowsSecret(): string
    {
        $value = static::readInput();
        static::assertInputSize($value);

        return static::sanitize($value);
    }

    protected static function handleUnixSecret(): string
    {
        static::disableEcho();

        try {
            $value = static::readInput();
            static::assertInputSize($value);
        } finally {
            static::enableEcho();
            echo PHP_EOL;
        }

        return static::sanitize($value);
    }

    protected static function disableEcho(): void
    {
        @system('stty -echo');
    }

    protected static function enableEcho(): void
    {
        @system('stty echo');
    }
}