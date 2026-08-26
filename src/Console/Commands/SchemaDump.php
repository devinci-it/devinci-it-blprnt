<?php

namespace DevinciIT\Blprnt\Console\Commands;

use DevinciIT\Blprnt\Console\Command;

class SchemaDump extends Command
{
    protected string $signature = 'schema:dump';
    protected string $description = 'Dump compiled schema to stdout or save to a file';

    public function handle(array $args = [])
    {
        $file = $args[0] ?? null;

        // If a PHP migration file was passed, require it so the Schema::create
        // calls populate the Schema registry, but avoid overwriting PHP files.
        if ($file && str_ends_with($file, '.php')) {
            if (!is_file($file)) {
                echo "Migration file not found: {$file}\n";
                return 1;
            }

            // Indicate to migrations that we're doing a schema dump (they can
            // skip data-only operations when this is defined).
            if (!defined('SCHEMA_DUMP')) {
                define('SCHEMA_DUMP', true);
            }

            // Ensure a database service is available in CLI context (some CLI
            // bootstraps don't bind it by default).
            try {
                \DevinciIT\Blprnt\Support\Database::ensureBound();
            } catch (\Throwable $e) {
                // If app() isn't available for some reason, continue and let
                // require fail with a clearer error.
            }

            require $file;

            $sql = \DevinciIT\Blprnt\Database\Schema::compileToSqlString();

            echo $sql;

            return 0;
        }

        $sql = \DevinciIT\Blprnt\Database\Schema::compileToSqlString();

        if ($file && str_ends_with($file, '.sql')) {
            $dir = dirname($file);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($file, $sql);
            echo "Wrote schema to {$file}\n";
            return 0;
        }

        // Default: print to stdout
        echo $sql;
        return 0;
    }
}
