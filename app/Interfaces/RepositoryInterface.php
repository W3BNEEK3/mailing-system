<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * RepositoryInterface
 *
 * Base contract for all repositories.
 * Defines the standard CRUD operations every repository must support.
 */
interface RepositoryInterface
{
    /**
     * Find a single record by its primary key.
     * Returns null if not found.
     */
    public function find(int $id): ?object;

    /**
     * Get all records.
     */
    public function all(): array;

    /**
     * Create a new record with the given data.
     * Returns the created model instance.
     */
    public function create(array $data): object;

    /**
     * Update a record by its primary key.
     * Returns true on success, false on failure.
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record by its primary key.
     * Returns true on success, false on failure.
     */
    public function delete(int $id): bool;
}
