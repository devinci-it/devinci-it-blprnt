<?php

use DevinciIT\Blprnt\Console\CLI;

if (!function_exists('success')) {
    function success(string $message): void
    {
        CLI::io()->success($message);
    }
}

if (!function_exists('error')) {
    function error(string $message): void
    {
        CLI::io()->error($message);
    }
}

if (!function_exists('warn')) {
    function warn(string $message): void
    {
        CLI::io()->warn($message);
    }
}

if (!function_exists('info')) {
    function info(string $message): void
    {
        CLI::io()->info($message);
    }
}

if (!function_exists('accent')) {
    function accent(string $message): void
    {
        CLI::io()->accent($message);
    }
}

if (!function_exists('banner')) {
    function banner(string $message): void
    {
        CLI::io()->header(strtoupper($message));
        CLI::io()->hr();
    }
}

if (!function_exists('newLine')) {
    function newLine(): void
    {
        CLI::io()->newLine();
    }
}

if (!function_exists('hr')) {
    function hr(): void
    {
        CLI::io()->hr();
    }
}

if (!function_exists('kv')) {
    function kv(string $key, string|int|float|bool|null $value): void
    {
        $value = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        CLI::io()->write("  <info>{$key}:</info> {$value}");
    }
}