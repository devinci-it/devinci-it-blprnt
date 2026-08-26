<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Database;

class Schema
{
    /**
     * Holds all generated SQL strings across all tables definitions
     *
     * @var string[]
     */
    private static array $allStatements = [];

    /**
     * Standard table creator (executes SQL immediately)
     */
    public static function create(string $table, callable $callback): void
    {
        $builder = new TableBuilder($table);
        $callback($builder);
        $statements = $builder->toSqlStatements();

        // Track statements globally for file generation
        self::$allStatements = array_merge(self::$allStatements, $statements);

        // Execute on the live DB unless we're performing a schema dump.
        // During schema dumps we set SCHEMA_DUMP to avoid requiring a DB driver
        // or running side-effectful operations in migration files.
        if (!defined('SCHEMA_DUMP') || !SCHEMA_DUMP) {
            foreach ($statements as $sql) {
                // Expect a global helper `db()` returning a PDO instance
                db()->exec($sql);
            }
        }
    }

    /**
     * Compiles all defined statements into a clean SQL string
     */
    public static function compileToSqlString(): string
    {
        $output = "-- -----------------------------------------------------\n";
        $output .= "-- Automatically Generated Framework Database Schema\n";
        $output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- -----------------------------------------------------\n\n";

        // Enable foreign key support by default in the script
        $output .= "PRAGMA foreign_keys = ON;\n\n";

        foreach (self::$allStatements as $statement) {
            $output .= $statement . "\n\n";
        }

        return $output;
    }

    /**
     * Exports the compiled schema string into a physical .sql file
     */
    public static function saveToFile(string $filePath): bool
    {
        $sqlContent = self::compileToSqlString();

        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return file_put_contents($filePath, $sqlContent) !== false;
    }
}
