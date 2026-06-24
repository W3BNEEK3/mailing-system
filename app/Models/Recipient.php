<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Recipient
 *
 * A saved email contact.
 *
 * Usage:
 *   $recipient = Recipient::findBy('email', 'alice@example.com');
 *   echo $recipient->fullName();  // "Alice Smith"
 *   if ($recipient->isSuppressed()) { ... }
 */
class Recipient extends Model
{
    protected static string $table = 'recipients';

    protected array $fillable = [
        'first_name',
        'last_name',
        'email',
        'company',
        'notes',
        'is_suppressed',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Get the recipient's full name.
     * Falls back to email address if no name is stored.
     *
     * Usage:
     *   echo $recipient->fullName();  // "Alice Smith" or "alice@example.com"
     */
    public function fullName(): string
    {
        $parts = array_filter([(string)$this->first_name, (string)$this->last_name]);
        return implode(' ', $parts) ?: (string)$this->email;
    }

    /**
     * Check if this recipient has been suppressed (unsubscribed or blocked).
     * Suppressed recipients must not receive any further emails.
     */
    public function isSuppressed(): bool
    {
        return (bool)$this->is_suppressed;
    }


    /*
     * Build a static factory from a PDO row array.
     * Used by RecipientRepository to hydrate query results.
     */
    public static function fromArray(array $row): static
    {
        $obj                = new static();
        $obj->id            = (int)    ($row['id']           ?? 0);
        $obj->first_name    = (string) ($row['first_name']   ?? '');
        $obj->last_name     = (string) ($row['last_name']    ?? '');
        $obj->email         = (string) ($row['email']        ?? '');
        $obj->company       = (string) ($row['company']      ?? '');
        $obj->notes         = (string) ($row['notes']        ?? '');
        $obj->is_suppressed = (bool)   ($row['is_suppressed'] ?? false);
        $obj->created_at    = (string) ($row['created_at']   ?? '');

        return $obj;
    }
}
