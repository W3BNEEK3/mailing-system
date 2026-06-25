<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RenderContext;
use App\Repositories\SettingRepository;
use App\Helpers\Url;

class TemplateRenderService
{
    public function __construct(
        private readonly SettingRepository $settings,
    ) {}

    public function render(string $html, RenderContext $ctx): string
    {
        $replyTo = $ctx->replyTo ?? $ctx->senderEmail;

        // FIXED: Using flexible, case-insensitive regex to catch {{Logo}}, {{Secondary_color}}, etc.
        $tokens = [
            '/\{\{\s*(LOGO_URL|LOGO)\s*\}\}/i'             => $ctx->logoUrl,
            '/\{\{\s*(PRIMARY_COLOR|PRIMARY)\s*\}\}/i'       => $ctx->primaryColor,
            '/\{\{\s*(SECONDARY_COLOR|SECONDARY)\s*\}\}/i'   => $ctx->secondaryColor,
            '/\{\{\s*(SENDER_NAME|SENDER)\s*\}\}/i'         => $ctx->senderName,
            '/\{\{\s*(SENDER_EMAIL|EMAIL)\s*\}\}/i'        => $ctx->senderEmail,
            '/\{\{\s*(REPLY_TO|REPLYTO)\s*\}\}/i'          => $replyTo,
        ];

        $result = preg_replace(array_keys($tokens), array_values($tokens), $html);
        return $result ?? $html;
    }

    public function renderWithGlobalContext(string $html): string
    {
        return $this->render($html, $this->buildGlobalContext());
    }

    public function inspect(string $html): array
    {
        return [
            'supports_logo'   => preg_match('/\{\{\s*(LOGO_URL|LOGO)\s*\}\}/i', $html) === 1,
            'supports_colors' => preg_match('/\{\{\s*(PRIMARY_COLOR|PRIMARY|SECONDARY_COLOR|SECONDARY)\s*\}\}/i', $html) === 1,
        ];
    }

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