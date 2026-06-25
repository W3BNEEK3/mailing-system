<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\EmailPayload;
use App\DTOs\RenderContext;
use App\Exceptions\NotFoundException;
use App\Exceptions\ProviderException;
use App\Exceptions\ValidationException;
use App\Repositories\DraftRepository;
use App\Repositories\LogRepository;
use App\Repositories\TemplateRepository;
use App\Services\EmailSendService;
use App\Services\RecipientResolverService;
use App\Services\TemplateRenderService;

/**
 * ComposeController
 *
 * Handles the email composition page and the send flow.
 *
 * Routes:
 *   GET  /compose                   → index()          — main compose page
 *   POST /compose/send              → send()           — validate, resolve, render, send
 *   POST /compose/preview           → preview()        — render body for preview modal
 *   POST /compose/load-template     → loadTemplate()   — load a template HTML into editor
 *   GET  /compose/recipient-hints   → recipientHints() — autocomplete suggestions
 *
 * Send flow (in order):
 *   1. Validate: recipients, subject, body all non-empty
 *   2. Resolve recipients: expand group names, filter suppressed contacts
 *   3. Build RenderContext: merge global settings with any per-email overrides
 *   4. Render body HTML: substitute all {{TOKEN}} placeholders
 *   5. Build EmailPayload DTO
 *   6. Send via EmailSendService (delegates to active provider)
 *   7. Log success → email_logs
 *   8. On failure → log to email_error_logs, return error toast
 */
class ComposeController extends BaseController
{
    public function __construct(
        private readonly TemplateRepository      $templates,
        private readonly DraftRepository         $drafts,
        private readonly LogRepository           $logs,
        private readonly TemplateRenderService   $renderer,
        private readonly EmailSendService        $sendService,
        private readonly RecipientResolverService $resolver,
    ) {}

    // ─── GET /compose ──────────────────────────────────────────────────────────

    /**
     * Render the main compose page.
     *
     * Passes the template list (for the selector dropdown) and the global
     * context values (for pre-filling the colour override pickers) to the view.
     */
    public function index(Request $request): Response
    {
        $globalContext = $this->renderer->buildGlobalContext();

        return $this->view('compose/index', [
            'pageTitle'      => 'Compose — ' . setting('site_name', 'Emirates'),
            'templates'      => $this->templates->findAll(),
            'globalContext'  => $globalContext,
            'draft'          => null, // No draft pre-loaded on fresh open
        ]);
    }

    // ─── POST /compose/send ────────────────────────────────────────────────────

    /**
     * Validate, resolve, render, send, and log an email.
     *
     * All validation errors, recipient resolution warnings, and send failures
     * are returned as HTMX-triggered toasts — no full page reload occurs.
     *
     * On success:
     *   - Email is sent via the active provider.
     *   - A row is inserted into email_logs.
     *   - The compose form is reset to a fresh state.
     *   - A success toast is shown.
     *   - The draft is deleted (if one was active).
     *
     * On failure:
     *   - A row is inserted into email_error_logs.
     *   - An error toast is shown.
     *   - The form state is preserved (user can correct and retry).
     */
    public function send(Request $request): Response
    {
        $post = $request->post();

        // ── 1. Input validation ────────────────────────────────────────────

        // Decode recipients from the JSON hidden input (set by chip input JS)
        $rawRecipients = json_decode($post['recipients'] ?? '[]', true);
        $rawRecipients = is_array($rawRecipients) ? $rawRecipients : [];

        $subject  = trim($post['subject']   ?? '');
        $bodyHtml = trim($post['body_html'] ?? '');
        $replyTo  = trim($post['reply_to']  ?? '');
        $rawCc    = json_decode($post['cc']  ?? '[]', true);
        $rawBcc   = json_decode($post['bcc'] ?? '[]', true);

        $errors = [];

        if (empty($rawRecipients)) {
            $errors[] = 'Add at least one recipient before sending.';
        }

        if ($subject === '') {
            $errors[] = 'Subject cannot be empty.';
        }

        if ($bodyHtml === '') {
            $errors[] = 'Email body cannot be empty. Select a template or write your content.';
        }

        if (!empty($errors)) {
            return Response::html('')
                ->htmxTrigger('showToast', [
                    'type'    => 'error',
                    'message' => implode(' ', $errors),
                ]);
        }

        // ── 2. Resolve recipients ──────────────────────────────────────────

        $resolved = $this->resolver->resolve($rawRecipients);

        if ($resolved->isEmpty()) {
            return Response::html('')
                ->htmxTrigger('showToast', [
                    'type'    => 'error',
                    'message' => 'No deliverable recipients after filtering suppressed contacts. '
                               . $resolved->warningMessage(),
                ]);
        }

        // ── 3. Build RenderContext (global + per-email overrides) ──────────

        $globalCtx = $this->renderer->buildGlobalContext();

        // Per-email overrides: if the user changed the logo or colours in
        // the compose toolbar, those values are submitted as form fields.
        // Non-empty values override the global settings for this send only.
        $emailLogoPath   = $post['email_logo_path']  ?? null;
        $primaryColor    = $post['primary_color']    ?? null;
        $secondaryColor  = $post['secondary_color']  ?? null;

        // Resolve logo URL: per-email override takes precedence
        $logoUrl = $emailLogoPath
            ? url('/storage/uploads/' . ltrim($emailLogoPath, '/'))
            : $globalCtx->logoUrl;

        $renderCtx = new RenderContext(
            logoUrl:        $logoUrl,
            primaryColor:   $primaryColor    ?: $globalCtx->primaryColor,
            secondaryColor: $secondaryColor  ?: $globalCtx->secondaryColor,
            senderName:     $globalCtx->senderName,
            senderEmail:    $globalCtx->senderEmail,
            replyTo:        $replyTo ?: null,
        );

        // ── 4. Render the body HTML ────────────────────────────────────────

        $renderedHtml = $this->renderer->render($bodyHtml, $renderCtx);

        // ── 5. Build payload ───────────────────────────────────────────────

        $templateId = isset($post['template_id']) && $post['template_id'] !== ''
            ? (int) $post['template_id']
            : null;

        $payload = new EmailPayload(
            fromName:   $globalCtx->senderName,
            fromEmail:  $globalCtx->senderEmail,
            recipients: $resolved->emails,
            subject:    $subject,
            html:       $renderedHtml,
            replyTo:    $replyTo,
            cc:         is_array($rawCc)  ? $rawCc  : [],
            bcc:        is_array($rawBcc) ? $rawBcc : [],
        );

        // ── 6. Send ────────────────────────────────────────────────────────

        // Determine which provider is active (for logging purposes)
        $activeCredential  = null;
        $providerName      = 'unknown';

        try {
            $activeCredential = method_exists($this->sendService, 'getActiveCredential')
                ? $this->sendService->getActiveCredential()
                : null;
        } catch (\Throwable) {}

        $logData = [
            'subject'          => $subject,
            'recipients_json'  => json_encode($resolved->emails),
            'recipient_count'  => count($resolved->emails),
            'template_id'      => $templateId,
            'provider'         => $providerName,
            'body_html'        => $renderedHtml,
        ];

        try {
            $result = $this->sendService->send($payload);

            // ── 7. Log success ─────────────────────────────────────────────

            $this->logs->insertLog(array_merge($logData, [
                'provider_message_id' => $result->messageId,
                'status'              => 'sent',
            ]));

            // Delete the draft if one was active for this compose session
            $draftId = isset($post['draft_id']) && $post['draft_id'] !== ''
                ? (int) $post['draft_id']
                : null;

            if ($draftId !== null) {
                $this->drafts->delete($draftId);
            }

            // Build success message (include suppression warning if relevant)
            $successMsg = 'Email sent to ' . count($resolved->emails)
                . ' recipient' . (count($resolved->emails) > 1 ? 's' : '') . '.';

            if ($resolved->warningMessage() !== '') {
                $successMsg .= ' Note: ' . $resolved->warningMessage();
            }

            // Reset the compose form and show success toast
            return $this->partial('compose/_form-reset')
                ->htmxTrigger('showToast', ['type' => 'success', 'message' => $successMsg])
                ->htmxTrigger('composeSent');  // JS listener clears draft_id

        } catch (ProviderException $e) {

            // ── 8. Log failure ─────────────────────────────────────────────

            $this->logs->insertErrorLog(array_merge($logData, [
                'error_message' => $e->getMessage(),
            ]));

            return Response::html('')
                ->htmxTrigger('showToast', [
                    'type'    => 'error',
                    'message' => 'Send failed: ' . $e->getMessage(),
                ]);

        } catch (\RuntimeException $e) {

            // No active provider configured
            return Response::html('')
                ->htmxTrigger('showToast', [
                    'type'    => 'error',
                    'message' => $e->getMessage(),
                ]);
        }
    }

    // ─── POST /compose/preview ─────────────────────────────────────────────────

    /**
     * Render the current body for the preview modal.
     *
     * The compose form's "Preview" button POSTs the current body_html and
     * optional per-email overrides. This method renders the tokens and returns
     * the HTML for display in a sandboxed iframe inside the preview modal.
     *
     * No layout wrapper — raw HTML only.
     */
    public function preview(Request $request): Response
    {
        $post     = $request->post();
        $bodyHtml = $post['body_html'] ?? '';
        $replyTo  = $post['reply_to']  ?? '';

        // Build context with per-email overrides (same logic as send())
        $globalCtx     = $this->renderer->buildGlobalContext();
        $emailLogoPath = $post['email_logo_path'] ?? null;
        $primaryColor  = $post['primary_color']   ?? null;
        $secondaryColor = $post['secondary_color'] ?? null;

        $logoUrl = $emailLogoPath
            ? url('/storage/uploads/' . ltrim($emailLogoPath, '/'))
            : $globalCtx->logoUrl;

        $ctx = new RenderContext(
            logoUrl:        $logoUrl,
            primaryColor:   $primaryColor   ?: $globalCtx->primaryColor,
            secondaryColor: $secondaryColor ?: $globalCtx->secondaryColor,
            senderName:     $globalCtx->senderName,
            senderEmail:    $globalCtx->senderEmail,
            replyTo:        $replyTo ?: null,
        );

        $rendered = $this->renderer->render($bodyHtml, $ctx);

        return Response::html($rendered);
    }

    // ─── POST /compose/load-template ──────────────────────────────────────────

    /**
     * Load a template's HTML into the editor body.
     *
     * Called by HTMX when the user selects a template from the dropdown.
     * Returns the rendered template HTML as an HTMX partial that replaces
     * the body editor's textarea value.
     *
     * The response is the full `_editor.php` partial, pre-populated with
     * the template's rendered HTML and the template ID hidden input updated.
     */
    public function loadTemplate(Request $request): Response
    {
        $templateId = (int) ($request->post('template_id') ?? 0);

        if ($templateId === 0) {
            // User selected "None / Blank" — return the editor with empty body
            return $this->partial('compose/_editor', [
                'bodyHtml'   => '',
                'templateId' => null,
            ]);
        }

        $template = $this->templates->findById($templateId);

        if (!$template) {
            throw new NotFoundException("Template #{$templateId} not found.");
        }

        // Render with global context so the preview in the editor already
        // reflects the current branding (colours, logo)
        $renderedHtml = $this->renderer->renderWithGlobalContext($template->htmlContent);

        return $this->partial('compose/_editor', [
            'bodyHtml'   => $renderedHtml,
            'templateId' => $template->id,
        ]);
    }

    // ─── GET /compose/recipient-hints ─────────────────────────────────────────

    /**
     * Return recipient autocomplete suggestions for the chip input.
     *
     * Called by HTMX on keyup in the chip input's text field (debounced 300ms).
     * Returns a small dropdown list of matching contacts and groups.
     *
     * The response is an `_autocomplete-dropdown.php` partial positioned
     * absolutely below the chip input.
     */
    public function recipientHints(Request $request): Response
    {
        $query = trim($request->get('q', ''));

        if (strlen($query) < 2) {
            // Don't search until the user has typed at least 2 characters
            return $this->partial('compose/_autocomplete-dropdown', ['suggestions' => []]);
        }

        // Search contacts
        $contacts = $this->resolver->searchContacts($query);

        // Search groups (simple string filter on group names)
        $groups = array_filter(
            $this->resolver->allGroups(),
            fn($g) => stripos($g, $query) !== false
        );

        $suggestions = [];

        foreach ($contacts as $contact) {
            $suggestions[] = [
                'type'  => 'email',
                'value' => $contact->email,
                'label' => $contact->fullName() . ' <' . $contact->email . '>',
            ];
        }

        foreach ($groups as $group) {
            $suggestions[] = [
                'type'  => 'group',
                'value' => $group,
                'label' => '🏷 ' . $group . ' (group)',
            ];
        }

        return $this->partial('compose/_autocomplete-dropdown', [
            'suggestions' => array_slice($suggestions, 0, 8), // max 8 suggestions
        ]);
    }
}