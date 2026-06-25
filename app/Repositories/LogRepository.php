<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmailDraft;
use App\Models\EmailLog;

/**
 * LogRepository
 *
 * Inserts and queries the email_logs and email_error_logs tables.
 *
 * Phase 8 only uses insertLog() and insertErrorLog().
 * Phase 10 adds paginate(), updateStatus(), and clearAll().
 */
class LogRepository
{
    // ─── Phase 8 Methods ──────────────────────────────────────────────────────

    /**
     * Record a successful send attempt in email_logs.
     *
     * Called by ComposeController::send() after EmailSendService::send()
     * returns a SendResult.
     *
     * @param array $data  Column values — see email_logs schema
     * @return int         The inserted log row ID
     */
    public function insertLog(array $data): int
    {
        $stmt = EmailLog::db()->prepare(
            'INSERT INTO email_logs
             (subject, recipients_json, recipient_count, template_id,
              provider, provider_message_id, status, body_html, sent_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $data['subject']              ?? '',
            $data['recipients_json']      ?? '[]',
            $data['recipient_count']      ?? 0,
            $data['template_id']          ?? null,
            $data['provider']             ?? 'resend',
            $data['provider_message_id']  ?? null,
            $data['status']               ?? 'sent',
            $data['body_html']            ?? '',
        ]);

        return (int) EmailLog::db()->lastInsertId();
    }

    /**
     * Record a failed send attempt in email_error_logs.
     *
     * @param array $data  Column values — see email_error_logs schema
     */
    public function insertErrorLog(array $data): void
    {
        $db = EmailLog::db();

        $stmt = $db->prepare(
            'INSERT INTO email_error_logs
             (subject, recipients_json, error_message, provider, template_id, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $data['subject']          ?? '',
            $data['recipients_json']  ?? '[]',
            $data['error_message']    ?? 'Unknown error',
            $data['provider']         ?? 'resend',
            $data['template_id']      ?? null,
        ]);
    }

    // ─── Phase 10 Methods (stubs — implemented in Phase 10) ─────────────────

    /**
     * Paginate email_logs or email_error_logs.
     * Full implementation in Phase 10.
     *
     * @param int    $page
     * @param string $type     'sent' | 'error' | 'received'
     * @param array  $filters  Keys: recipient, subject, status, date_from, date_to
     * @return array{rows: EmailLog[], total: int, page: int, lastPage: int}
     */
    public function paginate(int $page, string $type = 'sent', array $filters = []): array
    {
        // Stub — Phase 10 implementation
        return ['rows' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1];
    }

    /**
     * Update the status of a log row by provider_message_id.
     * Called by WebhookController in Phase 10.
     */
    public function updateStatus(string $providerMsgId, string $status): bool
    {
        // Stub — Phase 10 implementation
        return false;
    }
}