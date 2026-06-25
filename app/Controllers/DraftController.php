<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Repositories\DraftRepository;

/**
 * DraftController
 *
 * Manages email drafts. All routes are HTMX-aware.
 *
 * Routes:
 *   GET  /drafts              → index()    — draft list partial for the drawer
 *   POST /drafts              → store()    — create a new draft (manual save)
 *   POST /drafts/autosave     → autosave() — create or update draft (auto-save)
 *   GET  /drafts/{id}/load    → load()     — populate compose form with a draft
 *   POST /drafts/{id}/delete  → destroy()  — delete a draft
 *
 * Auto-save vs manual save:
 *   autosave() is called every 60s by the compose page's HTMX polling div.
 *   It returns a tiny `<span>Saved HH:MM</span>` partial that replaces
 *   the `#autosave-status` element in the toolbar.
 *
 *   store() is called when the user explicitly clicks "Save Draft". It also
 *   upserts (same logic) but additionally triggers a toast notification.
 */
class DraftController extends BaseController
{
    public function __construct(
        private readonly DraftRepository $drafts,
    ) {}

    // ─── GET /drafts ───────────────────────────────────────────────────────────

    /**
     * Return the draft list as an HTMX partial for the slide-in drawer.
     *
     * Called by HTMX when the drafts drawer is opened, and after any
     * save/delete operation that affects the list.
     */
    public function index(Request $request): Response
    {
        $draftList = $this->drafts->findLatest();

        return $this->partial('compose/_draft-list', [
            'drafts' => $draftList,
        ]);
    }

    // ─── POST /drafts ──────────────────────────────────────────────────────────

    /**
     * Create or update a draft — triggered by the "Save Draft" button.
     *
     * Upserts based on draft_id in the POST body. On success, returns:
     *   - An HX-Trigger toast confirmation
     *   - An HX-Trigger to update the autosave status indicator
     *   - An HX-Trigger to refresh the draft list drawer
     *   - A JSON response with the draft ID (so the compose form can store it)
     *
     * The draft ID is set in a hidden input `<input name="draft_id">` in the
     * compose form and included in every subsequent autosave request.
     */
    public function store(Request $request): Response
    {
        $draft = $this->drafts->upsertAutosave($this->buildData($request));

        return Response::html('')
            ->htmxTrigger('showToast', [
                'type'    => 'success',
                'message' => 'Draft saved.',
            ])
            ->htmxTrigger('draftSaved', ['draftId' => $draft->id])
            ->htmxTrigger('refreshDraftList');
    }

    // ─── POST /drafts/autosave ─────────────────────────────────────────────────

    /**
     * Auto-save handler — called by HTMX every 60 seconds.
     *
     * Returns a tiny HTML partial: `<span>Saved HH:MM</span>`
     * This is swapped into `#autosave-status` in the compose toolbar.
     *
     * No toast is shown for auto-saves — it would be disruptive.
     * The status indicator gives silent feedback instead.
     */
    public function autosave(Request $request): Response
    {
        $draft = $this->drafts->upsertAutosave($this->buildData($request));

        $time = date('H:i');

        // Also fire draftSaved event so the form updates its hidden draft_id input
        return Response::html(
            "<span class=\"text-xs text-slate-400\">Saved at {$time}</span>"
        )->htmxTrigger('draftSaved', ['draftId' => $draft->id]);
    }

    // ─── GET /drafts/{id}/load ─────────────────────────────────────────────────

    /**
     * Load a draft into the compose form.
     *
     * Returns the full compose form partial pre-populated with the draft's
     * content. HTMX swaps this into `#compose-area`, replacing the current
     * form state.
     *
     * After loading, the hidden `draft_id` input in the form is set to the
     * draft's ID so subsequent auto-saves update the same row.
     */
    public function load(Request $request, int $id): Response
    {
        $draft = $this->findOrFail($id);

        return $this->partial('compose/_form', [
            'draft' => $draft,
        ]);
    }

    // ─── POST /drafts/{id}/delete ──────────────────────────────────────────────

    /**
     * Delete a draft.
     *
     * Returns an empty body so HTMX removes the draft row from the list
     * (hx-swap="outerHTML" on the list item).
     */
    public function destroy(Request $request, int $id): Response
    {
        $this->findOrFail($id);
        $this->drafts->delete($id);

        return Response::html('')
            ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Draft deleted.'])
            ->htmxTrigger('refreshDraftList');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Find a draft by ID or throw NotFoundException.
     */
    private function findOrFail(int $id): \App\Models\EmailDraft
    {
        $draft = $this->drafts->find($id);

        if (!$draft) {
            throw new NotFoundException("Draft #{$id} not found.");
        }

        return $draft;
    }

    /**
     * Build the data array for upsertAutosave() from the current request.
     *
     * The compose form posts all fields on every auto-save, so this method
     * reads them all and serialises arrays to JSON for storage.
     *
     * recipients comes in as a JSON string from the hidden chip input.
     * cc and bcc come in as JSON strings too.
     */
    private function buildData(Request $request): array
    {
        $post = $request->post();

        // Decode recipients from JSON (chip input stores them as JSON array)
        $recipients = json_decode($post['recipients'] ?? '[]', true);
        $cc         = json_decode($post['cc']          ?? '[]', true);
        $bcc        = json_decode($post['bcc']         ?? '[]', true);

        return [
            'draft_id'        => $post['draft_id']       ?? '',
            'subject'         => $post['subject']         ?? '',
            'recipients_json' => json_encode(is_array($recipients) ? $recipients : []),
            'template_id'     => $post['template_id'] !== '' ? ($post['template_id'] ?? null) : null,
            'body_html'       => $post['body_html']       ?? '',
            'email_logo_path' => $post['email_logo_path'] ?? null,
            'primary_color'   => $post['primary_color']   ?? null,
            'secondary_color' => $post['secondary_color'] ?? null,
            'reply_to'        => $post['reply_to']        ?? null,
            'cc_json'         => json_encode(is_array($cc)  ? $cc  : []),
            'bcc_json'        => json_encode(is_array($bcc) ? $bcc : []),
        ];
    }
}