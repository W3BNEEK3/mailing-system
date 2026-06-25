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

        $tokens = [
            '{{LOGO_URL}}'        => (string)$ctx->logoUrl,
            '{{PRIMARY_COLOR}}'   => (string)$ctx->primaryColor,
            '{{SECONDARY_COLOR}}' => (string)$ctx->secondaryColor,
            '{{SENDER_NAME}}'     => (string)$ctx->senderName,
            '{{SENDER_EMAIL}}'    => (string)$ctx->senderEmail,
            '{{REPLY_TO}}'        => (string)$replyTo,
        ];

        return str_ireplace(
            array_keys($tokens),
            array_values($tokens),
            $html
        );
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