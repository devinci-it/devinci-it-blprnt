<?php

namespace DevinciIT\Blprnt\Auth;

/**
 * TokenStore: Handles storing, finding, validating, and deleting tokens with expiration.
 * Simple file-based implementation (storage/tokens.php).
 */
class TokenStore
{
    protected string $file;

    public function __construct()
    {
        $this->file = dirname(__DIR__, 2) . '/storage/tokens.php';
        if (!file_exists($this->file)) {
            file_put_contents($this->file, '<?php return [];');
                $this->secureFile();
                
        }
    }

    public function secureFile(): void
    {
        // Restrict access to owner only
        @chmod($this->file, 0600);
    }

    protected function load(): array
    {
        if (!file_exists($this->file) || filesize($this->file) === 0) {
            return [];
        }
        $tokens = @include $this->file;
        if (!is_array($tokens)) {
            return [];
        }
        return $tokens;
    }

    protected function save(array $tokens): void
    {
        file_put_contents($this->file, '<?php return ' . var_export($tokens, true) . ';');
    }

    /**
     * Store a token with user and TTL (seconds)
     */
    public function store(string $token, array $user, int $ttl): void
    {
        $tokens = $this->load();
        $tokens[$token] = [
            'user' => $user,
            'expires' => time() + $ttl,
        ];
        $this->save($tokens);
    }

    /**
     * Find a token by hashed value
     */
    public function find(string $hashedToken): ?array
    {
        $tokens = $this->load();
        return $tokens[$hashedToken] ?? null;
    }

    /**
     * Validate a token (not expired)
     */
    public function validate(string $hashedToken): ?array
    {
        $tokens = $this->load();
        $entry = $tokens[$hashedToken] ?? null;
        if (!$entry) return null;
        if ($entry['expires'] < time()) {
            $this->delete($hashedToken);
            return null;
        }
        return $entry['user'];
    }

    /**
     * Delete a token
     */
    public function delete(string $hashedToken): void
    {
        $tokens = $this->load();
        unset($tokens[$hashedToken]);
        $this->save($tokens);
    }

    /**
     * Cleanup expired tokens
     */
    public function cleanupExpired(): void
    {
        $tokens = $this->load();
        $now = time();
        foreach ($tokens as $token => $entry) {
            if ($entry['expires'] < $now) {
                unset($tokens[$token]);
            }
        }
        $this->save($tokens);
    }
}
