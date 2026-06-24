<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;

/**
 * SettingRepository
 *
 * Reads and writes application settings from the settings table.
 *
 * Caches all settings in a static property for the request lifetime
 * so we don't hit the database every time setting() is called in a view.
 */
class SettingRepository implements SettingRepositoryInterface
{
    /**
     * In-memory cache of all settings for the current request.
     * Format: ['key' => 'value', 'key2' => 'value2', ...]
     *
     * Static so the cache persists across multiple instances of this class.
     */
    private static ?array $cache = null;

    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get a setting value by key.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->allAsKeyValue();
        return $all[$key] ?? $default;
    }

    /**
     * Create or update a setting value.
     * Also invalidates the in-memory cache so the next get() reads fresh data.
     */
    public function set(string $key, mixed $value): void
    {
        Setting::setValue($key, $value);

        // Clear the cache so the next call to allAsKeyValue() re-reads from DB
        static::$cache = null;
    }

    /**
     * Get all settings as a flat key => value array.
     *
     * Results are cached in a static property for the duration of the request.
     * The cache is cleared by set() so changes are always visible immediately.
     */
    public function allAsKeyValue(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        // Load all rows from the settings table
        $rows = Setting::all('key', 'ASC');

        // Build the key => value map
        $result = [];
        foreach ($rows as $setting) {
            $result[(string)$setting->key] = $setting->value;
        }

        static::$cache = $result;
        return $result;
    }

    // ─── RepositoryInterface stubs ────────────────────────────────────────
    // Settings don't use the standard CRUD interface, but we implement
    // basic stubs so this class is consistent with the rest of the codebase.

    public function find(int $id): ?object
    {
        return Setting::find($id);
    }

    public function all(): array
    {
        return Setting::all();
    }

    public function create(array $data): object
    {
        return Setting::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $setting = Setting::find($id);
        return $setting ? $setting->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $setting = Setting::find($id);
        return $setting ? $setting->delete() : false;
    }

    /**
     * Clear the in-memory cache.
     * Useful in tests or when settings are bulk-updated.
     */
    public static function clearCache(): void
    {
        static::$cache = null;
    }
}
