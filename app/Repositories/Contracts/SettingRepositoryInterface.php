<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * SettingRepositoryInterface
 *
 * Contract for reading and writing application settings
 * stored in the settings table as key-value pairs.
 */
interface SettingRepositoryInterface
{
    /**
     * Get a setting value by key.
     * Returns $default if the key does not exist.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Create or update a setting value.
     */
    public function set(string $key, mixed $value): void;

    /**
     * Get all settings as a flat key => value array.
     * Example: ['site_name' => 'Emirates', 'primary_color' => '#4F46E5', ...]
     */
    public function allAsKeyValue(): array;
}
