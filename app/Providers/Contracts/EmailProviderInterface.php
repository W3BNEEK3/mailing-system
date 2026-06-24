<?php

declare(strict_types=1);

namespace App\Providers\Contracts;

use App\DTOs\EmailPayload;
use App\DTOs\SendResult;

/**
 * EmailProviderInterface
 *
 * The contract all email providers must implement.
 *
 * Emirates supports two providers in MVP:
 *   - ResendProvider  (primary: Resend HTTP API via cURL)
 *   - SmtpProvider    (fallback: PHPMailer SMTP)
 *
 * Both providers implement this interface so EmailSendService (Phase 8)
 * can call them interchangeably without knowing which is active.
 *
 * Contract:
 *   send()            — Send a single email. Returns SendResult on success.
 *                       Throws ProviderException on failure.
 *   testConnection()  — Validate connectivity and credentials without sending
 *                       a real email. Returns true/false.
 */
interface EmailProviderInterface
{
    /**
     * Send an email using this provider.
     *
     * @param EmailPayload $payload  All data needed to send the email
     * @return SendResult            The provider's message ID and send status
     *
     * @throws \App\Exceptions\ProviderException  When the provider rejects the send
     */
    public function send(EmailPayload $payload): SendResult;

    /**
     * Test the provider's connection and credentials.
     *
     * For Resend: calls GET /domains to validate the API key.
     * For SMTP: attempts an SMTP connection and login.
     *
     * Does NOT send an email — this is a connectivity check only.
     *
     * @return bool  true if credentials are valid and provider is reachable
     */
    public function testConnection(): bool;
}
