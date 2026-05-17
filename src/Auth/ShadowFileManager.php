<?php

namespace DevinciIT\Blprnt\Auth;

use RuntimeException;

class ShadowFileManager
{
    protected string $file;

    public function __construct()
    {
        $this->file = $this->resolvePath();
    }

    // ─────────────────────────────────────────────
    // PUBLIC API
    // ─────────────────────────────────────────────

    public function exists(): bool
    {
        return file_exists($this->file);
    }

    public function create(string $username, string $hash): void
    {
        $this->assertNotExists();

        $data = $this->buildInitialData($username, $hash);

        $this->write($data);
        $this->secure();
    }

    public function update(string $username, string $hash): void
    {
        $data = $this->read();
        $data = $this->mergeUser($data, $username, $hash);

        $this->write($data);
        $this->secure();
    }

    public function read(): array
    {
        $this->assertExists();

        return $this->loadFile();
    }

    public function write(array $data): void
    {
        $this->ensureDirectoryExists();
        $this->writeFile($data);
    }

    public function secure(): void
    {
        $this->applyPermissions();
        $this->applyOwnershipIfAllowed();
    }

    // ─────────────────────────────────────────────
    // FILE PATH
    // ─────────────────────────────────────────────

    protected function resolvePath(): string
    {
        return dirname(__DIR__, 2) . '/storage/secure/shadow.php';
    }

    protected function getFile(): string
    {
        return $this->file;
    }

    public function writeUser(string $username, string $hash): void
{
    $data = $this->exists()
        ? $this->read()
        : [];

    $data[$username] = [
        'password' => $hash,
    ];

    $this->write($data);
    $this->secure();
}

    // ─────────────────────────────────────────────
    // VALIDATION
    // ─────────────────────────────────────────────

    protected function assertExists(): void
    {
        if (!$this->exists()) {
            throw new RuntimeException('Shadow file does not exist.');
        }
    }

    protected function assertNotExists(): void
    {
        if ($this->exists()) {
            throw new RuntimeException('Shadow file already exists.');
        }
    }

    // ─────────────────────────────────────────────
    // DATA BUILDING
    // ─────────────────────────────────────────────

    protected function buildInitialData(string $username, string $hash): array
    {
        return [
            $username => [
                'password' => $hash,
            ],
        ];
    }

    protected function mergeUser(array $data, string $username, string $hash): array
    {
        $data[$username] = [
            'password' => $hash,
        ];

        return $data;
    }

    // ─────────────────────────────────────────────
    // FILE IO
    // ─────────────────────────────────────────────

    protected function loadFile(): array
    {
        return include $this->file;
    }

    protected function writeFile(array $data): void
    {
        file_put_contents(
            $this->file,
            $this->formatPhpArray($data)
        );
    }

    protected function formatPhpArray(array $data): string
    {
        return '<?php return ' . var_export($data, true) . ';';
    }

    // ─────────────────────────────────────────────
    // DIRECTORY
    // ─────────────────────────────────────────────

    protected function ensureDirectoryExists(): void
    {
        $dir = dirname($this->file);

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
    }

    // ─────────────────────────────────────────────
    // SECURITY LAYER
    // ─────────────────────────────────────────────

    protected function applyPermissions(): void
    {
        @chmod($this->file, 0600);
    }

    protected function applyOwnershipIfAllowed(): void
    {
        if (!$this->canChangeOwnership()) {
            return;
        }

        $owner = $this->resolveSafeOwner();

        @chown($this->file, $owner);
        @chgrp($this->file, $owner);
    }

    protected function canChangeOwnership(): bool
    {
        return function_exists('posix_geteuid')
            && posix_geteuid() === 0;
    }

    protected function resolveSafeOwner(): string
    {
        if (!$this->isCli()) {
            return 'www-data';
        }

        return $this->detectCliUser() ?? 'www-data';
    }

    protected function detectCliUser(): ?string
    {
        if (!function_exists('posix_getpwuid')) {
            return null;
        }

        $uid = posix_getuid();
        $info = posix_getpwuid($uid);

        return $info['name'] ?? null;
    }

    protected function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }
}