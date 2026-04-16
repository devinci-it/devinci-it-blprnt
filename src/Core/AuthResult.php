<?php

namespace DevinciIT\Blprnt\Core;

class AuthResult
{
    public function __construct(
        public bool $success,
        public ?array $user = null,
        public ?string $error = null
    ) {}
}
