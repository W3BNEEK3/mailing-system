<?php

declare(strict_types=1);

/**
 * SuperAdminSeeder
 *
 * Creates a super admin user account.
 * Reads credentials from SUPER_ADMIN_EMAIL and SUPER_ADMIN_PASSWORD in .env.
 * Skips if the user already exists (safe to run multiple times).
 */
class SuperAdminSeeder
{
    public function run(PDO $pdo): void
    {
        $email    = $_ENV['SUPER_ADMIN_EMAIL']    ?? 'ssadmin@emirates.local';
        $password = $_ENV['SUPER_ADMIN_PASSWORD'] ?? 'supersecret123';
        $name     = $_ENV['APP_NAME']             ?? 'Emirates Super Admin';

        // Check if the user already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            echo "    ℹ️  Super Admin [{$email}] already exists. Skipping.\n";
            return;
        }

        // Hash the password securely
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $hash, 'super_admin']);

        echo "    ✅ Super Admin user created: {$email}\n";
        echo "    ⚠️  Change the password after first login!\n";
    }
}
