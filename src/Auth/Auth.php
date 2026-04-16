<?php

namespace DevinciIT\Blprnt\Auth;

use DevinciIT\Blprnt\Core\View;
use DevinciIT\Blprnt\Core\AuthResult;
use DevinciIT\Blprnt\Support\AuthLogger;

class Auth
{
    protected array $fields;
    protected bool $useTokens;
    public AuthSession $session;
    protected Authenticator $authenticator;
    protected TokenManager $tokens;

    public function __construct(
        ?AuthSession $session = null,
        ?Authenticator $authenticator = null,
        ?TokenManager $tokens = null,
        array $fields = ['username', 'password'],
        bool $useTokens = false
    ) {
        $this->session = $session ?: new AuthSession();
        $this->authenticator = $authenticator ?: new Authenticator();
        $this->tokens = $tokens ?: new TokenManager();
        $this->fields = $fields;
        $this->useTokens = $useTokens;
    }

    public static function make(): self
    {
        return new self();
    }

    public function fields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    public function useTokens(bool $enabled = true): self
    {
        $this->useTokens = $enabled;
        return $this;
    }

    public function attempt(array $credentials): AuthResult
    {
        AuthLogger::debug('Handling auth attempt', $credentials);
        $this->session->start();
        $input = $this->extractCredentials($credentials);
        $user = $this->authenticator->attempt($input, null);
        if (!$user) {
            AuthLogger::error('Auth failed', $input);
            return new AuthResult(false, null, 'Invalid credentials');
        }
        $this->session->regenerate();
        $this->session->login($user);
        if ($this->useTokens) {
            $token = $this->tokens->issue($user);
            $this->session->set('auth_token', $token);
        }
        AuthLogger::log('Auth success', $user);
        return new AuthResult(true, $user);
    }

    public function check(): bool
    {
        return $this->session->check();
    }

    public function user(): ?array
    {
        return $this->session->user();
    }

    public function logout(): void
    {
        $this->session->logout();
    }

    public function issueToken(array $user): string
    {
        return $this->tokens->issue($user);
    }

    public function validateToken(?string $token): bool
    {
        return $this->tokens->validate($token);
    }

    protected function extractCredentials(array $request): array
    {
        $fields = $this->fields ?: ['username', 'password'];
        $input = [];
        foreach ($fields as $field) {
            $input[$field] = $request[$field] ?? null;
        }
        return $input;
    }
}

