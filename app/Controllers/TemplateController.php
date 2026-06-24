<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\TemplateData;
use App\Exceptions\AppException;
use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\TemplateRepository;
use App\Services\FileUploadService;
use App\Services\TemplateRenderService;

/**
 * TemplateController
 *
 * Handles all CRUD operations for email templates.
 *
 * Routes served:
 *   GET  /settings/templates                → index()
 *   GET  /settings/templates/create         → create()
 *   POST /settings/templates                → store()
 *   GET  /settings/templates/{id}/edit      → edit()
 *   POST /settings/templates/{id}           → update()  (HTML form PUT override)
 *   POST /settings/templates/{id}/delete    → destroy() (HTML form DELETE override)
 *   POST /settings/templates/{id}/duplicate → duplicate()
 *   GET  /settings/templates/{id}/preview   → preview()
 *   POST /settings/templates/preview-draft  → previewDraft()
 *
 * Built-in protection:
 *   Built-in templates can be previewed, duplicated, and viewed.
 *   They CANNOT be edited (HTML content) or deleted.
 *   destroy() and update() check is_built_in and throw AppException if true.
 */
class TemplateController extends BaseController
{
    public function __construct(
        private readonly TemplateRepository    $templates,
        private readonly FileUploadService     $uploader,
        private readonly TemplateRenderService $renderer,
    ) {}

    // ─── GET /settings/templates ──────────────────────────────────────────────

    /**
     * Template gallery — lists built-in and custom templates in two sections.
     */
    public function index(Request $request): Response
    {
        return $this->view('settings/templates/index', [
            'pageTitle'  => 'Email Templates',
            'builtIn'    => $this->templates->findBuiltIn(),
            'custom'     => $this->templates->findCustom(),
        ]);
    }

    // ─── GET /settings/templates/create ──────────────────────────────────────

    /**
     * Render the "Add Template" form.
     */
    public function create(Request $request): Response
    {
        return $this->view('settings/templates/create', [
            'pageTitle'  => 'Add Template',
            'categories' => $this->categories(),
        ]);
    }

    // ─── POST /settings/templates ─────────────────────────────────────────────

    /**
     * Validate and persist a new template.
     *
     * Accepts either:
     *   A) A file upload via $_FILES['template_file'] (HTML or ZIP)
     *   B) Raw HTML pasted into $_POST['html_content']
     *
     * The two input modes are mutually exclusive: if a file is uploaded,
     * it takes precedence and the textarea content is ignored.
     */
    public function store(Request $request): Response
    {
        // ── 1. Validate scalar fields ──────────────────────────────────────

        try {
            $data = $this->validate($request->post(), [
                'name'     => 'required|max:150',
                'category' => 'required|in:' . implode(',', array_keys($this->categories())),
            ]);
        } catch (ValidationException $e) {
            return $this->withErrors($e);
        }

        // ── 2. Resolve HTML content ────────────────────────────────────────

        $htmlContent = '';
        $file        = $request->file('template_file');

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            // Mode A: File upload
            try {
                $storagePath = $this->uploader->uploadTemplate($file);
                $absolutePath = storage_path('uploads') . '/' . ltrim($storagePath, '/');
                $htmlContent  = file_get_contents($absolutePath);

                if ($htmlContent === false || $htmlContent === '') {
                    throw new AppException('Uploaded template file is empty or could not be read.');
                }
            } catch (\App\Exceptions\StorageException $e) {
                return $this->withError('template_file', $e->getMessage());
            }
        } else {
            // Mode B: Pasted HTML
            $htmlContent = trim($request->post('html_content', ''));
        }

        if ($htmlContent === '') {
            return $this->withError('html_content', 'Template content cannot be empty. Upload a file or paste HTML.');
        }

        // ── 3. Inspect for placeholder support ────────────────────────────

        $inspection = $this->renderer->inspect($htmlContent);

        // ── 4. Persist ────────────────────────────────────────────────────

        $template = $this->templates->create(new TemplateData(
            name:           $data['name'],
            category:       $data['category'],
            htmlContent:    $htmlContent,
            supportsLogo:   $inspection['supports_logo'],
            supportsColors: $inspection['supports_colors'],
        ));

        // ── 5. Respond ────────────────────────────────────────────────────

        $toast = ['type' => 'success', 'message' => "Template '{$template->name}' saved successfully."];

        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', $toast)
                ->htmxRedirect('/settings/templates');
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/settings/templates');
    }

    // ─── GET /settings/templates/{id}/edit ───────────────────────────────────

    /**
     * Edit form for a custom template.
     * Built-in templates redirect back with an error — they cannot be edited.
     */
    public function edit(Request $request, int $id): Response
    {
        $template = $this->findOrFail($id);

        if ($template->isBuiltIn) {
            session()->flash('_toast', [
                'type'    => 'error',
                'message' => 'Built-in templates cannot be edited. Duplicate it first to create a customisable copy.',
            ]);
            return $this->redirect('/settings/templates');
        }

        return $this->view('settings/templates/edit', [
            'pageTitle'  => 'Edit Template',
            'template'   => $template,
            'categories' => $this->categories(),
        ]);
    }

    // ─── POST /settings/templates/{id} (with _method=PUT) ────────────────────

    /**
     * Validate and update an existing custom template.
     */
    public function update(Request $request, int $id): Response
    {
        $template = $this->findOrFail($id);

        if ($template->isBuiltIn) {
            throw new AppException('Built-in templates cannot be modified.');
        }

        try {
            $data = $this->validate($request->post(), [
                'name'     => 'required|max:150',
                'category' => 'required|in:' . implode(',', array_keys($this->categories())),
            ]);
        } catch (ValidationException $e) {
            return $this->withErrors($e);
        }

        // Resolve updated HTML — same logic as store()
        $htmlContent = '';
        $file        = $request->file('template_file');

        if ($file && $file['error'] === UPLOAD_ERR_OK) {
            try {
                $storagePath  = $this->uploader->uploadTemplate($file);
                $absolutePath = storage_path('uploads') . '/' . ltrim($storagePath, '/');
                $htmlContent  = (string) file_get_contents($absolutePath);
            } catch (\App\Exceptions\StorageException $e) {
                return $this->withError('template_file', $e->getMessage());
            }
        }

        // If no new file, keep the existing HTML unless the user pasted new content
        if ($htmlContent === '') {
            $pasted = trim($request->post('html_content', ''));
            $htmlContent = $pasted !== '' ? $pasted : $template->htmlContent;
        }

        $inspection = $this->renderer->inspect($htmlContent);

        $updated = $this->templates->update($id, new TemplateData(
            name:           $data['name'],
            category:       $data['category'],
            htmlContent:    $htmlContent,
            supportsLogo:   $inspection['supports_logo'],
            supportsColors: $inspection['supports_colors'],
        ));

        $toast = ['type' => 'success', 'message' => "Template '{$updated->name}' updated."];

        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', $toast)
                ->htmxRedirect('/settings/templates');
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/settings/templates');
    }

    // ─── POST /settings/templates/{id}/delete ────────────────────────────────

    /**
     * Delete a custom template.
     *
     * On HTMX request: returns a 200 with HX-Trigger to remove the card from
     * the DOM without a full page reload — the card element uses hx-target="closest .template-card"
     * with hx-swap="outerHTML" and receives an empty response, which removes it.
     */
    public function destroy(Request $request, int $id): Response
    {
        $template = $this->findOrFail($id);

        if ($template->isBuiltIn) {
            throw new AppException('Built-in templates cannot be deleted.');
        }

        $this->templates->delete($id);

        $toast = ['type' => 'success', 'message' => "Template '{$template->name}' deleted."];

        if ($request->isHtmx()) {
            // Return empty body — HTMX will swap the card's outerHTML with nothing (removing it)
            return Response::html('')
                ->htmxTrigger('showToast', $toast);
        }

        session()->flash('_toast', $toast);
        return $this->redirect('/settings/templates');
    }

    // ─── POST /settings/templates/{id}/duplicate ─────────────────────────────

    /**
     * Duplicate any template (built-in or custom) and open the copy for editing.
     */
    public function duplicate(Request $request, int $id): Response
    {
        $this->findOrFail($id); // confirm original exists

        $copy  = $this->templates->duplicate($id);
        $toast = ['type' => 'success', 'message' => "Template duplicated as '{$copy->name}'."];

        if ($request->isHtmx()) {
            return Response::html('')
                ->htmxTrigger('showToast', $toast)
                ->htmxRedirect("/settings/templates/{$copy->id}/edit");
        }

        session()->flash('_toast', $toast);
        return $this->redirect("/settings/templates/{$copy->id}/edit");
    }

    // ─── GET /settings/templates/{id}/preview ────────────────────────────────

    /**
     * Return the rendered template HTML as a partial for the preview modal.
     *
     * The caller (the preview modal JS) sets this as the iframe's srcdoc attribute.
     * We return raw HTML, not a layout-wrapped response.
     */
    public function preview(Request $request, int $id): Response
    {
        $template = $this->findOrFail($id);
        $rendered = $this->renderer->renderWithGlobalContext($template->htmlContent);

        return Response::html($rendered);
    }

    // ─── POST /settings/templates/preview-draft ───────────────────────────────

    /**
     * Render pasted HTML (from the create/edit textarea) for the live preview iframe.
     *
     * Called by HTMX on every keystroke (debounced via JS). Accepts raw HTML
     * from $_POST['html_content'] and returns it rendered with the global context.
     *
     * Security: the rendered HTML goes into an iframe srcdoc attribute via JS —
     * it is sandboxed and never injected directly into the page DOM.
     */
    public function previewDraft(Request $request): Response
    {
        $raw      = $request->post('html_content', '');
        $rendered = $this->renderer->renderWithGlobalContext($raw);

        return Response::html($rendered);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Load a template by ID or throw NotFoundException.
     */
    private function findOrFail(int $id): \App\Models\EmailTemplate
    {
        $template = $this->templates->findById($id);

        if (!$template) {
            throw new NotFoundException("Template #{$id} not found.");
        }

        return $template;
    }

    /**
     * Centrally defined category options used in validation and view dropdowns.
     *
     * @return array<string, string>  slug => label
     */
    private function categories(): array
    {
        return [
            'newsletter'    => 'Newsletter',
            'transactional' => 'Transactional',
            'promotional'   => 'Promotional',
            'general'       => 'General',
        ];
    }

    /**
     * Return a Response with a single field error and re-render the current form.
     *
     * Used when validation of individual fields (file upload, html_content) fails
     * outside the main validate() call.
     *
     * @param string $field  The field name to attach the error to
     * @param string $message
     */
    private function withError(string $field, string $message): Response
    {
        $ve = new ValidationException([$field => [$message]]);
        return $this->withErrors($ve);
    }
}
