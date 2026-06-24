<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\EmailDraft;

/**
 * DraftRepositoryInterface
 *
 * Contract for email draft data access.
 */
interface DraftRepositoryInterface
{
    /**
     * Get the most recently updated drafts (for the draft list panel).
     * Ordered by updated_at descending.
     */
    public function findLatest(int $limit = 20): array;

    /**
     * Create or update a draft (used by both autosave and manual save).
     *
     * If $data contains an 'id' key, updates the existing draft.
     * If no 'id' is present, creates a new draft.
     *
     * Returns the saved/updated EmailDraft instance.
     */
    public function upsertAutosave(array $data): EmailDraft;
}
