<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Html — HTML output helpers for views
 *
 * Usage in views:
 *   <?= Html::e($user->name) ?>                    — safe output
 *   <option <?= Html::selected($lang, 'en') ?>>   — conditional selected
 *   <input <?= Html::checked($active, 1) ?>>       — conditional checked
 *   <a class="<?= Html::active('/compose') ?>">    — active class
 */
class Html
{
    /**
     * HTML-escape a value. Alias of the global e() function.
     * Prevents XSS by converting special characters to HTML entities.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Output 'selected' if $value matches $current (for <select> options).
     *
     * Usage:
     *   <option value="en" <?= Html::selected($currentLang, 'en') ?>>English</option>
     */
    public static function selected(mixed $value, mixed $current): string
    {
        return $value == $current ? 'selected' : '';
    }

    /**
     * Output 'checked' if $value matches $current (for checkboxes/radio inputs).
     *
     * Usage:
     *   <input type="checkbox" <?= Html::checked($isActive, 1) ?>>
     */
    public static function checked(mixed $value, mixed $current): string
    {
        return $value == $current ? 'checked' : '';
    }

    /**
     * Return a CSS class string if the current request URI matches the given route.
     * Used to highlight the active sidebar link.
     *
     * Usage:
     *   <a class="nav-link <?= Html::active('/compose') ?>">Compose</a>
     *
     * If the current URI is '/compose', this outputs 'nav-link active'.
     * If not, this outputs 'nav-link '.
     */
    public static function active(string $route, string $activeClass = 'active'): string
    {
        $currentUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        return $currentUri === $route ? $activeClass : '';
    }

    /**
     * Build an HTMX attribute string from an array.
     *
     * Usage:
     *   Html::htmxAttrs([
     *       'hx-get'    => '/recipients',
     *       'hx-target' => '#recipient-table',
     *       'hx-swap'   => 'innerHTML',
     *   ])
     *
     * Output:
     *   hx-get="/recipients" hx-target="#recipient-table" hx-swap="innerHTML"
     */
    public static function htmxAttrs(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $key => $value) {
            $parts[] = static::e($key) . '="' . static::e($value) . '"';
        }
        return implode(' ', $parts);
    }
}
