<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Date — Date and time formatting helpers
 *
 * All methods use the application timezone set in config/app.php.
 */
class Date
{
    /**
     * Format a datetime string for display.
     *
     * Usage:
     *   Date::format('2025-06-15 14:30:00')       => '15 Jun 2025, 2:30pm'
     *   Date::format('2025-06-15 14:30:00', 'd/m/Y') => '15/06/2025'
     */
    public static function format(string $datetime, string $format = 'd M Y, g:ia'): string
    {
        try {
            $timezone = new \DateTimeZone(config('app.timezone', 'UTC'));
            $dt       = new \DateTime($datetime, new \DateTimeZone('UTC'));
            $dt->setTimezone($timezone);
            return $dt->format($format);
        } catch (\Throwable) {
            return $datetime; // Return the raw value if parsing fails
        }
    }

    /**
     * Return a human-readable time difference like "2 hours ago" or "just now".
     *
     * Usage:
     *   Date::diffForHumans('2025-06-15 12:00:00')  => '2 hours ago'
     *   Date::diffForHumans('2025-06-15 13:59:30')  => 'just now'
     */
    public static function diffForHumans(string $datetime): string
    {
        try {
            $past    = new \DateTime($datetime, new \DateTimeZone('UTC'));
            $now     = new \DateTime('now', new \DateTimeZone('UTC'));
            $seconds = $now->getTimestamp() - $past->getTimestamp();

            if ($seconds < 60)  return 'just now';
            if ($seconds < 3600) {
                $m = (int)($seconds / 60);
                return $m . ' minute' . ($m !== 1 ? 's' : '') . ' ago';
            }
            if ($seconds < 86400) {
                $h = (int)($seconds / 3600);
                return $h . ' hour' . ($h !== 1 ? 's' : '') . ' ago';
            }
            if ($seconds < 2592000) {
                $d = (int)($seconds / 86400);
                return $d . ' day' . ($d !== 1 ? 's' : '') . ' ago';
            }

            // Older than 30 days — show the actual date
            return static::format($datetime, 'd M Y');

        } catch (\Throwable) {
            return $datetime;
        }
    }

    /**
     * Get the current datetime in MySQL format (Y-m-d H:i:s).
     *
     * Usage:
     *   Date::now() => '2025-06-15 14:32:01'
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
