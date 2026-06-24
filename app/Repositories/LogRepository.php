<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmailLog;
use App\Models\EmailErrorLog;
use App\Core\Database;
use App\Repositories\Contracts\LogRepositoryInterface;

/**
 * LogRepository
 *
 * Data access for email logs (sent, errors, received).
 */
class LogRepository implements LogRepositoryInterface
{
    private const PER_PAGE = 25;

    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get paginated log entries with optional filters.
     *
     * $type determines which table/dataset to query:
     *   'sent'     → email_logs WHERE direction = 'sent'
     *   'received' → email_logs WHERE direction = 'received'
     *   'error'    → email_error_logs
     *
     * $filters can include:
     *   'subject'   → partial match on subject
     *   'status'    → exact match on status (sent/delivered/etc.)
     *   'date_from' → records on or after this date (Y-m-d)
     *   'date_to'   → records on or before this date (Y-m-d)
     */
    public function paginate(int $page, string $type, array $filters = []): array
    {
        $page    = max(1, $page);
        $perPage = static::PER_PAGE;
        $offset  = ($page - 1) * $perPage;

        if ($type === 'error') {
            return $this->paginateErrors($page, $perPage, $offset, $filters);
        }

        return $this->paginateSentOrReceived($type, $page, $perPage, $offset, $filters);
    }

    /**
     * Update the delivery status of a sent email log.
     * Called when Resend sends a webhook (delivered, bounced, opened).
     */
    public function updateStatus(string $providerMsgId, string $status): bool
    {
        $pdo  = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(
            "UPDATE email_logs SET status = ? WHERE provider_msg_id = ?"
        );
        $stmt->execute([$status, $providerMsgId]);

        // Returns true if exactly one row was updated
        return $stmt->rowCount() > 0;
    }

    /**
     * Delete all log entries of a given type.
     */
    public function clearAll(string $type): int
    {
        $pdo = Database::getInstance()->getConnection();

        if ($type === 'error') {
            $stmt = $pdo->query("DELETE FROM email_error_logs");
        } elseif ($type === 'received') {
            $stmt = $pdo->prepare("DELETE FROM email_logs WHERE direction = 'received'");
            $stmt->execute();
        } else {
            // 'sent' — delete sent direction logs
            $stmt = $pdo->prepare("DELETE FROM email_logs WHERE direction = 'sent'");
            $stmt->execute();
        }

        return $stmt->rowCount();
    }

    // ─── Standard CRUD (for email_logs) ──────────────────────────────────

    public function find(int $id): ?object
    {
        return EmailLog::find($id);
    }

    public function all(): array
    {
        return EmailLog::all('sent_at', 'DESC');
    }

    public function create(array $data): object
    {
        return EmailLog::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $log = EmailLog::find($id);
        return $log ? $log->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $log = EmailLog::find($id);
        return $log ? $log->delete() : false;
    }

    // ─── Internal helpers ─────────────────────────────────────────────────

    /**
     * Paginate sent or received email logs with optional filters.
     */
    private function paginateSentOrReceived(
        string $type,
        int    $page,
        int    $perPage,
        int    $offset,
        array  $filters
    ): array {
        $pdo    = Database::getInstance()->getConnection();
        $wheres = ['direction = ?'];
        $values = [$type];

        // Apply optional filters
        if (!empty($filters['subject'])) {
            $wheres[] = 'subject LIKE ?';
            $values[] = '%' . $filters['subject'] . '%';
        }
        if (!empty($filters['status'])) {
            $wheres[] = 'status = ?';
            $values[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $wheres[] = 'DATE(sent_at) >= ?';
            $values[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $wheres[] = 'DATE(sent_at) <= ?';
            $values[] = $filters['date_to'];
        }

        $where = 'WHERE ' . implode(' AND ', $wheres);

        // Count total matching rows
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM email_logs {$where}");
        $countStmt->execute($values);
        $total = (int)$countStmt->fetchColumn();

        // Fetch the current page
        $dataStmt = $pdo->prepare(
            "SELECT * FROM email_logs {$where}
             ORDER BY sent_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($values);
        $rows = $dataStmt->fetchAll();

        // Hydrate each row into an EmailLog model instance
        $data = array_map(
            fn($row) => EmailLog::rawOne("SELECT * FROM email_logs WHERE id = ?", [$row['id']]),
            $rows
        );

        return [
            'data'      => array_filter($data),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Paginate error logs with optional filters.
     */
    private function paginateErrors(int $page, int $perPage, int $offset, array $filters): array
    {
        $pdo    = Database::getInstance()->getConnection();
        $wheres = ['1 = 1'];
        $values = [];

        if (!empty($filters['date_from'])) {
            $wheres[] = 'DATE(created_at) >= ?';
            $values[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $wheres[] = 'DATE(created_at) <= ?';
            $values[] = $filters['date_to'];
        }

        $where = 'WHERE ' . implode(' AND ', $wheres);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM email_error_logs {$where}");
        $countStmt->execute($values);
        $total = (int)$countStmt->fetchColumn();

        $dataStmt = $pdo->prepare(
            "SELECT * FROM email_error_logs {$where}
             ORDER BY created_at DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $dataStmt->execute($values);
        $rows = $dataStmt->fetchAll();

        $data = array_map(
            fn($row) => EmailErrorLog::rawOne("SELECT * FROM email_error_logs WHERE id = ?", [$row['id']]),
            $rows
        );

        return [
            'data'      => array_filter($data),
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => (int)ceil($total / $perPage),
        ];
    }
}
