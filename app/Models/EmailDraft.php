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
 * $draft      = EmailDraft::find(5);
 * $recipients = $draft->recipientsArray(); // ['alice@example.com', 'Clients']
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

    // ─── Type-hinted property accessors ───────────────────────────────────
    // These are documentation helpers — PHP reads the actual values
    // from $this->attributes via the __get magic method in Model.

    // public int     $id;
    // public string  $subject;
    // public string  $recipients_json;
    // public ?int    $template_id;
    // public string  $body_html;
    // public ?string $email_logo_path;
    // public ?string $primary_color;
    // public ?string $secondary_color;
    // public ?string $reply_to;
    // public string  $cc_json;
    // public string  $bcc_json;
    // public string  $created_at;
    // public string  $updated_at;
    // public string  $last_auto_saved_at;

    /**
     * Decode the recipients_json column to a PHP array.
     *
     * @return string[]  Array of recipient strings (emails or group names)
     */
    public function recipientsArray(): array
    {
        $decoded = json_decode((string)$this->recipients_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Decode the cc_json column.
     *
     * @return string[]
     */
    public function ccArray(): array
    {
        $decoded = json_decode((string)$this->cc_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Decode the bcc_json column.
     *
     * @return string[]
     */
    public function bccArray(): array
    {
        $decoded = json_decode((string)$this->bcc_json, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Return a human-readable "last saved" time string.
     * e.g. "Saved 3 minutes ago"
     */
    public function savedAgo(): string
    {
        $seconds = time() - strtotime((string)$this->updated_at);

        if ($seconds < 60)  return 'Saved just now';
        if ($seconds < 3600) return 'Saved ' . floor($seconds / 60) . 'm ago';
        if ($seconds < 86400) return 'Saved ' . floor($seconds / 3600) . 'h ago';
        return 'Saved ' . date('d M', strtotime((string)$this->updated_at));
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