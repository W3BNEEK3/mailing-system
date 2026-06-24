<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\Date;

/**
 * EmailLog
 *
 * Records every email send attempt and inbound email received.
 * Status is updated by webhook events from Resend.
 *
 * Usage:
 *   $log        = EmailLog::find(10);
 *   $recipients = $log->recipientsArray();
 *   echo $log->statusBadgeClass(); // CSS class for the status badge
 */
class EmailLog extends Model
{
    protected static string $table = 'email_logs';

    protected array $fillable = [
        'direction',
        'recipients_json',
        'subject',
        'body_html',
        'template_id',
        'provider',
        'provider_msg_id',
        'status',
    ];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decode the recipients_json column into a PHP array.
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
     * Get a Tailwind CSS colour class for the status badge in the logs view.
     *
     * Usage in views:
     *   <span class="badge <?= e($log->statusBadgeClass()) ?>">
     *       <?= e($log->status) ?>
     *   </span>
     */
    public function statusBadgeClass(): string
    {
        return match ((string)$this->status) {
            'sent'      => 'bg-blue-100 text-blue-800',
            'delivered' => 'bg-green-100 text-green-800',
            'opened'    => 'bg-teal-100 text-teal-800',
            'failed'    => 'bg-red-100 text-red-800',
            'bounced'   => 'bg-orange-100 text-orange-800',
            'queued'    => 'bg-gray-100 text-gray-800',
            default     => 'bg-gray-100 text-gray-600',
        };
    }

    /**
     * Get a human-readable time since this email was sent.
     * e.g. "2 hours ago", "just now"
     */
    public function sentAgo(): string
    {
        return Date::diffForHumans((string)$this->sent_at);
    }

    /**
     * Check if this log entry is for a sent (outbound) email.
     */
    public function isSent(): bool
    {
        return $this->direction === 'sent';
    }

    /**
     * Check if this log entry is for a received (inbound) email.
     */
    public function isReceived(): bool
    {
        return $this->direction === 'received';
    }
}
