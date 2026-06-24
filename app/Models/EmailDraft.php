<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmailDraft
 *
 * An in-progress email composition saved by the user.
 * Drafts are auto-saved every 60 seconds and manually saved via "Save Draft".
 *
 * Usage:
 *   $draft      = EmailDraft::find(5);
 *   $recipients = $draft->recipientsArray(); // ['alice@example.com', 'Clients']
 */
class EmailDraft extends Model
{
    protected static string $table = 'email_drafts';

    protected array $fillable = [
        'subject',
        'recipients_json',
        'body_html',
        'template_id',
        'logo_override_path',
        'primary_color',
        'secondary_color',
        'language',
        'last_auto_saved_at',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decode the recipients_json column into a PHP array.
     *
     * The recipients_json column stores something like:
     *   '["alice@example.com", "bob@example.com", "Clients"]'
     *
     * Where email addresses are literal, and group names are resolved
     * server-side at send time via RecipientGroup::members().
     *
     * Returns an empty array if the column is null or invalid JSON.
     */
    public function recipientsArray(): array
    {
        if (empty($this->recipients_json)) {
            return [];
        }

        $decoded = json_decode((string)$this->recipients_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Get a short display label for the draft (used in the draft list).
     * Falls back to "(No Subject)" if the subject is empty.
     */
    public function displaySubject(): string
    {
        return trim((string)$this->subject) ?: '(No Subject)';
    }

    /**
     * Get the number of recipients as a human-readable string.
     */
    public function recipientSummary(): string
    {
        $count = count($this->recipientsArray());
        return $count === 1 ? '1 recipient' : "{$count} recipients";
    }
}
