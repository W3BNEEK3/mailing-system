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

    public int     $id;
    public string  $subject;
    public string  $recipientsJson;
    public int     $recipientCount;
    public ?int    $templateId;
    public string  $provider;
    public ?string $providerMessageId;
    public string  $status;
    public string  $bodyHtml;
    public ?string $sentAt;
    public string  $createdAt;

    public static function fromArray(array $row): static
    {
        $obj                    = new static();
        $obj->id                = (int)    $row['id'];
        $obj->subject           = (string) $row['subject'];
        $obj->recipientsJson    = (string) $row['recipients_json'];
        $obj->recipientCount    = (int)    $row['recipient_count'];
        $obj->templateId        = isset($row['template_id']) ? (int) $row['template_id'] : null;
        $obj->provider          = (string) $row['provider'];
        $obj->providerMessageId = $row['provider_message_id'] ?? null;
        $obj->status            = (string) $row['status'];
        $obj->bodyHtml          = (string) $row['body_html'];
        $obj->sentAt            = $row['sent_at'] ?? null;
        $obj->createdAt         = (string) $row['created_at'];

        return $obj;
    }

    /**
     * @return string[]
     */
    public function recipientsArray(): array
    {
        $decoded = json_decode($this->recipientsJson, associative: true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Return a short summary of recipients for the logs listing.
     * e.g. "alice@example.com" or "alice@example.com +4 more"
     */
    public function recipientSummary(): string
    {
        $list = $this->recipientsArray();

        if (empty($list)) return '—';

        $first = $list[0];
        $extra = count($list) - 1;

        return $extra > 0
            ? "{$first} +{$extra} more"
            : $first;
    }

    /**
     * Return a Tailwind CSS class pair for the status badge.
     */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'delivered' => 'bg-emerald-100 text-emerald-800',
            'sent'      => 'bg-blue-100    text-blue-800',
            'queued'    => 'bg-slate-100   text-slate-600',
            'bounced'   => 'bg-amber-100   text-amber-800',
            'failed'    => 'bg-red-100     text-red-800',
            default     => 'bg-slate-100   text-slate-600',
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
