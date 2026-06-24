<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Recipient;

/**
 * RecipientRepositoryInterface
 *
 * Contract for recipient contact data access.
 */
interface RecipientRepositoryInterface
{
    /**
     * Search recipients by name, email, or company.
     * Returns matching Recipient instances.
     */
    public function search(string $query): array;

    /**
     * Find a recipient by their email address.
     * Returns null if not found.
     */
    public function findByEmail(string $email): ?Recipient;

    /**
     * Get all active (non-suppressed) recipients in a named group.
     */
    public function findByGroup(string $groupName): array;

    /**
     * Bulk-insert an array of recipient data records.
     * Skips rows where the email already exists (INSERT IGNORE).
     *
     * Each record in $records should be:
     *   ['first_name' => ..., 'last_name' => ..., 'email' => ..., ...]
     *
     * Returns the number of newly inserted rows.
     */
    public function bulkInsert(array $records): int;

    /**
     * Mark a recipient as suppressed (unsubscribed).
     * Returns true on success.
     */
    public function suppress(int $id): bool;
}
