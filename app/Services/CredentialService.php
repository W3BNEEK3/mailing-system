<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ProviderException;
use App\Helpers\Crypto;
use App\Models\Credential;
use App\Providers\Contracts\EmailProviderInterface;
use App\Providers\ResendProvider;
use App\Providers\SmtpProvider;

/**
 * CredentialService
 *
 * Manages email provider credentials:
 *   - Encrypting config before storage
 *   - Decrypting config for use
 *   - Resolving the active provider adapter
 *   - Testing connections
 *   - Switching the active provider
 *
 * The service owns the provider resolution logic. Nothing outside this class
 * should directly instantiate ResendProvider or SmtpProvider.
 *
 * Usage:
 *   $service  = new CredentialService();
 *   $service->save('resend', ['api_key' => 're_abc...']);
 *   $provider = $service->getActiveProvider();  // ResendProvider or SmtpProvider
 *   $result   = $provider->testConnection();
 */
class CredentialService
{
    // ─── Save ─────────────────────────────────────────────────────────────────

    /**
     * Encrypt and save credentials for a provider.
     *
     * If a row for this provider already exists (due to the UNIQUE KEY on `provider`),
     * the existing row is updated. If not, a new row is inserted.
     *
     * The config array is JSON-encoded then AES-256-CBC encrypted before storage.
     * The raw config values NEVER touch the database unencrypted.
     *
     * @param string               $provider  'resend' or 'smtp'
     * @param array<string, mixed> $config    Key-value pairs for this provider
     * @return Credential                     The saved/updated Credential row
     *
     * @throws \App\Exceptions\StorageException  If encryption fails (missing APP_KEY)
     */
    public function save(string $provider, array $config): Credential
    {
        // Encrypt the entire config as a JSON blob
        $encryptedConfig = Crypto::encrypt(json_encode($config));

        // Find existing row for this provider
        $existing = Credential::findBy('provider', $provider);

        if ($existing) {
            // Update the config but preserve the is_active flag
            $existing->update(['config' => $encryptedConfig]);
            return $existing;
        }

        // Insert a new row (not active by default — user must explicitly activate)
        return Credential::create([
            'provider'  => $provider,
            'is_active' => 0,
            'config'    => $encryptedConfig,
        ]);
    }

    // ─── Read ─────────────────────────────────────────────────────────────────

    /**
     * Get the currently active provider credential row.
     *
     * @return Credential|null  null if no provider has been saved and activated yet
     */
    public function getActive(): ?Credential
    {
        return Credential::findBy('is_active', 1);
    }

    /**
     * Get the credential row for a specific provider.
     * Returns null if credentials for that provider have never been saved.
     *
     * @param string $provider  'resend' or 'smtp'
     * @return Credential|null
     */
    public function getForProvider(string $provider): ?Credential
    {
        return Credential::findBy('provider', $provider);
    }

    // ─── Active Provider Management ───────────────────────────────────────────

    /**
     * Set a provider as active, deactivating all others.
     *
     * This runs two queries:
     *   1. Set all credentials rows to is_active = 0
     *   2. Set the target provider row to is_active = 1
     *
     * @param string $provider  'resend' or 'smtp'
     * @return bool  true if the provider credential exists and was activated
     */
    public function setActive(string $provider): bool
    {
        $db = Credential::db();

        // Step 1: Deactivate all providers
        $db->exec("UPDATE credentials SET is_active = 0");

        // Step 2: Activate the requested provider
        $stmt = $db->prepare("UPDATE credentials SET is_active = 1 WHERE provider = ?");
        $stmt->execute([$provider]);

        return $stmt->rowCount() > 0;
    }

    // ─── Provider Resolution ──────────────────────────────────────────────────

    /**
     * Resolve and return the active email provider adapter.
     *
     * Reads the active credential from the DB, decrypts the config,
     * and returns the appropriate provider instance.
     *
     * @return EmailProviderInterface  ResendProvider or SmtpProvider
     *
     * @throws ProviderException  If no active credential is found
     */
    public function getActiveProvider(): EmailProviderInterface
    {
        $credential = $this->getActive();

        if (!$credential) {
            throw new ProviderException(
                'No active email provider configured. ' .
                'Please set up credentials in Settings → Credentials.',
                'none'
            );
        }

        return $this->buildProvider($credential);
    }

    /**
     * Build a provider adapter for a specific provider string (for testing).
     *
     * Unlike getActiveProvider(), this does NOT require the provider to be active.
     * Used by testConnection() to test a provider that hasn't been activated yet.
     *
     * @param string $provider  'resend' or 'smtp'
     * @return EmailProviderInterface
     *
     * @throws ProviderException  If no credentials are saved for this provider
     */
    public function buildProviderByName(string $provider): EmailProviderInterface
    {
        $credential = $this->getForProvider($provider);

        if (!$credential) {
            throw new ProviderException(
                "No saved credentials found for provider: {$provider}. " .
                "Save credentials first, then test the connection.",
                $provider
            );
        }

        return $this->buildProvider($credential);
    }

    // ─── Connection Testing ───────────────────────────────────────────────────

    /**
     * Test the connection for a specific provider.
     *
     * Builds the provider adapter and calls testConnection() on it.
     * Returns a result array with 'success' and 'message' keys so the
     * controller can build the appropriate response without any provider logic.
     *
     * @param string $provider  'resend' or 'smtp'
     * @return array{success: bool, message: string}
     */
    public function testConnection(string $provider): array
    {
        try {
            $adapter = $this->buildProviderByName($provider);
            $success = $adapter->testConnection();

            return [
                'success' => $success,
                'message' => $success
                    ? ucfirst($provider) . ' connection successful. Credentials are valid.'
                    : ucfirst($provider) . ' connection failed. Check your credentials.',
            ];

        } catch (ProviderException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Throwable $e) {
            logger()->error("Connection test failed for {$provider}", ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => "An unexpected error occurred while testing the {$provider} connection.",
            ];
        }
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /**
     * Build a provider adapter from a Credential model instance.
     *
     * @param Credential $credential
     * @return EmailProviderInterface
     *
     * @throws ProviderException  If the provider type is unrecognised
     */
    private function buildProvider(Credential $credential): EmailProviderInterface
    {
        $config = $credential->decryptedConfig();

        return match ($credential->provider) {
            'resend' => new ResendProvider(apiKey: (string) ($config['api_key'] ?? '')),
            'smtp'   => new SmtpProvider(config: $config),
            default  => throw new ProviderException(
                "Unknown provider type: {$credential->provider}",
                (string) $credential->provider
            ),
        };
    }
    
    /**
 * Build and return the active email provider, or null if none is configured.
 *
 * Alias for convenience — called by EmailSendService.
 *
 * @return EmailProviderInterface|null
 */
public function buildActiveProvider(): ?EmailProviderInterface
{
    $credential = $this->getActiveCredential();

    if (!$credential) {
        return null;
    }

    return $this->buildProvider($credential);
}
}
