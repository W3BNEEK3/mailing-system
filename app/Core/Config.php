<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Config
 *
 * Loads configuration files from the config/ directory and provides
 * dot-notation access to their values.
 *
 * Usage:
 *   $config = new Config('/path/to/config');
 *   $config->get('app.debug');        // reads config/app.php -> 'debug' key
 *   $config->get('database.host');    // reads config/database.php -> 'host' key
 *   $config->get('app.missing', 'default'); // returns 'default' if key not found
 */
class Config
{
    /**
     * Stores already-loaded config files so we only read each file once per request.
     * Format: ['app' => ['name' => 'Emirates', 'debug' => true, ...], ...]
     */
    private array $cache = [];

    /**
     * The absolute path to the config/ directory.
     */
    private string $configPath;

    public function __construct(string $configPath)
    {
        // Remove any trailing slash so we can safely append filenames later
        $this->configPath = rtrim($configPath, '/\\');
    }

    /**
     * Get a config value using dot notation.
     *
     * The first segment before the dot is the filename.
     * Everything after the dot is the key within that file's array.
     *
     * Examples:
     *   get('app.name')       => value of 'name' key in config/app.php
     *   get('database.host')  => value of 'host' key in config/database.php
     *   get('app.missing', 'fallback') => 'fallback' (key does not exist)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Split 'app.debug' into ['app', 'debug']
        $parts = explode('.', $key, 2);

        // The first part is always the config filename (without .php)
        $file = $parts[0];

        // The second part is the key inside that file (may not exist for top-level access)
        $setting = $parts[1] ?? null;

        // Load the file into the cache if we haven't already
        $this->load($file);

        // If no key was specified after the dot, return the whole file array
        if ($setting === null) {
            return $this->cache[$file] ?? $default;
        }

        // Return the specific key value, or the default if it doesn't exist
        return $this->cache[$file][$setting] ?? $default;
    }

    /**
     * Load a config file into the cache.
     * Does nothing if the file is already cached.
     * Throws a RuntimeException if the file does not exist.
     */
    private function load(string $file): void
    {
        // Already loaded — skip
        if (isset($this->cache[$file])) {
            return;
        }

        $filePath = $this->configPath . '/' . $file . '.php';

        if (!file_exists($filePath)) {
            throw new \RuntimeException(
                "Config file not found: [{$filePath}]. " .
                "Make sure config/{$file}.php exists."
            );
        }

        // require the file — it must return an array
        $data = require $filePath;

        if (!is_array($data)) {
            throw new \RuntimeException(
                "Config file [{$filePath}] must return an array."
            );
        }

        $this->cache[$file] = $data;
    }
}
