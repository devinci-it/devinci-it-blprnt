<?php

namespace DevinciIT\Blprnt\Auth;

use DevinciIT\Blprnt\Support\Session;

class AuthSession
{
    public function login(array $user): void
    {
        $data = [
            'logged_in' => true,
            'user' => $user,
            'auth_time' => time(),
        ];
        Session::set('auth', $data);
    }

    public function logout(): void
    {
        Session::destroy();
    }

    public function user(): ?array
    {
        $auth = Session::get('auth');
        return $auth['user'] ?? null;
    }

    public function check(): bool
    {
        $auth = Session::get('auth');
        return isset($auth['logged_in']) && $auth['logged_in'] === true;
    }

    public function set(string $key, $value): void
    {
        $auth = Session::get('auth') ?: [];
        $auth[$key] = $value;
        Session::set('auth', $auth);
    }

    public function get(string $key)
    {
        $auth = Session::get('auth') ?: [];
        return $auth[$key] ?? null;
    }

    public function start(): void
    {
        Session::start();
    }

    public function regenerate(): void
    {
        Session::regenerate();
    }
}
