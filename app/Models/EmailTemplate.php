<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmailTemplate
 *
 * Represents a stored email template (built-in or custom).
 *
 * supports_logo and supports_colors are boolean flags detected at upload
 * by scanning the HTML for {{LOGO_URL}} and {{PRIMARY_COLOR}} tokens.
 * They control which toolbar overrides are available in the compose page.
 *
 * Usage:
 *   $template = EmailTemplate::find(1);
 *   echo $template->name;
 *   if ($template->supportsLogo()) { ... }
 *   if ($template->isBuiltIn()) { ... }
 */
class EmailTemplate extends Model
{
    protected static string $table = 'email_templates';

    protected array $fillable = [
        'name',
        'category',
        'html_content',
        'thumbnail_path',
        'is_built_in',
        'supports_logo',
        'supports_colors',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Check if this template supports logo injection.
     * True means the HTML contains {{LOGO_URL}}.
     */
    public function supportsLogo(): bool
    {
        return (bool)$this->supports_logo;
    }

    /**
     * Check if this template supports colour injection.
     * True means the HTML contains {{PRIMARY_COLOR}}.
     */
    public function supportsColors(): bool
    {
        return (bool)$this->supports_colors;
    }

    /**
     * Check if this is a built-in template (shipped with the app).
     * Built-in templates cannot be deleted — only duplicated.
     */
    public function isBuiltIn(): bool
    {
        return (bool)$this->is_built_in;
    }
}
