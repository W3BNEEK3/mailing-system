<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\DTOs\EmailPayload;
use App\DTOs\RenderContext;
use App\Exceptions\NotFoundException;
use App\Exceptions\ProviderException;
use App\Repositories\DraftRepository;
use App\Repositories\LogRepository;
use App\Repositories\TemplateRepository;
use App\Services\EmailSendService;
use App\Services\RecipientResolverService;
use App\Services\TemplateRenderService;

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

    public function index(Request $request): Response
    {
        $globalContext = $this->renderer->buildGlobalContext();

        return $this->view('compose/index', [
            'pageTitle'      => 'Compose — ' . setting('site_name', 'Emirates'),
            'templates'      => $this->templates->findAll(),
            'globalContext'  => $globalContext,
            'draft'          => null,
        ]);
    }

    public function send(Request $request): Response
    {
        $post = $request->post();

        $rawRecipients = json_decode($post['recipients'] ?? '[]', true);
        $rawRecipients = is_array($rawRecipients) ? $rawRecipients : [];

        $subject  = trim($post['subject']   ?? '');
        $bodyHtml = trim($post['body_html'] ?? '');
        $replyTo  = trim($post['reply_to']  ?? '');
        $rawCc    = json_decode($post['cc']  ?? '[]', true);
        $rawBcc   = json_decode($post['bcc'] ?? '[]', true);

        $errors = [];
        if (empty($rawRecipients)) $errors[] = 'Add at least one recipient before sending.';
        if ($subject === '') $errors[] = 'Subject cannot be empty.';
        if ($bodyHtml === '') $errors[] = 'Email body cannot be empty. Select a template or write your content.';

        if (!empty($errors)) {
            return Response::html('')->htmxTrigger('showToast', ['type' => 'error', 'message' => implode(' ', $errors)]);
        }

        $resolved = $this->resolver->resolve($rawRecipients);

        if ($resolved->isEmpty()) {
            return Response::html('')->htmxTrigger('showToast', ['type' => 'error', 'message' => 'No deliverable recipients after filtering suppressed contacts. ' . $resolved->warningMessage()]);
        }

        $globalCtx = $this->renderer->buildGlobalContext();
        $emailLogoPath   = $post['email_logo_path']  ?? null;
        $primaryColor    = $post['primary_color']    ?? null;
        $secondaryColor  = $post['secondary_color']  ?? null;

        $logoUrl = $emailLogoPath ? url('/storage/uploads/' . ltrim($emailLogoPath, '/')) : $globalCtx->logoUrl;

        $renderCtx = new RenderContext(
            logoUrl:        $logoUrl,
            primaryColor:   $primaryColor    ?: $globalCtx->primaryColor,
            secondaryColor: $secondaryColor  ?: $globalCtx->secondaryColor,
            senderName:     $globalCtx->senderName,
            senderEmail:    $globalCtx->senderEmail,
            replyTo:        $replyTo ?: null,
        );

        $templateId = isset($post['template_id']) && $post['template_id'] !== '' ? (int) $post['template_id'] : null;

        $finalBodyHtml = $bodyHtml;
        if ($templateId) {
            $template = $this->templates->findById($templateId);
            $origHtml = $template->html_content ?? $template->htmlContent ?? '';
            
            if ($origHtml) {
                $posStart = stripos($origHtml, '<body');
                if ($posStart !== false) {
                    $posStartClose = strpos($origHtml, '>', $posStart);
                    $posEnd = strripos($origHtml, '</body>');
                    if ($posStartClose !== false && $posEnd !== false) {
                        $headPart = substr($origHtml, 0, $posStartClose + 1);
                        $tailPart = substr($origHtml, $posEnd);
                        $finalBodyHtml = $headPart . "\n" . $bodyHtml . "\n" . $tailPart;
                    }
                }
            }
        }

        $renderedHtml = $this->renderer->render($finalBodyHtml, $renderCtx);

        $payload = new EmailPayload(
            senderName:  $globalCtx->senderName,
            senderEmail: $globalCtx->senderEmail,
            recipients:  $resolved->emails,
            subject:     $subject,
            html:        $renderedHtml,
            replyTo:     $replyTo,
            cc:          is_array($rawCc)  ? $rawCc  : [],
            bcc:         is_array($rawBcc) ? $rawBcc : [],
        );

        $providerName = 'unknown';
        try {
            if (method_exists($this->sendService, 'getActiveCredential')) {
                $this->sendService->getActiveCredential();
            }
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

            $this->logs->insertLog(array_merge($logData, ['provider_message_id' => $result->messageId, 'status' => 'sent']));

            $draftId = isset($post['draft_id']) && $post['draft_id'] !== '' ? (int) $post['draft_id'] : null;
            if ($draftId !== null) $this->drafts->delete($draftId);

            $successMsg = 'Email sent to ' . count($resolved->emails) . ' recipient' . (count($resolved->emails) > 1 ? 's' : '') . '.';
            if ($resolved->warningMessage() !== '') $successMsg .= ' Note: ' . $resolved->warningMessage();

            $response = $this->partial('compose/_form-reset')
                ->htmxTrigger('showToast', ['type' => 'success', 'message' => $successMsg])
                ->htmxTrigger('composeSent');

            if (!empty($resolved->unsavedEmails)) {
                $response->htmxTrigger('promptSaveRecipients', ['emails' => $resolved->unsavedEmails]);
            }

            return $response;

        } catch (ProviderException $e) {
            $this->logs->insertErrorLog(array_merge($logData, ['error_message' => $e->getMessage()]));
            return Response::html('')->htmxTrigger('showToast', ['type' => 'error', 'message' => 'Send failed: ' . $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return Response::html('')->htmxTrigger('showToast', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function preview(Request $request): Response
    {
        $post     = $request->post();
        $bodyHtml = $post['body_html'] ?? '';
        $replyTo  = $post['reply_to']  ?? '';

        $globalCtx     = $this->renderer->buildGlobalContext();
        $emailLogoPath = $post['email_logo_path'] ?? null;
        $primaryColor  = $post['primary_color']   ?? null;
        $secondaryColor = $post['secondary_color'] ?? null;

        $logoUrl = $emailLogoPath ? url('/storage/uploads/' . ltrim($emailLogoPath, '/')) : $globalCtx->logoUrl;

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

    public function loadTemplate(Request $request): Response
    {
        $templateId = (int) ($request->post('template_id') ?? 0);

        if ($templateId === 0) {
            return Response::html(component('compose/_editor', [
                'bodyHtml'   => '',
                'templateId' => null,
            ]));
        }

        $template = $this->templates->findById($templateId);

        if (!$template) {
            throw new NotFoundException("Template #{$templateId} not found.");
        }

        // FIXED: Reading the exact database column 'html_content'
        $html = $template->html_content ?? $template->htmlContent ?? '';

        return Response::html(component('compose/_editor', [
            'bodyHtml'   => $html,
            'templateId' => $template->id,
        ]));
    }
    public function recipientHints(Request $request): Response
    {
        $query = trim($request->get('q', ''));

        if ($query === '') {
            $contacts = $this->resolver->searchContacts('');
            $groups = $this->resolver->allGroups();
        } else {
            $contacts = $this->resolver->searchContacts($query);
            $groups = array_filter($this->resolver->allGroups(), fn($g) => stripos($g, $query) !== false);
        }

        $suggestions = [];
        foreach ($contacts as $contact) {
            $suggestions[] = ['type' => 'email', 'value' => $contact->email, 'label' => $contact->fullName() . ' <' . $contact->email . '>'];
        }
        foreach ($groups as $group) {
            $suggestions[] = ['type' => 'group', 'value' => $group, 'label' => '🏷 ' . $group . ' (group)'];
        }

        $suggestions = array_slice($suggestions, 0, 8);

        // Return JSON when the client requests it (our chip-input JS does this).
        // Fall back to the HTML partial for any legacy HTMX callers.
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            return Response::json(['suggestions' => $suggestions]);
        }

        return $this->partial('compose/_autocomplete-dropdown', ['suggestions' => $suggestions]);
    }
}