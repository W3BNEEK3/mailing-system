<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Helpers\Crypto;

/**
 * Credential
 *
 * Stores encrypted email provider credentials.
 * The 'config' column contains an AES-256-CBC encrypted JSON string.
 * Never read 'config' directly — always use decryptedConfig().
 *
 * Usage:
 *   $credential = Credential::findBy('provider', 'resend');
 *   $config     = $credential->decryptedConfig();
 *   $apiKey     = $config['api_key'];
 */
class Credential extends Model
{
    protected static string $table = 'credentials';

    protected array $fillable = ['provider', 'is_active', 'config'];

    // ─── Domain methods ────────────────────────────────────────────────────

    /**
     * Decrypt the stored config JSON and return it as an associative array.
     *
     * The stored value looks like: "base64encodedciphertext..."
     * After decryption it becomes: {"api_key": "re_abc123...", ...}
     *
     * Usage:
     *   $config = $credential->decryptedConfig();
     *   $apiKey = $config['api_key'];  // for Resend
     *   $host   = $config['host'];     // for SMTP
     */
    public function decryptedConfig(): array
    {
        if (empty($this->config)) {
            return [];
        }

        try {
            $json = Crypto::decrypt((string)$this->config);
            $data = json_decode($json, associative: true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            // Log the error but don't expose it — return empty array as safe fallback
            logger()->error('Failed to decrypt credential config', [
                'provider' => $this->provider,
                'error'    => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check if this credential is the currently active provider.
     */
    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }

    /**
     * Return a masked version of the most sensitive config field.
     * Used in the credentials form to show "••••••••" for saved credentials
     * without exposing any real characters.
     *
     * @param string $field  Config key to mask — e.g. 'api_key', 'password'
     * @return string        '••••••••' if the field exists and is non-empty, '' otherwise
     */
    public function maskField(string $field): string
    {
        $config = $this->decryptedConfig();
        return isset($config[$field]) && $config[$field] !== '' ? '••••••••' : '';
    }
}
