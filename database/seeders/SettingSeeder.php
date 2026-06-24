<?php

declare(strict_types=1);

/**
 * database/seeders/SettingSeeder.php
 *
 * Seeds default values for all platform settings.
 * Uses INSERT IGNORE so re-running is always safe — existing values are preserved.
 *
 * All keys listed here must match exactly the keys used in SettingsController::update()
 * and read via setting() throughout the application.
 */
class SettingSeeder
{
    public function run(\PDO $pdo): void
    {
        $defaults = [
            // Platform identity
            ['site_name',         'Emirates'],
            ['site_url',          'http://localhost'],
            ['site_logo_path',    null],

            // Email sending defaults
            ['sender_name',       'Emirates Mailer'],
            ['sender_email',      ''],

            // Email branding
            ['email_logo_path',   null],
            ['primary_color',     '#1d4ed8'],
            ['secondary_color',   '#0f172a'],

            // Localisation
            ['default_language',  'en'],
            ['timezone',          'Africa/Lagos'],
        ];

        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO settings (`key`, `value`)
             VALUES (?, ?)'
        );

        foreach ($defaults as [$key, $value]) {
            $stmt->execute([$key, $value]);
        }
    }
}
