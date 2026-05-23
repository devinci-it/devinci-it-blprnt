<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Console\Commands\Dev;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Core\Installer;
use DevinciIT\Blprnt\Support\IOHelper;

class SyncSkeletonCommand extends Command
{
    protected string $signature = 'dev:sync-skel';
    protected string $description = 'Sync app/bootstrap/routes/public/config (and .env) into resources/skel for package publishing';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('source-root', 's', true, false, 'src')
            ->addOption('force', 'f', false, false)
            ->addOption('clean', 'c', false, false)
            ->addOption('prune-source', 'p', false, false);
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

        $projectRoot = rtrim((string)getcwd(), '/');
        $sourceRoot = $this->normalizeRoot((string)$this->getOption('source-root', 'src'));
        $force = (bool)$this->getOption('force', false);
        $clean = (bool)$this->getOption('clean', false);
        $pruneSource = (bool)$this->getOption('prune-source', false);

        $targets = $this->resolveTargets($this->parsedArguments[0] ?? null);
        if (empty($targets)) {
            fwrite(STDERR, "No valid targets to sync.\n");
            return;
        }

        $io = new IOHelper();

        foreach ($targets as $target) {
            [$fromRel, $toRel] = $target;

            $from = $this->join($projectRoot, $sourceRoot, $fromRel);
            $to = $this->join($projectRoot, '', $toRel);

            if (!file_exists($from)) {
                $io->warn("Skipped missing source: {$from}");
                continue;
            }

            if ($clean && file_exists($to)) {
                $this->removePath($to);
                $io->warn("Cleaned destination: {$to}");
            }

            if (is_dir($from)) {
                Installer::recurseCopy($from, $to, $force, $io);
            } else {
                Installer::publishFile($from, $to, $force, $io);
            }

            if ($pruneSource) {
                $this->removePath($from);
                $io->warn("Pruned source: {$from}");
            }
        }
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    private function resolveTargets(?string $specific): array
    {
        $defaultTargets = [
            ['app', 'resources/skel/app'],
            ['bootstrap', 'resources/skel/bootstrap'],
            ['routes', 'resources/skel/routes'],
            ['public', 'resources/skel/public'],
            ['config', 'resources/skel/config'],
            ['.env', 'resources/skel/.env.tmp'],
        ];

        if ($specific === null || trim($specific) === '') {
            return $defaultTargets;
        }

        $specific = $this->normalizeRelativePath($specific);

        if ($specific === '.env') {
            return [['.env', 'resources/skel/.env.tmp']];
        }

        $top = explode('/', $specific, 2)[0];
        $allowedTop = ['app', 'bootstrap', 'routes', 'public', 'config'];

        if (!in_array($top, $allowedTop, true)) {
            return [];
        }

        return [[$specific, 'resources/skel/' . $specific]];
    }

    private function normalizeRoot(string $value): string
    {
        $value = trim($value);

        if ($value === '' || $value === '.' || $value === './') {
            return '';
        }

        return trim(str_replace('\\', '/', $value), '/');
    }

    private function normalizeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $path = ltrim($path, '/');

        if ($path === '') {
            return '';
        }

        $parts = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                continue;
            }

            $parts[] = $segment;
        }

        return implode('/', $parts);
    }

    private function join(string $projectRoot, string $root, string $path): string
    {
        $projectRoot = rtrim($projectRoot, '/');
        $root = trim($root, '/');
        $path = ltrim($path, '/');

        if ($root === '') {
            return $projectRoot . '/' . $path;
        }

        return $projectRoot . '/' . $root . '/' . $path;
    }

    private function removePath(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $items = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach ($items as $item) {
            $this->removePath($path . '/' . $item);
        }

        @rmdir($path);
    }

    private function printHelp(): void
    {
        echo "\n";
        echo "Description:\n";
        echo "  Sync scaffold files from your development tree into resources/skel.\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php blprnt dev:sync-skel [path] [options]\n";
        echo "\n";
        echo "Arguments:\n";
        echo "  path               Optional path to sync (app, bootstrap, routes, public, config, .env,\n";
        echo "                     or nested paths like app/Views).\n";
        echo "\n";
        echo "Options:\n";
        echo "  -h, --help         Show this help message\n";
        echo "  -s, --source-root  Source root to read from (default: src). Use . for project root.\n";
        echo "  -f, --force        Overwrite destination files if they exist\n";
        echo "  -c, --clean        Remove destination target before syncing\n";
        echo "  -p, --prune-source Remove source target after successful copy\n";
        echo "\n";
        echo "Examples:\n";
        echo "  php blprnt dev:sync-skel --source-root=src\n";
        echo "  php blprnt dev:sync-skel app/Views --source-root=. --force\n";
        echo "  php blprnt dev:sync-skel public --source-root=src --clean\n";
        echo "\n";
    }
}
