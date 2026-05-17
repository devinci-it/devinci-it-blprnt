<?php

namespace DevinciIT\Blprnt\Console\Commands\Auth;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Support\Console;
use DevinciIT\Blprnt\Support\Hash;
use DevinciIT\Blprnt\Auth\ShadowFileManager;
use RuntimeException;

class ShadowInitCommand extends Command
{
    protected string $signature = 'shadow:init';
    protected string $description = 'Initialize shadow authentication file';

    protected function configureOptions(): void
    {
        $this->addOption('user', 'u', true);
        $this->addOption('force', 'f');
        $this->addOption('no-secure');
    }

    public function handle(array $args = [])
    {
        try {
            $manager = $this->manager();

            $this->assertState($manager);

            [$user, $pass] = $this->credentials();

            $this->create($manager, $user, $pass);

            Console::success("Shadow file created for: {$user}");

            $this->maybeSecure();

        } catch (RuntimeException $e) {
            Console::error($e->getMessage());
            exit(1);
        }
    }

    // ─────────────────────────────
    // CORE
    // ─────────────────────────────

    protected function create(
        ShadowFileManager $manager,
        string $user,
        string $pass
    ): void {
        $hash = Hash::make($pass);

        if ($manager->exists() && !$this->getOption('force')) {
            throw new RuntimeException('Shadow file already exists (use --force).');
        }

        $manager->writeUser($user, $hash);
    }

    protected function maybeSecure(): void
    {
        if ($this->getOption('no-secure')) {
            Console::warn("Skipping secure.sh (manual security required)");
            return;
        }

        Console::info("Running security hardening script...");
        Console::line("You may be prompted for sudo password.");

        system('bash scripts/secure.sh');
    }

    // ─────────────────────────────
    // INPUT
    // ─────────────────────────────

    protected function credentials(): array
    {
        $user = $this->getOption('user') ?? Console::input("Username [admin]: ") ?: 'admin';

        $pass = Console::secret("Password: ");
        $confirm = Console::secret("Confirm Password: ");

        if ($pass !== $confirm) {
            throw new RuntimeException("Passwords do not match.");
        }

        if (strlen($pass) < 6) {
            throw new RuntimeException("Password too short.");
        }

        return [$user, $pass];
    }

    // ─────────────────────────────
    // STATE
    // ─────────────────────────────

    protected function assertState(ShadowFileManager $manager): void
    {
        if ($manager->exists() && !$this->getOption('force')) {
            throw new RuntimeException("Shadow file already exists. Use --force.");
        }
    }

    protected function manager(): ShadowFileManager
    {
        return new ShadowFileManager();
    }
}