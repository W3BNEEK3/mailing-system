<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * SendResult
 *
 * The result returned by a provider after attempting to send an email.
 * Returned from ResendProvider::send() and SmtpProvider::send().
 *
 * Usage:
 *   $result = $provider->send($payload);
 *   echo $result->messageId;  // 'msg_abc123' (from Resend)
 *   echo $result->status;     // 'sent'
 */
readonly class SendResult
{
    public function __construct(
        /** The unique message ID assigned by the provider */
        public string  $messageId,

        /** Delivery status: 'sent', 'failed', etc. */
        public string  $status,

        /** Raw response body from the provider (for debugging) */
        public ?string $providerResponse = null,
    ) {}

    /**
     * Check if the send was successful.
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'sent';
    }
}
