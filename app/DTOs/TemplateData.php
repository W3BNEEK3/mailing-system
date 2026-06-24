<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * TemplateData
 *
 * Carries the data needed to create or update an email template.
 * Used by TemplateController → TemplateRepository.
 *
 * Usage:
 *   $data = new TemplateData(
 *       name:          'My Template',
 *       category:      'Newsletter',
 *       htmlContent:   '<html>...',
 *       supportsLogo:  true,
 *       supportsColors: true,
 *   );
 */
readonly class TemplateData
{
    public function __construct(
        public string $name,
        public string $category,
        public string $htmlContent,
        public bool   $supportsLogo,
        public bool   $supportsColors,
    ) {}

    /**
     * Convert to an array for EmailTemplate::create() or update().
     */
    public function toArray(): array
    {
        return [
            'name'            => $this->name,
            'category'        => $this->category,
            'html_content'    => $this->htmlContent,
            'supports_logo'   => $this->supportsLogo   ? 1 : 0,
            'supports_colors' => $this->supportsColors ? 1 : 0,
        ];
    }
}
