<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * RenderContext
 *
 * The resolved branding and sender context used when rendering an email template.
 * Passed to TemplateRenderService::render() to inject into template tokens.
 *
 * The values in this object represent the FINAL resolved values after
 * applying the override hierarchy:
 *   Email-level override > Global settings
 *
 * Usage:
 *   $context = new RenderContext(
 *       logoUrl:        'https://example.com/storage/logos/global/logo.png',
 *       primaryColor:   '#4F46E5',
 *       secondaryColor: '#10B981',
 *       senderName:     'Acme Corp',
 *       senderEmail:    'hello@acme.com',
 *   );
 *
 *   $html = $templateRenderService->render($template->html_content, $context);
 */
readonly class RenderContext
{
    public function __construct(
        /** URL to the logo image (absolute, accessible from recipient's email client) */
        public string  $logoUrl,

        /** Primary brand colour in hex format: '#4F46E5' */
        public string  $primaryColor,

        /** Secondary brand colour in hex format: '#10B981' */
        public string  $secondaryColor,

        /** Display name shown in the From field */
        public string  $senderName,

        /** Email address shown in the From field */
        public string  $senderEmail,

        /** Optional Reply-To override */
        public ?string $replyTo = null,
    ) {}

    /**
     * Build a RenderContext from the current global settings plus any per-email overrides.
     *
     * The email-level overrides win over global settings when provided.
     *
     * @param array $overrides Keys: logo_override_path, primary_color, secondary_color
     */
    public static function fromSettings(array $overrides = []): static
    {
        $settingRepo  = new \App\Repositories\SettingRepository();
        $settings     = $settingRepo->allAsKeyValue();

        // Resolve logo URL: prefer email-level override, fall back to global
        $logoPath = $overrides['logo_override_path'] ?? $settings['email_logo_path'] ?? null;
        $logoUrl  = $logoPath
            ? url('/storage/logos/' . ltrim(basename((string)$logoPath), '/'))
            : url('/assets/img/placeholder-logo.svg');

        return new static(
            logoUrl:        $logoUrl,
            primaryColor:   $overrides['primary_color']   ?? $settings['primary_color']   ?? '#4F46E5',
            secondaryColor: $overrides['secondary_color']  ?? $settings['secondary_color'] ?? '#10B981',
            senderName:     $settings['default_sender_name']  ?? '',
            senderEmail:    $settings['default_sender_email'] ?? '',
        );
    }
}
