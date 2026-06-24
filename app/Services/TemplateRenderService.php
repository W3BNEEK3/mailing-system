<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RenderContext;
use App\Repositories\SettingRepository;
use App\Helpers\Url;

/**
 * TemplateRenderService
 *
 * Performs token substitution on email template HTML.
 *
 * Supported tokens (case-sensitive):
 *   {{LOGO_URL}}         — URL to the active logo image
 *   {{PRIMARY_COLOR}}    — 6-digit hex colour, e.g. #1d4ed8
 *   {{SECONDARY_COLOR}}  — 6-digit hex colour, e.g. #0f172a
 *   {{SENDER_NAME}}      — Display name shown in the From field
 *   {{SENDER_EMAIL}}     — From email address
 *   {{REPLY_TO}}         — Reply-to address (falls back to sender email)
 *
 * Substitution rules:
 *   1. Only tokens listed above are replaced. Any other {{...}} patterns in
 *      the template are left intact (e.g. {{FIRST_NAME}} for personalisation
 *      is handled by a future send-time pass, not here).
 *   2. If a context value is an empty string or null (e.g. no logo is set),
 *      the token is replaced with an empty string — it is never left in the
 *      output as a literal {{...}} tag that a recipient would see.
 *   3. The replacement is HTML-context-safe for the values we control
 *      (hex colours, URLs, names). Values originate from admin settings,
 *      not from untrusted user input.
 *
 * Preview vs. send distinction:
 *   - For preview, call renderWithGlobalContext() — it builds the context
 *     from current settings and is suitable for displaying in iframes.
 *   - For send, the caller builds a RenderContext with any per-email
 *     overrides (compose-time logo/colour overrides) and calls render().
 *
 * Usage:
 *   // Preview in the template gallery:
 *   $html = $service->renderWithGlobalContext($template->html_content);
 *
 *   // Send with overrides:
 *   $ctx  = new RenderContext(logoUrl: $overrideLogo, primaryColor: $overrideColor, ...);
 *   $html = $service->render($template->html_content, $ctx);
 */
class TemplateRenderService
{
    public function __construct(
        private readonly SettingRepository $settings,
    ) {}

    // ─── Primary API ──────────────────────────────────────────────────────────

    /**
     * Substitute all known {{TOKEN}} placeholders in the template HTML.
     *
     * Performs a single str_replace pass over all known tokens at once,
     * which is more efficient than multiple sequential replacements.
     *
     * @param string        $html  Raw template HTML (may contain {{TOKEN}} placeholders)
     * @param RenderContext $ctx   Context values to substitute
     * @return string              Rendered HTML with all tokens replaced
     */
    public function render(string $html, RenderContext $ctx): string
    {
        $replyTo = $ctx->replyTo ?? $ctx->senderEmail;

        $tokens = [
            '{{LOGO_URL}}'        => $ctx->logoUrl,
            '{{PRIMARY_COLOR}}'   => $ctx->primaryColor,
            '{{SECONDARY_COLOR}}' => $ctx->secondaryColor,
            '{{SENDER_NAME}}'     => $ctx->senderName,
            '{{SENDER_EMAIL}}'    => $ctx->senderEmail,
            '{{REPLY_TO}}'        => $replyTo,
        ];

        return str_replace(
            array_keys($tokens),
            array_values($tokens),
            $html,
        );
    }

    /**
     * Build a RenderContext from the current global settings and render.
     *
     * Convenience method for preview scenarios where no per-email overrides exist.
     * Called by TemplateController::preview() and previewDraft().
     *
     * @param string $html  Raw template HTML
     * @return string       Rendered HTML using global settings as context
     */
    public function renderWithGlobalContext(string $html): string
    {
        return $this->render($html, $this->buildGlobalContext());
    }

    // ─── Inspection ───────────────────────────────────────────────────────────

    /**
     * Inspect a template's HTML to determine which dynamic features it supports.
     *
     * This is called once at upload/create time and the result is stored in
     * the `supports_logo` and `supports_colors` columns. It is NOT called
     * on every render — that would be wasteful.
     *
     * @param string $html  Raw template HTML to inspect
     * @return array{supports_logo: bool, supports_colors: bool}
     */
    public function inspect(string $html): array
    {
        return [
            'supports_logo'   => str_contains($html, '{{LOGO_URL}}'),
            'supports_colors' => str_contains($html, '{{PRIMARY_COLOR}}')
                              || str_contains($html, '{{SECONDARY_COLOR}}'),
        ];
    }

    // ─── Context Builder ──────────────────────────────────────────────────────

    /**
     * Build a RenderContext from the current global settings.
     *
     * Called internally by renderWithGlobalContext(). Exposed as public so
     * ComposeController (Phase 8) can retrieve the base context and then
     * overlay per-email overrides before calling render().
     *
     * Logo URL resolution:
     *   - If a logo path is stored in settings, resolve it to a full URL via storageUrl().
     *   - If no logo is stored (first-time setup), use an empty string so {{LOGO_URL}}
     *     is replaced with '' — this hides any <img> tag that wraps the token,
     *     provided the template uses a display:none fallback.
     *
     * @return RenderContext
     */
    public function buildGlobalContext(): RenderContext
    {
        $logoPath = $this->settings->get('email_logo_path');
        $logoUrl  = $logoPath ? Url::storageUrl($logoPath) : '';

        return new RenderContext(
            logoUrl:        $logoUrl,
            primaryColor:   $this->settings->get('primary_color',   '#1d4ed8'),
            secondaryColor: $this->settings->get('secondary_color', '#0f172a'),
            senderName:     $this->settings->get('sender_name',     'Emirates'),
            senderEmail:    $this->settings->get('sender_email',    ''),
            replyTo:        null,
        );
    }
}
