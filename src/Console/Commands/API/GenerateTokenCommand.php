<?php
namespace DevinciIT\Blprnt\Console\Commands\API;

use DevinciIT\Blprnt\Console\Command;

class GenerateTokenCommand extends Command
{
    protected string $signature = 'generate:token';
    protected string $description = 'Generate a random API token';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('write', 'w', false, false)
            ->addOption('length', 'l', true, 32)
            ->addOption('env', 'e', true, true, 'API_TOKEN')
            ->addOption('prefix', 'p', true, 'SECRET_');
    }

    public function handle(array $args = [])
    {
        if ((bool)$this->getOption('help', false)) {
            $this->printHelp();
            return;
        }

        $unknown = $this->getUnknownOptions();
        if (!empty($unknown)) {
            fwrite(STDERR, 'Unknown option(s): ' . implode(', ', $unknown) . "\n");
            $this->printHelp();
            return;
        }

        $length = max(16, (int)$this->getOption('length', 32));
        $envKey = trim((string)$this->getOption('env', 'API_TOKEN'));
        $prefix = (string)$this->getOption('prefix', 'SECRET_');
        $token = $prefix . $this->generateToken($length);

        $shouldWrite = (bool)$this->getOption('write', false) || $envKey !== '';

        echo $token . PHP_EOL;

        if ($shouldWrite) {
            $updated = $this->writeTokenToEnv($envKey, $token);

            if ($updated) {
                echo sprintf('Updated .env: %s=%s', $envKey, $token) . PHP_EOL;
            } else {
                fwrite(STDERR, "Unable to update .env file. Token was only printed.\n");
            }
        }
    }

    private function generateToken(int $length): string
    {
        $byteLength = (int)ceil($length / 2);

        return substr(bin2hex(random_bytes($byteLength)), 0, $length);
    }

    private function writeTokenToEnv(string $key, string $token): bool
    {
        $envPath = $this->getEnvPath();

        if (!is_file($envPath)) {
            return false;
        }

        $this->warnIfEnvPermissionsAreInsecure($envPath);

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            return false;
        }

        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';
        $replacement = $key . '=' . $token;

        if (preg_match($pattern, $contents) === 1) {
            $contents = preg_replace($pattern, $replacement, $contents, 1);
        } else {
            $contents = rtrim($contents) . PHP_EOL . $replacement . PHP_EOL;
        }

        return file_put_contents($envPath, $contents) !== false;
    }

    private function getEnvPath(): string
    {
        return getcwd() . '/.env';
    }

    private function warnIfEnvPermissionsAreInsecure(string $envPath): void
    {
        $perms = @fileperms($envPath);

        if ($perms === false) {
            return;
        }

        $mode = $perms & 0777;

        if ($mode !== 0600) {
            fwrite(
                STDERR,
                sprintf(
                    "Warning: .env permissions are %o, expected 600. Consider running: chmod 600 %s\n",
                    $mode,
                    $envPath
                )
            );
        }
    }

    private function printHelp(): void
    {
        echo "Usage:\n";
        echo "  blprnt generate:token [--length=32] [--env=API_TOKEN] [--prefix=SECRET_] [--write]\n\n";
        echo "Options:\n";
        echo "  --length   Token length in characters (default: 32)\n";
        echo "  --env      Environment key to update and write to .env (default: API_TOKEN)\n";
        echo "  --prefix   Token prefix to prepend to the generated token (default: SECRET_)\n";
        echo "  --write    Persist the generated token to .env\n";
    }
}
