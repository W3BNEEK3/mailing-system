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
}
