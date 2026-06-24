<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\RecipientRepository;
use App\Services\CsvImportService;

/*
 * RecipientController
 *
 * Manages the Recipients page (/recipients) and all contact CRUD operations.
 *
 * Routes served:
 *   GET  /recipients                  → index()      — paginated list + HTMX search
 *   GET  /recipients/create           → create()     — add-contact form
 *   POST /recipients                  → store()      — save new contact
 *   GET  /recipients/{id}/edit        → edit()       — edit-contact form
 *   POST /recipients/{id}             → update()     — save edits
 *   POST /recipients/{id}/delete      → destroy()    — delete contact
 *   POST /recipients/{id}/suppress    → suppress()   — mark as suppressed
 *   POST /recipients/{id}/unsuppress  → unsuppress() — reverse suppression
 *   GET  /recipients/import           → importPage() — CSV import form
 *   POST /recipients/import           → import()     — process CSV upload
 *
 * HTMX search:
 *   index() checks whether the request is an HTMX request with a `q` GET
 *   parameter. If so, it returns only the table body partial (_table-rows.php)
 *   instead of the full page. This implements live search without page reloads.
 *
 * HTMX delete / suppress:
 *   destroy() and suppress() return an empty 200 response on HTMX requests.
 *   The calling button uses hx-target="closest tr" hx-swap="outerHTML" so
 *   the row is removed or updated in the DOM without a full page reload.
 *
 * Pagination:
 *   index() reads the `page` GET parameter (default 1) and passes the
 *   paginated result to the view. The pagination component renders page links.
 */
class RecipientController extends BaseController
{
    public function __construct(
        private readonly RecipientRepository $recipients,
        private readonly CsvImportService    $csvImporter,
    ) {}

    // ─── GET /recipients ──────────────────────────────────────────────────────

    /*
     * List recipients with optional live search.
     *
     * On a regular GET:   renders the full recipients/index page.
     * On an HTMX GET:     renders only the _table-rows.php partial (for search).
     *
     * Query params:
     *   q    (string) — search query
     *   page (int)    — current page number
     */
    public function index(Request $request): Response
    {
        $search = trim($request->get('q', ''));
        $page   = max(1, (int) $request->get('page', 1));

        $paginated = $this->recipients->paginate(
            page:             $page,
            perPage:          20,
            search:           $search,
            includeSuppressed: false,
        );

        /* HTMX live-search: return only the table rows partial */
        if ($request->isHtmx() && $request->get('q') !== null) {
            return $this->partial('recipients/_table-rows', [
                'recipients' => $paginated['data'],
                'paginated'  => $paginated,
                'search'     => $search,
            ]);
        }

        return $this->view('recipients/index', [
            'pageTitle'  => 'Recipients',
            'recipients' => $paginated['data'],
            'paginated'  => $paginated,
            'search'     => $search,
            'groups'     => $this->recipients->allGroups(),
        ]);
    }

    // ─── GET /recipients/create ───────────────────────────────────────────────

    /*
     * Render the "Add Recipient" form.
     */
    public function create(Request $request): Response
    {
        return $this->view('recipients/create', [
            'pageTitle' => 'Add Recipient',
            'groups'    => $this->recipients->allGroups(),
        ]);
    }

    // ─── POST /recipients ─────────────────────────────────────────────────────

    /*
     * Validate and save a new recipient.
     *
     * Validation rules:
     *   email      — required, valid email format
     *   first_name — required (at least one name field must be present)
     *
     * After saving, group tags from the `tags` field are parsed and the
     * recipient is added to each named group (creating the group if needed).
     */
    public function store(Request $request): Response
    {
        try {
            $data = $this->validate($request->post(), [
                'first_name' => 'required|max:80',
                'last_name'  => 'max:80',
                'email'      => 'required|email|max:150',
                'company'    => 'max:150',
                'notes'      => 'max:1000',
            ]);
        } catch (ValidationException $e) {
            return $this->withErrors($e, $request->post());
        }

        /* Check for duplicate email */
        if ($this->recipients->findByEmail($data['email'])) {
            return $this->withErrors(
                new ValidationException(['email' => "A contact with the email '{$data['email']}' already exists."]),
                $request->post()
            );
        }

        /* Create the recipient */
        $recipient = $this->recipients->create($data);

        /* Process tags — comma-separated group names */
        $tags = trim($request->post('tags', ''));
        if ($tags !== '') {
            foreach (array_filter(array_map('trim', explode(',', $tags))) as $tagName) {
                $group = $this->recipients->findOrCreateGroup($tagName);
                $this->recipients->addToGroup((int) $recipient->id, (int) $group->id);
            }
        }

        $toast = ['type' => 'success', 'message' => "Contact '{$recipient->fullName()}' saved."];

        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', $toast)
                ->htmxRedirect('/recipients');
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/recipients');
    }

    // ─── GET /recipients/{id}/edit ────────────────────────────────────────────

    /*
     * Render the edit form for an existing recipient.
     */
    public function edit(Request $request, int $id): Response
    {
        $recipient = $this->findOrFail($id);
        $currentGroups = $this->recipients->groupsForRecipient($id);

        /* Build the pre-populated tags string */
        $currentTags = implode(', ', array_map(fn($g) => $g->name, $currentGroups));

        return $this->view('recipients/edit', [
            'pageTitle'    => 'Edit Recipient',
            'recipient'    => $recipient,
            'currentTags'  => $currentTags,
            'groups'       => $this->recipients->allGroups(),
        ]);
    }

    // ─── POST /recipients/{id} (with _method=PUT) ─────────────────────────────

    /*
     * Validate and save edits to an existing recipient.
     *
     * Email uniqueness check: if the email has changed, confirm the new email
     * is not already taken by another recipient.
     */
    public function update(Request $request, int $id): Response
    {
        $recipient = $this->findOrFail($id);

        try {
            $data = $this->validate($request->post(), [
                'first_name' => 'required|max:80',
                'last_name'  => 'max:80',
                'email'      => 'required|email|max:150',
                'company'    => 'max:150',
                'notes'      => 'max:1000',
            ]);
        } catch (ValidationException $e) {
            return $this->withErrors($e, $request->post());
        }

        /* Email uniqueness: only check if the email changed */
        if (strtolower($data['email']) !== strtolower((string) $recipient->email)) {
            if ($this->recipients->findByEmail($data['email'])) {
                return $this->withErrors(
                    new ValidationException(['email' => "The email '{$data['email']}' is already in use by another contact."]),
                    $request->post()
                );
            }
        }

        $this->recipients->update($id, $data);

        /*
         * Tag update: remove the recipient from ALL current groups,
         * then add them to the groups specified in the submitted tags field.
         * Simplest approach for MVP — a proper diff would be more efficient
         * but this is clear and correct.
         */
        $pdo = \App\Core\Database::getInstance()->getConnection();
        $pdo->prepare('DELETE FROM recipient_group_pivot WHERE recipient_id = ?')->execute([$id]);

        $tags = trim($request->post('tags', ''));
        if ($tags !== '') {
            foreach (array_filter(array_map('trim', explode(',', $tags))) as $tagName) {
                $group = $this->recipients->findOrCreateGroup($tagName);
                $this->recipients->addToGroup($id, (int) $group->id);
            }
        }

        /* Re-fetch to get the updated fullName() */
        $updated = $this->findOrFail($id);

        $toast = ['type' => 'success', 'message' => "Contact '{$updated->fullName()}' updated."];

        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', $toast)
                ->htmxRedirect('/recipients');
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/recipients');
    }

    // ─── POST /recipients/{id}/delete ─────────────────────────────────────────

    /*
     * Permanently delete a recipient.
     *
     * On HTMX request: returns an empty body. The calling button uses
     * hx-target="closest tr" hx-swap="outerHTML" to remove the table row.
     */
    public function destroy(Request $request, int $id): Response
    {
        $recipient = $this->findOrFail($id);
        $name      = $recipient->fullName();

        $this->recipients->delete($id);

        $toast = ['type' => 'success', 'message' => "Contact '{$name}' deleted."];

        if ($request->isHtmx()) {
            /* Empty body: HTMX will replace the <tr> with nothing (removes the row) */
            return Response::html('')->htmxTrigger('showToast', $toast);
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/recipients');
    }

    // ─── POST /recipients/{id}/suppress ──────────────────────────────────────

    /*
     * Mark a recipient as suppressed (opted out).
     *
     * Suppressed contacts appear in the list with a visual indicator but
     * are never included in sends. On HTMX: returns a partial updating the
     * row to reflect the suppressed state.
     */
    public function suppress(Request $request, int $id): Response
    {
        $recipient = $this->findOrFail($id);
        $this->recipients->suppress($id);

        $toast = ['type' => 'info', 'message' => "'{$recipient->fullName()}' has been suppressed."];

        if ($request->isHtmx()) {
            /* Re-render the single row with updated suppressed state */
            $updated = $this->findOrFail($id);
            return $this->partial('recipients/_table-row', ['recipient' => $updated])
                        ->htmxTrigger('showToast', $toast);
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/recipients');
    }

    // ─── POST /recipients/{id}/unsuppress ────────────────────────────────────

    /*
     * Restore a suppressed recipient so they can receive emails again.
     */
    public function unsuppress(Request $request, int $id): Response
    {
        $recipient = $this->findOrFail($id);
        $this->recipients->unsuppress($id);

        $toast = ['type' => 'success', 'message' => "'{$recipient->fullName()}' has been restored."];

        if ($request->isHtmx()) {
            $updated = $this->findOrFail($id);
            return $this->partial('recipients/_table-row', ['recipient' => $updated])
                        ->htmxTrigger('showToast', $toast);
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/recipients');
    }

    // ─── GET /recipients/import ───────────────────────────────────────────────

    /*
     * Render the CSV import form.
     */
    public function importPage(Request $request): Response
    {
        return $this->view('recipients/import', [
            'pageTitle' => 'Import Recipients',
        ]);
    }

    // ─── POST /recipients/import ──────────────────────────────────────────────

    /*
     * Process a CSV file upload and import contacts.
     *
     * The uploaded file must be text/csv or text/plain.
     * After processing, the controller returns either:
     *   - An HTMX partial replacing #import-results with the result summary.
     *   - A redirect with a flash toast for non-HTMX submits.
     */
    public function import(Request $request): Response
    {
        $file = $request->file('csv_file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $ve = new ValidationException(['csv_file' => 'Please select a CSV file to upload.']);
            return $this->withErrors($ve, $request->post());
        }

        /* Validate MIME — accept text/csv and text/plain (some OS report CSV as plain text) */
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mime     = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowedMimes = ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'];
        if (!in_array($mime, $allowedMimes, true)) {
            $ve = new ValidationException(['csv_file' => "Invalid file type '{$mime}'. Please upload a .csv file."]);
            return $this->withErrors($ve, $request->post());
        }

        /* Run the import */
        try {
            $importResult = $this->csvImporter->import($file['tmp_name']);
        } catch (\App\Exceptions\StorageException $e) {
            $ve = new ValidationException(['csv_file' => 'Could not read the CSV file: ' . $e->getMessage()]);
            return $this->withErrors($ve, $request->post());
        }

        /* Build result message */
        $imported = $importResult['imported'];
        $skipped  = $importResult['skipped'];
        $errors   = $importResult['errors'];

        $toastMessage = "{$imported} contact(s) imported.";
        if ($skipped > 0) {
            $toastMessage .= " {$skipped} skipped (already exist).";
        }
        if (!empty($errors)) {
            $toastMessage .= ' ' . count($errors) . ' row(s) had errors (see below).';
        }

        $toast = [
            'type'    => empty($errors) ? 'success' : 'warning',
            'message' => $toastMessage,
        ];

        /* HTMX: replace the results area with a detailed summary partial */
        if ($request->isHtmx()) {
            return $this->partial('recipients/_import-results', [
                'imported' => $imported,
                'skipped'  => $skipped,
                'errors'   => $errors,
            ])->htmxTrigger('showToast', $toast);
        }

        session()->flash('_toast', $toast);
        session()->flash('_import_result', $importResult);
        return $this->redirect('/recipients/import');
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /*
     * Load a recipient by ID or throw NotFoundException.
     *
     * @throws NotFoundException  If no recipient with the given ID exists
     */
    private function findOrFail(int $id): \App\Models\Recipient
    {
        $recipient = $this->recipients->find($id);
        if (!$recipient) {
            throw new NotFoundException("Recipient #{$id} not found.");
        }
        return $recipient;
    }
}
