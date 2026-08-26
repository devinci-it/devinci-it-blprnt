<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Console\Commands\Database;

use PDO;

/**
 * Destructive dev convenience: drop known tables, then run every migration
 * from scratch. Refuses outright when APP_ENV=production (or unset, which
 * defaults to the same "production" treatment as ErrorHandler/isLocalEnvironment
 * use elsewhere) — there's no --force override for that check on purpose.
 */
class MigrateFreshCommand extends AbstractMigrationCommand
{
    protected string $signature = 'migrate:fresh';
    protected string $description = 'Drop known tables and re-run all migrations (refuses when APP_ENV=production)';

    protected function configureOptions(): void
    {
        $this->addOption('help', 'h', false, false)
            ->addOption('path', null, true, false, 'database/migrations')
            ->addOption('seed', null, false, false);
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

        if ($this->isProductionEnvironment()) {
            error('migrate:fresh refuses to run when APP_ENV=production. This command drops tables.');
            return 1;
        }

        $migrationsPath = $this->migrationsPath();

        if (!is_dir($migrationsPath)) {
            info("No migrations directory found at {$migrationsPath} — nothing to run.");
            return 0;
        }

        $files = $this->migrationFiles($migrationsPath);

        if (empty($files)) {
            info("No migration files found in {$migrationsPath}.");
            return 0;
        }

        $this->ensureDatabaseBound();
        $pdo = db();

        $this->dropKnownTables($pdo);

        $seedRequested = (bool) $this->getOption('seed', false);
        $seedAllowed = $seedRequested || $this->isLocalEnvironment();

        return $this->runMigrations($files, $pdo, $seedAllowed);
    }

    /**
     * APP_ENV unset is treated as production here too — same default the
     * rest of the framework (ErrorHandler::isLocalDevelopment()) uses, so a
     * fresh project with no .env can't accidentally nuke its own tables.
     */
    private function isProductionEnvironment(): bool
    {
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';

        return strtolower((string) $appEnv) === 'production';
    }

    private function dropKnownTables(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver !== 'sqlite') {
            $pdo->exec('DROP TABLE IF EXISTS migrations');
            warn("migrate:fresh only drops tables automatically for the sqlite driver. On '{$driver}', only the migrations tracking table was reset — drop any other tables manually if you need a truly clean slate.");
            return;
        }

        $tables = $pdo
            ->query("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'")
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $pdo->exec('DROP TABLE IF EXISTS "' . str_replace('"', '', $table) . '"');
        }

        if (!empty($tables)) {
            info('Dropped ' . count($tables) . ' table(s): ' . implode(', ', $tables));
        }
    }

    private function printHelp(): void
    {
        echo "\n";
        echo "Description:\n";
        echo "  Drop every table in the database (sqlite only — other drivers only reset\n";
        echo "  the migrations tracking table) and re-run every migration from scratch.\n";
        echo "  Refuses to run when APP_ENV=production or unset.\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php blprnt migrate:fresh [options]\n";
        echo "\n";
        echo "Options:\n";
        echo "  -h, --help          Show this help message\n";
        echo "      --path=<dir>    Migrations directory, relative to cwd (default: database/migrations)\n";
        echo "      --seed          Allow seed data even outside a local environment\n";
        echo "\n";
    }
}
