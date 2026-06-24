<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmailDraft;
use App\Repositories\Contracts\DraftRepositoryInterface;
use App\Helpers\Date;

/**
 * DraftRepository
 *
 * Data access for email drafts.
 */
class DraftRepository implements DraftRepositoryInterface
{
    // ─── Interface implementation ─────────────────────────────────────────

    /**
     * Get the most recently updated drafts.
     * Shown in the draft list panel in the compose page.
     */
    public function findLatest(int $limit = 20): array
    {
        return EmailDraft::where([], 'updated_at', 'DESC', $limit);
    }

    /**
     * Create or update a draft (upsert).
     *
     * If $data['id'] is set and the draft exists → UPDATE
     * Otherwise → INSERT a new draft
     *
     * This is called by both manual "Save Draft" and the 60-second autosave.
     */
    public function upsertAutosave(array $data): EmailDraft
    {
        // Encode the recipients array to JSON if it's passed as an array
        if (isset($data['recipients']) && is_array($data['recipients'])) {
            $data['recipients_json'] = json_encode($data['recipients']);
            unset($data['recipients']);
        }

        // Check if we're updating an existing draft
        if (!empty($data['id'])) {
            $draftId = (int)$data['id'];
            $draft   = EmailDraft::find($draftId);

            if ($draft) {
                // Update the autosave timestamp and other fields
                $data['last_auto_saved_at'] = Date::now();
                unset($data['id']); // Don't try to update the primary key

                $draft->update($data);
                return $draft;
            }
        }

        // No existing draft found — create a new one
        unset($data['id']); // Remove id so INSERT generates a new one

        $data['last_auto_saved_at'] = Date::now();
        return EmailDraft::create($data);
    }

    // ─── Standard CRUD ────────────────────────────────────────────────────

    public function find(int $id): ?object
    {
        return EmailDraft::find($id);
    }

    public function all(): array
    {
        return EmailDraft::all('updated_at', 'DESC');
    }

    public function create(array $data): object
    {
        return EmailDraft::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $draft = EmailDraft::find($id);
        return $draft ? $draft->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $draft = EmailDraft::find($id);
        return $draft ? $draft->delete() : false;
    }
}
