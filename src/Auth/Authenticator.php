<?php

namespace DevinciIT\Blprnt\Auth;

use DevinciIT\Blprnt\Auth\Strategy\ShadowAuthStrategy;

class Authenticator
{
    /**
     * Handle attempt logic and choose strategy (callback vs shadow)
     */
    public function attempt(array $input, ?callable $callback): ?array
    {
        if ($callback) {
            return $callback($input);
        }
        return (new ShadowAuthStrategy())->attempt($input);
    }
}
