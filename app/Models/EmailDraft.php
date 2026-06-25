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

    public int     $id;
    public string  $subject;
    public string  $recipientsJson;
    public ?int    $templateId;
    public string  $bodyHtml;
    public ?string $emailLogoPath;
    public ?string $primaryColor;
    public ?string $secondaryColor;
    public ?string $replyTo;
    public string  $ccJson;
    public string  $bccJson;
    public string  $createdAt;
    public string  $updatedAt;

    /**
     * Hydrate an EmailDraft from a PDO row.
     */
    public static function fromArray(array $row): static
    {
        $obj                  = new static();
        $obj->id              = (int)    $row['id'];
        $obj->subject         = (string) $row['subject'];
        $obj->recipientsJson  = (string) $row['recipients_json'];
        $obj->templateId      = isset($row['template_id']) ? (int) $row['template_id'] : null;
        $obj->bodyHtml        = (string) $row['body_html'];
        $obj->emailLogoPath   = $row['email_logo_path'] ?? null;
        $obj->primaryColor    = $row['primary_color']   ?? null;
        $obj->secondaryColor  = $row['secondary_color'] ?? null;
        $obj->replyTo         = $row['reply_to']        ?? null;
        $obj->ccJson          = (string) ($row['cc_json']  ?? '[]');
        $obj->bccJson         = (string) ($row['bcc_json'] ?? '[]');
        $obj->createdAt       = (string) $row['created_at'];
        $obj->updatedAt       = (string) $row['updated_at'];

        return $obj;
    }

    /**
     * Decode the recipients_json column to a PHP array.
     *
     * @return string[]  Array of recipient strings (emails or group names)
     */
    public function recipientsArray(): array
    {
        $decoded = json_decode($this->recipientsJson, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Decode the cc_json column.
     *
     * @return string[]
     */
    public function ccArray(): array
    {
        $decoded = json_decode($this->ccJson, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Decode the bcc_json column.
     *
     * @return string[]
     */
    public function bccArray(): array
    {
        $decoded = json_decode($this->bccJson, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Return a display label for the draft list.
     * Falls back to "No Subject" if subject is empty.
     */
    public function displaySubject(): string
    {
        return $this->subject !== '' ? $this->subject : 'No Subject';
    }

    /**
     * Return a human-readable "last saved" time string.
     * e.g. "Saved 3 minutes ago"
     */
    public function savedAgo(): string
    {
        $seconds = time() - strtotime($this->updatedAt);

        if ($seconds < 60)  return 'Saved just now';
        if ($seconds < 3600) return 'Saved ' . floor($seconds / 60) . 'm ago';
        if ($seconds < 86400) return 'Saved ' . floor($seconds / 3600) . 'h ago';
        return 'Saved ' . date('d M', strtotime($this->updatedAt));
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
