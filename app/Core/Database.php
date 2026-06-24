<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Database
 *
 * Manages a single PDO database connection for the entire request lifecycle.
 * Uses the Singleton pattern — only one connection is ever created.
 *
 * Usage:
 *   $pdo = Database::getInstance()->getConnection();
 */
class Database
{
    private static ?Database $instance = null;
    private \PDO $connection;

    private function __construct()
    {
        $config = require(BASE_PATH . '/config/database.php');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['name'],
            $config['charset']
        );

        try {
            $this->connection = new \PDO($dsn, $config['user'], $config['pass'], [
                // Throw exceptions on errors (instead of returning false silently)
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                // Return rows as associative arrays by default
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                // Disable emulated prepared statements for true security
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (\PDOException $e) {
            // Wrap in a generic exception so the DB credentials
            // don't appear in error messages shown to users
            throw new \RuntimeException(
                'Database connection failed. Check your DB credentials in .env. ' .
                'Original error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Get the single Database instance (create it if it doesn't exist yet).
     */
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Get the underlying PDO connection object.
     */
    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}
