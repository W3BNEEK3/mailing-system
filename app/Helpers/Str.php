<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Str — String utility methods
 *
 * Usage:
 *   Str::slug('Hello World!')  => 'hello-world'
 *   Str::truncate('Long text', 10) => 'Long text...'
 *   Str::mask('sk_live_abc123xyz', 4, 4) => 'sk_l••••••••xyz'
 *   Str::uuid() => 'a1b2c3d4-...'
 */
class Str
{
    /**
     * Convert a string to a URL-safe lowercase slug.
     *
     * Examples:
     *   'Hello World!'     => 'hello-world'
     *   'Newsletter (v2)' => 'newsletter-v2'
     */
    public static function slug(string $text): string
    {
        // Lowercase everything
        $text = mb_strtolower($text, 'UTF-8');

        // Replace anything that's not a letter, number, space, or hyphen with a space
        $text = preg_replace('/[^a-z0-9\s-]/', ' ', $text);

        // Replace multiple spaces or hyphens with a single hyphen
        $text = preg_replace('/[\s-]+/', '-', trim($text));

        return $text;
    }

    /**
     * Truncate a string to a maximum length and add a suffix.
     *
     * Examples:
     *   truncate('Hello World', 7)        => 'Hell...'
     *   truncate('Short', 10)             => 'Short'
     *   truncate('Hello World', 7, ' →') => 'Hell →'
     */
    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return $text;
        }

        // Cut to length minus the suffix length, so the total doesn't exceed $length
        $cutAt = $length - mb_strlen($suffix, 'UTF-8');
        return mb_substr($text, 0, max(0, $cutAt), 'UTF-8') . $suffix;
    }

    /**
     * Mask the middle of a string, keeping $visibleStart chars at the start
     * and $visibleEnd chars at the end. Used to safely display API keys.
     *
     * Examples:
     *   mask('sk_live_abc123xyz', 4, 4) => 'sk_l••••••••xyz'
     *   mask('hello@example.com', 2, 4) => 'he••••••••.com'
     */
    public static function mask(string $text, int $visibleStart = 4, int $visibleEnd = 4): string
    {
        $length = mb_strlen($text, 'UTF-8');

        // If the string is too short to mask, just return asterisks
        if ($length <= ($visibleStart + $visibleEnd)) {
            return str_repeat('•', $length);
        }

        $start  = mb_substr($text, 0, $visibleStart, 'UTF-8');
        $end    = mb_substr($text, -$visibleEnd, null, 'UTF-8');
        $middle = str_repeat('•', $length - $visibleStart - $visibleEnd);

        return $start . $middle . $end;
    }

    /**
     * Generate a random UUID v4.
     * UUIDs are used to name uploaded files to prevent collisions.
     *
     * Example output: '550e8400-e29b-41d4-a716-446655440000'
     */
    public static function uuid(): string
    {
        // Generate 16 random bytes
        $bytes = random_bytes(16);

        // Set version (4) and variant bits as per RFC 4122
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40); // version 4
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80); // variant

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Check if a string contains a substring.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Check if a string starts with a prefix.
     */
    public static function startsWith(string $haystack, string $prefix): bool
    {
        return str_starts_with($haystack, $prefix);
    }
}
