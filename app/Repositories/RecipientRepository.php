<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Recipient;
use App\Models\RecipientGroup;
use App\Core\Database;
use App\Repositories\Contracts\RecipientRepositoryInterface;

/**
 * RecipientRepository
 *
 * Data access for recipient contacts and groups.
 */
class RecipientRepository implements RecipientRepositoryInterface
{
    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Search recipients by name, email, or company.
     *
     * Uses LIKE with a % wildcard on each field.
     * Only returns non-suppressed contacts.
     *
     * Usage:
     *   $results = $repo->search('alice');
     *   // Returns recipients where first_name, last_name, email, or company contains 'alice'
     */
    public function search(string $query): array
    {
        // Wrap in % for partial matching: 'alice' becomes '%alice%'
        $like = '%' . $query . '%';

        return Recipient::raw(
            "SELECT * FROM recipients
             WHERE is_suppressed = 0
               AND (
                   first_name LIKE ?
                   OR last_name  LIKE ?
                   OR email      LIKE ?
                   OR company    LIKE ?
               )
             ORDER BY first_name ASC, last_name ASC",
            [$like, $like, $like, $like]
        );
    }

    /**
     * Find a recipient by their email address.
     */
    public function findByEmail(string $email): ?Recipient
    {
        return Recipient::findBy('email', $email);
    }

    /**
     * Get all active recipients belonging to a named group.
     *
     * Used at send time to resolve group names entered in the To field.
     */
    public function findByGroup(string $groupName): array
    {
        $group = RecipientGroup::findBy('name', $groupName);

        if (!$group) {
            return [];
        }

        return $group->members();
    }

    /**
     * Bulk-insert an array of recipient records efficiently.
     *
     * Skips rows where the email already exists (INSERT IGNORE prevents duplicates).
     *
     * Uses a single SQL statement with multiple value tuples instead of
     * running one INSERT per row — much faster for large CSV imports.
     *
     * @param array $records Array of ['first_name' => ..., 'email' => ..., ...] arrays
     * @return int Number of newly inserted rows
     */
    public function bulkInsert(array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        $pdo = Database::getInstance()->getConnection();

        // Build placeholders: (?, ?, ?, ?, ?) for each record
        $placeholders = [];
        $values       = [];

        foreach ($records as $record) {
            // Ensure each record has all expected columns (use null for missing ones)
            $placeholders[] = '(?, ?, ?, ?, ?)';
            $values[]       = $record['first_name'] ?? null;
            $values[]       = $record['last_name']  ?? null;
            $values[]       = trim($record['email'] ?? '');
            $values[]       = $record['company']    ?? null;
            $values[]       = $record['notes']      ?? null;
        }

        // INSERT IGNORE skips rows that would violate the UNIQUE constraint on 'email'
        $sql = "INSERT IGNORE INTO recipients (first_name, last_name, email, company, notes)
                VALUES " . implode(', ', $placeholders);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // rowCount() returns only the rows that were actually inserted (not the skipped ones)
        return $stmt->rowCount();
    }

    /**
     * Mark a recipient as suppressed so they receive no further emails.
     */
    public function suppress(int $id): bool
    {
        $recipient = Recipient::find($id);
        if (!$recipient) {
            return false;
        }

        return $recipient->update(['is_suppressed' => 1]);
    }

    // ─── Standard CRUD ────────────────────────────────────────────────────

    public function find(int $id): ?object
    {
        return Recipient::find($id);
    }

    public function all(): array
    {
        return Recipient::where(['is_suppressed' => 0], 'first_name', 'ASC');
    }

    public function create(array $data): object
    {
        return Recipient::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $recipient = Recipient::find($id);
        return $recipient ? $recipient->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $recipient = Recipient::find($id);
        return $recipient ? $recipient->delete() : false;
    }

    // ─── Pagination ───────────────────────────────────────────────────────

    /**
     * Get a paginated list of recipients with optional search.
     *
     * @param int    $page    Current page (1-indexed)
     * @param int    $perPage Records per page
     * @param string $search  Optional search string
     */
    public function paginate(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        if ($search !== '') {
            // Search doesn't support pagination in the base model, so we handle it here
            $all     = $this->search($search);
            $total   = count($all);
            $offset  = ($page - 1) * $perPage;
            $data    = array_slice($all, $offset, $perPage);

            return [
                'data'      => $data,
                'total'     => $total,
                'page'      => $page,
                'per_page'  => $perPage,
                'last_page' => (int)ceil($total / $perPage),
            ];
        }

        return Recipient::paginate($perPage, $page, ['is_suppressed' => 0]);
    }
}
