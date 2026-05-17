<?php

namespace DevinciIT\Blprnt\Console\Commands\Dev;

use DevinciIT\Blprnt\Console\Command;

class CreateSandBoxCommand extends Command
{
    protected string $signature = 'sandbox:create';
    protected string $description = 'Creates a sandbox project using local framework via Composer path repository';

    public function handle(array $args = [])
    {
        $this->banner();

        $root = getcwd();
        $demoPath = $this->resolveDemoPath($root);


        $this->ensureDirectory($demoPath);
        $this->writeComposerFile($demoPath, $root);
        $this->installDependencies($demoPath);

        $this->success();
    }

    private function banner(): void
    {
        newLine();
        hr();
        accent('Creating demo environment...');
        hr();
    }

    private function resolveDemoPath(string $root): string
    {
        return $root . '/test/sandbox';
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }

    private function writeComposerFile(string $demoPath, string $root): void
    {
        $projectRoot = realpath($root) ?: $root;

        $composer = [
            "name" => "blprnt/sandbox",
            "require" => [
                "devinci-it/blprnt" => "*"
            ],
            "repositories" => [
                [
                    "type" => "path",
                    "url" => $projectRoot,
                    "options" => [
                        "symlink" => true
                    ]
                ]
            ],
            "minimum-stability" => "dev",
            "prefer-stable" => true
        ];

        file_put_contents(
            $demoPath . '/composer.json',
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        accent("composer.json created in demo/");
    }

    // Sanitize command passthru to avoid shell injection risks, even though this is a local command
    private function sanitizeCommand(string $cmd): string
    {
        return escapeshellcmd($cmd);
    }

    private function installDependencies(string $demoPath): void
    {
        info('Running composer install in ' . $demoPath . '...');

        $originalCwd = getcwd();

        if ($originalCwd === false) {
            error('Unable to determine current working directory.');
            return;
        }

        if (!@chdir($demoPath)) {
            error('Unable to change directory to sandbox path: ' . $demoPath);
            return;
        }

        passthru('composer install');

        @chdir($originalCwd);
    }

    private function success(): void
    {
        newLine();
        accent('Sandbox ready. Linked to local framework root.');
        hr();
    }
}
