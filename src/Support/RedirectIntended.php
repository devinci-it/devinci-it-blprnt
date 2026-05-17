<?php

namespace DevinciIT\Blprnt\Support;

use DevinciIT\Blprnt\Support\Session;

class RedirectIntended
{
    public static function set(string $url): void
    {
        Session::set('intended_url', $url);
    }

    public static function get(): ?string
    {
        return Session::get('intended_url');
    }

    public static function forget(): void
    {
        Session::set('intended_url', null);
    }
}
