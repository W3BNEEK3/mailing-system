<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Url — URL and path generation helpers
 */
class Url
{
    /**
     * Get the full URL to a public asset (CSS, JS, images).
     *
     * Usage:
     *   Url::asset('css/app.css') => 'http://localhost:8000/assets/css/app.css'
     */
    public static function asset(string $path): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }

    /**
     * Get the full URL for an application route.
     *
     * Usage:
     *   Url::url('/login')   => 'http://localhost:8000/login'
     *   Url::url('/')        => 'http://localhost:8000'
     */
    public static function url(string $path = ''): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        return $path && $path !== '/' ? $base . '/' . ltrim($path, '/') : $base;
    }

    /**
     * Get the absolute filesystem path to a storage file.
     *
     * Usage:
     *   Url::storagePath('uploads/logos/logo.png')
     *   => '/var/www/emirates/storage/uploads/logos/logo.png'
     */
    public static function storagePath(string $relative): string
    {
        return BASE_PATH . '/storage/' . ltrim($relative, '/');
    }

    /**
     * Get the public URL for a stored file (served via StorageController).
     *
     * Uploaded files live outside the web root in storage/, so they're
     * served through a PHP controller at /storage/{type}/{filename}.
     *
     * Usage:
     *   Url::storageUrl('logos/global/logo-abc123.png')
     *   => 'http://localhost:8000/storage/logos/global/logo-abc123.png'
     */
    public static function storageUrl(string $relative): string
    {
        $base = rtrim(config('app.url', 'http://localhost'), '/');
        return $base . '/storage/' . ltrim($relative, '/');
    }
}
