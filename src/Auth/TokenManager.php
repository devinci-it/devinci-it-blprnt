<?php

namespace DevinciIT\Blprnt\Auth;

use DevinciIT\Blprnt\Support\Token;

class TokenManager
{
    public function issue(array $user): string
    {
        $token = Token::generate();
        $hashed = Token::hash($token);
        $ttl = 3600; // 1 hour default
        $store = new TokenStore();
        $store->store($hashed, $user, $ttl);
        return $token;
    }

    public function validate(?string $token): bool
    {
        if (!$token) {
            return false;
        }
        $hashed = Token::hash($token);
        $store = new TokenStore();
        return (bool)$store->validate($hashed);
    }
}
