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

    // ─── Phase 10 Methods ─────────────────────────────────────────────────────

    /**
     * Paginate email_logs (sent), email_error_logs (errors), or received_emails.
     *
     * @param int    $page
     * @param string $type     'sent' | 'error' | 'received'
     * @param array  $filters  Keys: recipient, subject, status, date_from, date_to
     * @return array{rows: array, total: int, page: int, lastPage: int, perPage: int}
     */
  public function paginate(int $page, string $type = 'sent', array $filters = []): array
{
    $perPage = 25;
    $offset  = ($page - 1) * $perPage;

    $db = EmailLog::db();

    if ($type === 'error') {
        [$where, $bindings] = $this->buildErrorFilters($filters);
        $countSql = "SELECT COUNT(*) FROM email_error_logs" . ($where ? " WHERE $where" : '');
        $rowSql   = "SELECT * FROM email_error_logs" . ($where ? " WHERE $where" : '')
                  . " ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
    } elseif ($type === 'received') {
        try {
            [$where, $bindings] = $this->buildReceivedFilters($filters);
            $countSql = "SELECT COUNT(*) FROM received_emails" . ($where ? " WHERE $where" : '');
            $rowSql   = "SELECT * FROM received_emails" . ($where ? " WHERE $where" : '')
                      . " ORDER BY received_at DESC LIMIT $perPage OFFSET $offset";
        } catch (\Throwable $e) {
            // Table doesn't exist yet — return empty gracefully
            return ['rows' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1, 'perPage' => $perPage];
        }
    } else {
        // 'sent' — default
        [$where, $bindings] = $this->buildSentFilters($filters);
        $countSql = "SELECT COUNT(*) FROM email_logs" . ($where ? " WHERE $where" : '');
        $rowSql   = "SELECT * FROM email_logs" . ($where ? " WHERE $where" : '')
                  . " ORDER BY sent_at DESC LIMIT $perPage OFFSET $offset";
    }

    try {
        // FIXED: Execute count query only once
        $countStmt = $db->prepare($countSql);
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $rowStmt = $db->prepare($rowSql);
        $rowStmt->execute($bindings);
        $rows = $rowStmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        return ['rows' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1, 'perPage' => $perPage];
    }

    return [
        'rows'     => $rows,
        'total'    => $total,
        'page'     => $page,
        'lastPage' => max(1, (int) ceil($total / $perPage)),
        'perPage'  => $perPage,
    ];
}

    /**
     * Find a single sent email log entry by ID.
     */
    public function findLog(int $id): ?array
    {
        $stmt = EmailLog::db()->prepare('SELECT * FROM email_logs WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Update the status of a log row by provider_message_id.
     * Called by WebhookController when Resend delivers a status event.
     */
    public function updateStatus(string $providerMsgId, string $status): bool
    {
        $stmt = EmailLog::db()->prepare(
            'UPDATE email_logs SET status = ? WHERE provider_message_id = ?'
        );
        $stmt->execute([$status, $providerMsgId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all records of the given log type.
     *
     * @param string $type 'sent' | 'error' | 'received'
     * @return int Number of rows deleted
     */
    public function clearAll(string $type): int
    {
        $table = match ($type) {
            'sent'     => 'email_logs',
            'error'    => 'email_error_logs',
            'received' => 'received_emails',
            default    => throw new \InvalidArgumentException("Unknown log type: $type"),
        };

        $stmt = EmailLog::db()->prepare("DELETE FROM $table");
        $stmt->execute();
        return $stmt->rowCount();
    }

    // ─── Private filter builders ──────────────────────────────────────────────

    private function buildSentFilters(array $filters): array
    {
        $where    = [];
        $bindings = [];

        if (!empty($filters['recipient'])) {
            $where[]    = 'recipients_json LIKE ?';
            $bindings[] = '%' . $filters['recipient'] . '%';
        }
        if (!empty($filters['subject'])) {
            $where[]    = 'subject LIKE ?';
            $bindings[] = '%' . $filters['subject'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[]    = 'status = ?';
            $bindings[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[]    = 'sent_at >= ?';
            $bindings[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]    = 'sent_at <= ?';
            $bindings[] = $filters['date_to'] . ' 23:59:59';
        }

        return [implode(' AND ', $where), $bindings];
    }

    private function buildErrorFilters(array $filters): array
    {
        $where    = [];
        $bindings = [];

        if (!empty($filters['recipient'])) {
            $where[]    = 'recipients_json LIKE ?';
            $bindings[] = '%' . $filters['recipient'] . '%';
        }
        if (!empty($filters['subject'])) {
            $where[]    = 'subject LIKE ?';
            $bindings[] = '%' . $filters['subject'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[]    = 'created_at >= ?';
            $bindings[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]    = 'created_at <= ?';
            $bindings[] = $filters['date_to'] . ' 23:59:59';
        }

        return [implode(' AND ', $where), $bindings];
    }

    private function buildReceivedFilters(array $filters): array
    {
        $where    = [];
        $bindings = [];

        if (!empty($filters['recipient'])) {
            $where[]    = 'from_email LIKE ?';
            $bindings[] = '%' . $filters['recipient'] . '%';
        }
        if (!empty($filters['subject'])) {
            $where[]    = 'subject LIKE ?';
            $bindings[] = '%' . $filters['subject'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[]    = 'received_at >= ?';
            $bindings[] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]    = 'received_at <= ?';
            $bindings[] = $filters['date_to'] . ' 23:59:59';
        }

        return [implode(' AND ', $where), $bindings];
    }
}