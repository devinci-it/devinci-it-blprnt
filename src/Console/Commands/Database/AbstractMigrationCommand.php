<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Console\Commands\Database;

use DevinciIT\Blprnt\Console\Command;
use DevinciIT\Blprnt\Support\Database;
use PDO;
use Throwable;

/**
 * Shared helpers for the migrate:* commands — path resolution, the
 * migrations bookkeeping table, and the "run these files" loop. Pulled out
 * so migrate:run, migrate:status, and migrate:fresh don't each carry their
 * own copy of the same logic.
 */
abstract class AbstractMigrationCommand extends Command
{
    protected function migrationsPath(): string
    {
        return $this->resolvePath((string) $this->getOption('path', 'database/migrations'));
    }

    /**
     * Relative paths resolve against cwd; a leading '/' is kept as-is
     * (absolute). Mirrors the generator commands' resolvePath() — pulled in
     * here too since migrationsPath() needs the same absolute-path support.
     */
    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return (string) getcwd();
        }

        if (str_starts_with($path, '/')) {
            return rtrim($path, '/');
        }

        return rtrim((string) getcwd() . '/' . ltrim($path, '/'), '/');
    }

    /**
     * @return string[]
     */
    protected function migrationFiles(string $path): array
    {
        $files = glob(rtrim($path, '/') . '/*.php') ?: [];
        sort($files, SORT_NATURAL);

        return $files;
    }

    protected function ensureDatabaseBound(): void
    {
        Database::ensureBound();
    }

    protected function ensureMigrationsTable(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS migrations (' .
            'id INTEGER PRIMARY KEY AUTOINCREMENT, ' .
            'migration TEXT NOT NULL UNIQUE, ' .
            'ran_at DATETIME DEFAULT CURRENT_TIMESTAMP' .
            ')'
        );
    }

    /**
     * @return string[]
     */
    protected function appliedMigrations(PDO $pdo): array
    {
        $statement = $pdo->query('SELECT migration FROM migrations');

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * True if the current environment is treated as safe to seed/reset data
     * in (mirrors ErrorHandler::isLocalDevelopment(), duplicated here to
     * avoid a hard dependency between Console and Core for one string check).
     */
    protected function isLocalEnvironment(): bool
    {
        $appEnv = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';

        return in_array(strtolower((string) $appEnv), ['local', 'development', 'dev'], true);
    }

    /**
     * Run every not-yet-applied migration file, in order. Stops on the first
     * failure and reports it. $seedAllowed becomes the BLPRNT_SEED constant
     * migration files can check before inserting starter/sample data.
     *
     * @param string[] $files
     * @return int Exit code — 0 on success (including "nothing to do"), 1 on failure.
     */
    protected function runMigrations(array $files, PDO $pdo, bool $seedAllowed): int
    {
        if (!defined('BLPRNT_SEED')) {
            define('BLPRNT_SEED', $seedAllowed);
        }

        $this->ensureMigrationsTable($pdo);
        $applied = $this->appliedMigrations($pdo);

        $ran = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $name = basename($file);

            if (in_array($name, $applied, true)) {
                $skipped++;
                continue;
            }

            info("Running: {$name}");

            try {
                require $file;
            } catch (Throwable $e) {
                error("Failed: {$name} — " . $e->getMessage());
                fwrite(STDERR, "\nMigration halted. Fix the error above, then re-run `php blprnt migrate:run`.\n");
                fwrite(STDERR, "Already-applied migrations before this one were kept — only \"{$name}\" and anything after it are pending.\n");
                return 1;
            }

            $stmt = $pdo->prepare('INSERT INTO migrations (migration) VALUES (:migration)');
            $stmt->execute([':migration' => $name]);

            success("Migrated: {$name}");
            $ran++;
        }

        newLine();

        if ($ran === 0) {
            info("Nothing to migrate — {$skipped} migration(s) already applied.");
        } else {
            $suffix = $skipped > 0 ? " ({$skipped} already applied, skipped.)" : '';
            success("Ran {$ran} migration(s).{$suffix}");
        }

        return 0;
    }
}
