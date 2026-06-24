<?php

declare(strict_types=1);

/**
 * SettingSeeder
 *
 * Inserts default values for all application settings.
 * Uses INSERT IGNORE so re-running never overwrites user-customised values.
 */
class SettingSeeder
{
    public function run(PDO $pdo): void
    {
        $appName = $_ENV['APP_NAME'] ?? 'Emirates';
        $appUrl  = $_ENV['APP_URL']  ?? 'http://localhost';

        // Default settings — all keys and their initial values
        $defaults = [
            // Platform identity
            'site_name'            => $appName,
            'site_url'             => $appUrl,
            'site_logo_path'       => null,

            // Email sending defaults
            'default_sender_name'  => $appName,
            'default_sender_email' => '',

            // Email branding (injected into templates)
            'email_logo_path'      => null,
            'primary_color'        => '#4F46E5',   // Indigo
            'secondary_color'      => '#10B981',   // Emerald

            // Localisation
            'default_language'     => 'en',
            'timezone'             => $_ENV['TIMEZONE'] ?? 'Africa/Lagos',
        ];

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)"
        );

        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
            echo "    ✅ Setting: {$key}\n";
        }
    }
}
