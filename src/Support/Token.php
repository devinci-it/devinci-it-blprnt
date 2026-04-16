<?php

namespace DevinciIT\Blprnt\Support;

class Token
{
    public static function generate(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
