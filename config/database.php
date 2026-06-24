<?php

// MySQL database connection configuration.

return [
    'host'    => $_ENV['DB_HOST'] ?? 'localhost',
    'port'    => $_ENV['DB_PORT'] ?? '3306',
    'name'    => $_ENV['DB_NAME'] ?? 'emirates',
    'user'    => $_ENV['DB_USER'] ?? 'root',
    'pass'    => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
];
