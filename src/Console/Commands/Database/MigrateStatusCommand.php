<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Console\Commands\Database;

use PDO;

/**
 * Read-only companion to migrate:run — lists which migration files have
 * been applied and which are still pending, without running anything.
 */
class MigrateStatusCommand extends AbstractMigrationCommand
{
    protected string $signature = 'migrate:status';
    protected string $description = 'List applied and pending database migrations';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('path', null, true, false, 'database/migrations');
    }

    public function handle(array $args = []): int
    {
        if ((bool) $this->getOption('help', false)) {
            $this->printHelp();
            return 0;
        }

        $unknown = $this->getUnknownOptions();
        if (!empty($unknown)) {
            fwrite(STDERR, 'Unknown option(s): ' . implode(', ', $unknown) . "\n");
            $this->printHelp();
            return 1;
        }

        $migrationsPath = $this->migrationsPath();

        if (!is_dir($migrationsPath)) {
            info("No migrations directory found at {$migrationsPath}.");
            return 0;
        }

        $files = $this->migrationFiles($migrationsPath);

        if (empty($files)) {
            info("No migration files found in {$migrationsPath}.");
            return 0;
        }

        $this->ensureDatabaseBound();
        $pdo = db();

        $applied = $this->migrationsTableExists($pdo) ? $this->appliedMigrations($pdo) : [];

        $appliedCount = 0;
        $pendingCount = 0;

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $applied, true)) {
                echo "  [x] {$name}\n";
                $appliedCount++;
            } else {
                echo "  [ ] {$name}\n";
                $pendingCount++;
            }
        }

        newLine();
        info("{$appliedCount} applied, {$pendingCount} pending.");

        return 0;
    }

    /**
     * Read-only existence check — status shouldn't create the bookkeeping
     * table just by being asked about it (unlike migrate:run, which needs
     * it to exist before it can insert into it).
     */
    private function migrationsTableExists(PDO $pdo): bool
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $statement = $pdo->query(
                "SELECT name FROM sqlite_master WHERE type = 'table' AND name = 'migrations'"
            );

            return $statement !== false && $statement->fetchColumn() !== false;
        }

        try {
            $pdo->query('SELECT 1 FROM migrations LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function printHelp(): void
    {
        echo "\n";
        echo "Description:\n";
        echo "  List every migration file under the migrations directory and whether it's\n";
        echo "  been applied yet, without running anything.\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php blprnt migrate:status [options]\n";
        echo "\n";
        echo "Options:\n";
        echo "  -h, --help          Show this help message\n";
        echo "      --path=<dir>    Migrations directory, relative to cwd (default: database/migrations)\n";
        echo "\n";
    }
}
