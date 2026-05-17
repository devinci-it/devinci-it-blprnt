<?php

namespace DevinciIT\Blprnt\Support;

class AuthLogger
{
    public static function log($message, array $context = []): void
    {
        error_log('[AUTH] ' . $message . ($context ? ' ' . json_encode($context) : ''));
    }
    public static function debug($message, array $context = []): void
    {
        if ($_ENV['AUTH_DEBUG'] ?? false) {
            self::log('[DEBUG] ' . $message, $context);
        }
    }
    public static function error($message, array $context = []): void
    {
        self::log('[ERROR] ' . $message, $context);
    }
}
