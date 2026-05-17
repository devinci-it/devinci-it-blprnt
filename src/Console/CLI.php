<?php

namespace DevinciIT\Blprnt\Console;

use DevinciIT\Blprnt\Support\IOHelper;

class CLI
{
    private static ?IOHelper $io = null;

    public static function boot(IOHelper $io): void
    {
        self::$io = $io;
    }

    public static function io(): IOHelper
    {
        if (!self::$io) {
            throw new \RuntimeException("CLI context not booted.");
        }

        return self::$io;
    }
}