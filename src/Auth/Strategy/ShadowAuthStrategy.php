<?php

namespace DevinciIT\Blprnt\Auth\Strategy;

use DevinciIT\Blprnt\Support\Hash;

class ShadowAuthStrategy
{
    /**
     * Attempt authentication using shadow file
     */
    public function attempt(array $credentials): ?array
    {
        $shadow = $this->loadShadowFile();
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        if (!$username || !$password) {
            return null;
        }
        if (!isset($shadow[$username])) {
            return null;
        }
        $hash = $shadow[$username]['password'] ?? '';
        if ($this->verify($password, $hash)) {
            return ['username' => $username];
        }
        return null;
    }

    /**
     * Load the shadow file
     */
    protected function loadShadowFile(): array
    {
        $file = dirname(__DIR__, 3) . '/storage/secure/shadow.php';
        if (!file_exists($file)) {
            return [];
        }
        return include $file;
    }

    /**
     * Verify password against hash
     */
    protected function verify(string $input, string $hash): bool
    {
        return Hash::verify($input, $hash);
    }
}
