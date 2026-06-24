<?php

declare(strict_types=1);

namespace App\Core;

/**
 * EnvLoader
 *
 * Reads a .env file and populates $_ENV and putenv() with its values.
 * This is a simple, dependency-free alternative to packages like vlucas/phpdotenv.
 *
 * Usage:
 *   EnvLoader::load('/path/to/project/.env');
 */
class EnvLoader
{
    /**
     * Load the .env file at the given path.
     *
     * Rules:
     * - Lines starting with # are comments and are skipped.
     * - Blank lines are skipped.
     * - Values are split on the FIRST = sign only (so values can contain = signs).
     * - Surrounding quotes (" or ') are stripped from values.
     * - Existing $_ENV values are NOT overwritten (server environment wins).
     */
    public static function load(string $path): void
    {
        // If there's no .env file, silently do nothing.
        // The application will fall back to defaults defined in config files.
        if (!file_exists($path)) {
            return;
        }

        // Read the file into an array, one entry per line
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            // Skip comment lines
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            // Skip lines that don't contain an = sign
            if (!str_contains($line, '=')) {
                continue;
            }

            // Split on the FIRST = only
            // e.g. "DB_PASS=p@ss=word" becomes ['DB_PASS', 'p@ss=word']
            [$name, $value] = explode('=', $line, 2);

            $name  = trim($name);
            $value = trim($value);

            // Strip surrounding quotes from the value
            // e.g. APP_NAME="Emirates" => Emirates
            //      APP_NAME='Emirates' => Emirates
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            // Only set the value if it isn't already defined in the server environment.
            // This allows real server environment variables to override .env values.
            if (!isset($_ENV[$name])) {
                $_ENV[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }
}
