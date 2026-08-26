<?php

declare(strict_types=1);

namespace DevinciIT\Blprnt\Support;

class Database
{
    private \PDO $pdo;

    /**
     * Accepts a simple config array. For sqlite, set 'driver' => 'sqlite' and 'database' => path
     * For other drivers provide host/database/username/password
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $driver = $config['driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $path = $config['database'] ?? __DIR__ . '/../../storage/database.sqlite';
            $dsn = 'sqlite:' . $path;
            $this->pdo = new \PDO($dsn);
        } else {
            $dsn = sprintf('%s:host=%s;dbname=%s;charset=utf8mb4', $driver, $config['host'] ?? '127.0.0.1', $config['database'] ?? '');
            $this->pdo = new \PDO($dsn, $config['username'] ?? '', $config['password'] ?? '');
        }

        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    public function exec(string $sql)
    {
        return $this->pdo->exec($sql);
    }

    /**
     * Prepare + execute a statement, return the executed PDOStatement.
     *
     * @param array<int|string, mixed> $params
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * Run a query and return the first row (or null if there isn't one).
     *
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch(\PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    /**
     * Run a query and return every row.
     *
     * @param array<int|string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Bind a default 'database' service into the container if nothing has
     * bound one yet. Reads DB_* env vars, falling back to a sqlite file
     * under storage/database.sqlite.
     *
     * CLI bootstrap doesn't load bootstrap/app.php (HTTP-only request
     * orchestration), so any command that touches the database needs this
     * as a defensive fallback rather than assuming 'database' is already
     * bound. Web apps that want a database should still bind their own
     * 'database' service in bootstrap/app.php — this fallback is meant for
     * the CLI/migration path, not as a replacement for that.
     */
    public static function ensureBound(): void
    {
        if (app()->has('database')) {
            return;
        }

        app()->bind('database', function () {
            return new self([
                'driver'   => getenv('DB_DRIVER') ?: 'sqlite',
                'database' => getenv('DB_DATABASE') ?: getcwd() . '/storage/database.sqlite',
                'host'     => getenv('DB_HOST') ?: '127.0.0.1',
                'username' => getenv('DB_USERNAME') ?: '',
                'password' => getenv('DB_PASSWORD') ?: '',
            ]);
        });
    }
}
