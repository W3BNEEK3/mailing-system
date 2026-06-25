<?php

declare(strict_types=1);

/**
 * UserSeeder
 *
 * Creates the single admin user account.
 * Reads credentials from ADMIN_EMAIL and ADMIN_PASSWORD in .env.
 * Skips if the user already exists (safe to run multiple times).
 */
class UserSeeder
{
    public function run(PDO $pdo): void
    {
        $email    = $_ENV['ADMIN_EMAIL']    ?? 'admin@emirates.local';
        $password = $_ENV['ADMIN_PASSWORD'] ?? 'changeme123';
        $name     = $_ENV['APP_NAME']       ?? 'Emirates Admin';

        // Check if the user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            echo "    ℹ️  User [{$email}] already exists. Skipping.\n";
            return;
        }

        // Hash the password securely using bcrypt (PHP's default for PASSWORD_DEFAULT)
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $hash, 'super_admin']);

        echo "    ✅ Admin user created: {$email}\n";
        echo "    ⚠️  Change the password after first login!\n";
    }
}
