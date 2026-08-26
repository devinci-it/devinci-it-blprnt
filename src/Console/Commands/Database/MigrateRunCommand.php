<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Console\Commands\Database;

/**
 * Runs pending migration files against the live database.
 *
 * This is the missing other half of `schema:dump`: that command always
 * defines SCHEMA_DUMP, which is the same flag migration files use to *skip*
 * executing their statements. `migrate:run` is the command that actually
 * calls Schema::create()'s live-execution path, in filename order, tracking
 * what's already been applied so re-running is safe.
 */
class MigrateRunCommand extends AbstractMigrationCommand
{
    protected string $signature = 'migrate:run';
    protected string $description = 'Run pending database migrations from database/migrations';

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

        $seedRequested = (bool) $this->getOption('seed', false);
        $seedAllowed = $seedRequested || $this->isLocalEnvironment();

        if (!$seedAllowed) {
            info('Seed data will be skipped (APP_ENV is not local and --seed was not passed).');
        }

        return $this->runMigrations($files, db(), $seedAllowed);
    }

    private function printHelp(): void
    {
        echo "\n";
        echo "Description:\n";
        echo "  Run pending migration files from database/migrations, in filename order.\n";
        echo "  Applied migrations are tracked in a `migrations` table so re-running only\n";
        echo "  picks up new files.\n";
        echo "\n";
        echo "Usage:\n";
        echo "  php blprnt migrate:run [options]\n";
        echo "\n";
        echo "Options:\n";
        echo "  -h, --help          Show this help message\n";
        echo "      --path=<dir>    Migrations directory, relative to cwd (default: database/migrations)\n";
        echo "      --seed          Allow migrations to insert seed/sample data even outside a local\n";
        echo "                      environment (APP_ENV=local/development/dev seeds automatically\n";
        echo "                      without this flag; anywhere else, seeding is skipped unless passed)\n";
        echo "\n";
        echo "Examples:\n";
        echo "  php blprnt migrate:run\n";
        echo "  php blprnt migrate:run --path=database/migrations\n";
        echo "  php blprnt migrate:run --seed\n";
        echo "\n";
    }
}
