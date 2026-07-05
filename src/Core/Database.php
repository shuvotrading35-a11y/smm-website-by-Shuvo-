<?php

declare(strict_types=1);

namespace SMMPanel\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Database — PDO singleton wrapper.
 *
 * All queries use prepared statements. Never concatenate user input.
 */
final class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $host    = Config::required('DB_HOST');
        $port    = Config::get('DB_PORT', 3306);
        $dbname  = Config::required('DB_NAME');
        $charset = Config::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                Config::required('DB_USER'),
                Config::required('DB_PASS'),
                $options
            );
        } catch (PDOException $e) {
            // Log raw error but surface generic message to callers
            error_log('[DB] Connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database connection failed.', 0, $e);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /** Prevent cloning of singleton. */
    private function __clone(): void {}

    // ── Public API ────────────────────────────────────────────

    /**
     * Prepare and execute a statement.
     *
     * @param string $sql    Parameterised SQL
     * @param array  $params Bind values (positional or named)
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row.
     */
    public function fetchOne(string $sql, array $params = []): array|false
    {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Fetch all rows.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single column value.
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Execute an INSERT and return the last inserted ID.
     */
    public function insert(string $table, array $data): string|false
    {
        if (empty($data)) {
            return false;
        }

        $cols        = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $columns      = implode(', ', array_map(fn($c) => "`{$c}`", $cols));

        $sql = "INSERT INTO `{$table}` ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));

        return $this->pdo->lastInsertId();
    }

    /**
     * Execute an UPDATE statement.
     *
     * @param string $table
     * @param array  $data    Columns to update
     * @param array  $where   ['col = ?' => value] or ['col' => value]
     */
    public function update(string $table, array $data, array $where): int
    {
        if (empty($data) || empty($where)) {
            return 0;
        }

        $setParts = [];
        $params   = [];

        foreach ($data as $col => $val) {
            $setParts[] = "`{$col}` = ?";
            $params[]   = $val;
        }

        $whereParts = [];

        foreach ($where as $col => $val) {
            if (str_contains($col, '?')) {
                // Full expression passed, e.g. 'id = ?'
                $whereParts[] = $col;
            } else {
                $whereParts[] = "`{$col}` = ?";
            }
            $params[] = $val;
        }

        $sql = "UPDATE `{$table}` SET "
            . implode(', ', $setParts)
            . ' WHERE '
            . implode(' AND ', $whereParts);

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Run a callable inside a transaction, auto-rolling back on exception.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Escape a value for use in LIKE clauses.
     */
    public function escapeLike(string $value, string $char = '\\'): string
    {
        return str_replace(
            [$char, '%', '_'],
            [$char . $char, $char . '%', $char . '_'],
            $value
        );
    }

    /**
     * Get last insert ID.
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * Directly expose PDO for rare edge cases.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
