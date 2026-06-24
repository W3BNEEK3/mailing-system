<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * LogRepositoryInterface
 *
 * Contract for email log data access.
 */
interface LogRepositoryInterface
{
    /**
     * Get a paginated list of log entries.
     *
     * @param int    $page    Current page number (1-indexed)
     * @param string $type    One of: 'sent', 'error', 'received'
     * @param array  $filters Optional: ['subject' => ..., 'status' => ..., 'date_from' => ..., 'date_to' => ...]
     *
     * Returns:
     *   ['data' => [...], 'total' => n, 'page' => n, 'per_page' => n, 'last_page' => n]
     */
    public function paginate(int $page, string $type, array $filters = []): array;

    /**
     * Update the status of an email log entry by its provider message ID.
     * Called when a webhook is received from Resend.
     *
     * Returns true if a row was updated, false if no matching log was found.
     */
    public function updateStatus(string $providerMsgId, string $status): bool;

    /**
     * Delete all log entries of a given type.
     *
     * @param string $type One of: 'sent', 'error', 'received'
     *
     * Returns the number of rows deleted.
     */
    public function clearAll(string $type): int;
}
