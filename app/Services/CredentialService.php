<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Credential;
use App\Helpers\Crypto;
use App\Providers\ResendProvider;
use App\Providers\SmtpProvider;

class CredentialService
{
    public function getActive(): ?Credential
    {
        $creds = Credential::where(['is_active' => 1]);
        return $creds[0] ?? null;
    }

    public function save(string $provider, array $config): Credential
    {
        $configJson = json_encode($config);
        $encrypted  = Crypto::encrypt($configJson);

        $existing = Credential::where(['provider' => $provider]);
        
        if (!empty($existing)) {
            $cred = $existing[0];
            $cred->update(['config' => $encrypted]);
            return Credential::find((int) $cred->id);
        }

        return Credential::create([
            'provider'  => $provider,
            'is_active' => 0,
            'config'    => $encrypted,
        ]);
    }

    public function setActive(string $provider): void
    {
        // Set all to 0
        \App\Core\Database::getInstance()->getConnection()->exec("UPDATE credentials SET is_active = 0");

        // Set target to 1
        $target = Credential::where(['provider' => $provider]);
        if (!empty($target)) {
            $target[0]->update(['is_active' => 1]);
        }
    }

    public function testConnection(string $provider): bool
    {
        $creds = Credential::where(['provider' => $provider]);
        if (empty($creds)) return false;

        $cred = $creds[0];
        
        // FIXED: Ensure decrypted config is converted to a usable array
        $decryptedStr = Crypto::decrypt($cred->config);
        $config = json_decode($decryptedStr, associative: true) ?: [];

        if ($provider === 'resend') {
            $key = $config['api_key'] ?? '';
            $p = new ResendProvider($key);
            return $p->testConnection();
        }

        if ($provider === 'smtp') {
            // Setup for SMTP Provider (if you are using it)
            return false;
        }

        return false;
    }
    public function getForProvider(string $provider): ?Credential { $results = Credential::where(['provider' => $provider]); return !empty($results) ? $results[0] : null; }

    /**
     * Build and return a ready-to-use provider instance from the active credential.
     * Returns null if no credential is active or the provider type is unknown.
     */
    public function buildActiveProvider(): ?\App\Providers\Contracts\EmailProviderInterface
    {
        $cred = $this->getActive();
        if ($cred === null) return null;

        $config = json_decode(Crypto::decrypt($cred->config), associative: true) ?: [];

        if ($cred->provider === 'resend') {
            return new ResendProvider($config['api_key'] ?? '');
        }

        if ($cred->provider === 'smtp') {
            return new SmtpProvider($config);
        }

        return null;
    }
}