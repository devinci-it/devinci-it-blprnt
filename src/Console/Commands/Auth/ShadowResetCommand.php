<?php

namespace DevinciIT\Blprnt\Console\Commands\Auth;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Support\Console;
use DevinciIT\Blprnt\Support\Hash;
use DevinciIT\Blprnt\Auth\ShadowFileManager;
use RuntimeException;

class ShadowResetCommand extends Command
{
    protected string $signature = 'shadow:reset';
    protected string $description = 'Reset shadow user password';

    protected function configureOptions(): void
    {
        $this->addOption('user', 'u', true);
        $this->addOption('no-secure');
    }

    public function handle(array $args = [])
    {
        try {
            $manager = $this->manager();

            $this->assertExists($manager);

            [$user, $pass] = $this->credentials();

            $this->update($manager, $user, $pass);

            Console::success("Password reset for: {$user}");

            $this->maybeSecure();

        } catch (RuntimeException $e) {
            Console::error($e->getMessage());
            exit(1);
        }
    }

    // ─────────────────────────────
    // CORE
    // ─────────────────────────────

    protected function update(
        ShadowFileManager $manager,
        string $user,
        string $pass
    ): void {
        $hash = Hash::make($pass);

        $manager->writeUser($user, $hash);
    }

    protected function maybeSecure(): void
    {
        if ($this->getOption('no-secure')) {
            Console::warn("Skipping secure.sh");
            return;
        }

        Console::info("Applying security rules...");
        system('bash scripts/secure.sh');
    }

    // ─────────────────────────────
    // INPUT
    // ─────────────────────────────

    protected function credentials(): array
    {
        $user = $this->getOption('user') ?? Console::input("Username: ");

        $pass = Console::secret("New Password: ");
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

    protected function assertExists(ShadowFileManager $manager): void
    {
        if (!$manager->exists()) {
            throw new RuntimeException("Shadow file does not exist.");
        }
    }

    protected function manager(): ShadowFileManager
    {
        return new ShadowFileManager();
    }
}