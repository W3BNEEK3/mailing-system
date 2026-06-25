<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\EmailPayload;
use App\DTOs\SendResult;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\EmailProviderInterface;
use App\Services\CredentialService;

/**
 * EmailSendService
 *
 * Orchestrates the mechanics of sending an email.
 *
 * Responsibilities:
 *   1. Resolve the currently active email provider via CredentialService.
 *   2. Call provider::send($payload).
 *   3. Return the SendResult to the caller.
 *
 * What this service does NOT do:
 *   - Log the send (the controller does this, after receiving the SendResult)
 *   - Resolve recipient groups (the controller does this before building the payload)
 *   - Render template tokens (TemplateRenderService does this before building the payload)
 *
 * This design means the service has exactly one job and can be tested in
 * isolation with a mock provider.
 *
 * Usage (in ComposeController::send()):
 *   $result = $this->sendService->send($payload);
 *   // $result->messageId is the provider's message ID
 *   // Log the result → $this->logs->insertLog([...])
 *
 * @throws ProviderException  When the active provider rejects the send
 * @throws \RuntimeException  When no active provider is configured
 */
class EmailSendService
{
    public function __construct(
        private readonly CredentialService $credentials,
    ) {}

    /**
     * Send an email using the currently active provider.
     *
     * @param EmailPayload $payload  Fully constructed payload (tokens substituted,
     *                               groups resolved, suppressed contacts removed)
     * @return SendResult            The provider's send result with message ID
     *
     * @throws ProviderException  When the provider rejects the request
     * @throws \RuntimeException  When no credential is active (user hasn't configured a provider)
     */
    public function send(EmailPayload $payload): SendResult
    {
        // Resolve the active provider — throws RuntimeException if none is configured
        $provider = $this->resolveProvider();

        // Delegate to the provider — throws ProviderException on failure
        return $provider->send($payload);
    }

    /**
     * Determine which provider is currently active and instantiate it.
     *
     * CredentialService::buildProvider() reads the active credential row,
     * decrypts the config, and returns a configured provider instance.
     *
     * @throws \RuntimeException  If no active credential is set in the database
     */
    private function resolveProvider(): EmailProviderInterface
    {
        $provider = $this->credentials->buildActiveProvider();

        if ($provider === null) {
            throw new \RuntimeException(
                'No active email provider is configured. ' .
                'Go to Settings → Credentials to set up Resend or SMTP.'
            );
        }

        return $provider;
    }
}