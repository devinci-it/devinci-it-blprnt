<?php
namespace DevinciIT\Blprnt\Console;

class CommandRegistry
{
    protected static array $commands = [];
    private const COMMAND_NAMESPACE_PREFIX = 'DevinciIT\\Blprnt\\Console\\Commands\\';

    public static function register(Command $command)
    {
        self::$commands[$command->getSignature()] = $command;
    }

    public static function all(): array
    {
        return self::$commands;
    }

    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::$commands as $signature => $command) {
            $group = self::groupFromClass($command);
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }

            $grouped[$group][$signature] = $command;
        }

        ksort($grouped, SORT_NATURAL | SORT_FLAG_CASE);

        foreach ($grouped as $group => $commands) {
            ksort($commands, SORT_NATURAL | SORT_FLAG_CASE);
            $grouped[$group] = $commands;
        }

        return $grouped;
    }

    public static function get(?string $signature): ?Command
    {
        if ($signature === null || $signature === '') {
            return null;
        }

        return self::$commands[$signature] ?? null;
    }

    private static function groupFromClass(Command $command): string
    {
        $fqcn = get_class($command);

        if (!str_starts_with($fqcn, self::COMMAND_NAMESPACE_PREFIX)) {
            return 'General';
        }

        $relative = substr($fqcn, strlen(self::COMMAND_NAMESPACE_PREFIX));
        if ($relative === false || $relative === '') {
            return 'General';
        }

        $lastSeparator = strrpos($relative, '\\');
        if ($lastSeparator === false) {
            return 'General';
        }

        $namespacePath = substr($relative, 0, $lastSeparator);
        if ($namespacePath === '' || $namespacePath === false) {
            return 'General';
        }

        return str_replace('\\', '/', $namespacePath);
    }
}
