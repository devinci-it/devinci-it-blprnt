<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Database;

class TableBuilder
{
    private string $table;
    private array $columns = [];
    private bool $hasTimestamps = false;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id(): self
    {
        $this->columns[] = "id INTEGER PRIMARY KEY AUTOINCREMENT";
        return $this;
    }

    public function string(string $name, bool $nullable = false): self
    {
        $this->columns[] = "{$name} TEXT" . ($nullable ? "" : " NOT NULL");
        return $this;
    }

    public function integer(string $name, bool $nullable = false): self
    {
        $this->columns[] = "{$name} INTEGER" . ($nullable ? "" : " NOT NULL");
        return $this;
    }

    public function text(string $name): self
    {
        $this->columns[] = "{$name} TEXT";
        return $this;
    }

    /**
     * Adds created_at and updated_at columns
     */
    public function timestamps(): self
    {
        $this->columns[] = "created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
        $this->columns[] = "updated_at DATETIME DEFAULT CURRENT_TIMESTAMP";
        $this->hasTimestamps = true;
        return $this;
    }

    /**
     * Adds a deleted_at column for soft deletes (nullable by default)
     */
    public function softDeletes(): self
    {
        $this->columns[] = "deleted_at DATETIME NULL DEFAULT NULL";
        return $this;
    }

    /**
     * Compiles the fluent structure into raw SQLite statements
     */
    public function toSqlStatements(): array
    {
        $statements = [];

        // 1. Build the Main Table Structure
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (\n";
        $sql .= implode(",\n", $this->columns);
        $sql .= "\n);";
        $statements[] = $sql;

        // 2. Automatically generate an On-Update Trigger for Timestamps
        if ($this->hasTimestamps) {
            $triggerName = "trigger_{$this->table}_updated_at";
            $triggerSql = "CREATE TRIGGER IF NOT EXISTS {$triggerName} \n                           AFTER UPDATE ON {$this->table}\n                           FOR EACH ROW\n                           BEGIN\n                               UPDATE {$this->table} \n                               SET updated_at = CURRENT_TIMESTAMP \n                               WHERE id = OLD.id;\n                           END;";
            $statements[] = $triggerSql;
        }

        return $statements;
    }
}
