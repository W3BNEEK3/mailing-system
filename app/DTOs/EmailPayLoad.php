<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * EmailPayload
 *
 * Everything needed to send one email.
 * Passed from ComposeController → EmailSendService → Provider.
 *
 * Usage:
 *   $payload = new EmailPayload(
 *       recipients: ['alice@example.com', 'bob@example.com'],
 *       subject:    'Hello from Emirates',
 *       html:       '<h1>Hi!</h1><p>This is your email.</p>',
 *       senderName:  'Acme Corp',
 *       senderEmail: 'hello@acme.com',
 *   );
 */
readonly class EmailPayload
{
    public function __construct(
        /** Array of recipient email address strings */
        public array   $recipients,

        /** Email subject line */
        public string  $subject,

        /** Final rendered HTML body (tokens already replaced) */
        public string  $html,

        /** Display name for the From field: "Acme Corp" */
        public string  $senderName,

        /** Email address for the From field: "hello@acme.com" */
        public string  $senderEmail,

        /** Optional Reply-To address (defaults to senderEmail if null) */
        public ?string $replyTo = null,

        /** Optional CC email addresses */
        public ?array  $cc = null,

        /** Optional BCC email addresses */
        public ?array  $bcc = null,
    ) {}

    /**
     * Get the formatted "From" string used by email providers.
     * Format: "Display Name <email@address.com>"
     */
    public function fromString(): string
    {
        return "{$this->senderName} <{$this->senderEmail}>";
    }

    /**
     * Get the effective reply-to address.
     * Falls back to sender email if not explicitly set.
     */
    public function effectiveReplyTo(): string
    {
        return $this->replyTo ?? $this->senderEmail;
    }
}
