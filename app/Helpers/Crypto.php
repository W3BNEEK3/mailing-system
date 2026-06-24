<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Crypto — AES-256-CBC encryption and decryption
 *
 * Used to encrypt sensitive data (API keys, SMTP passwords) before storing
 * them in the database. The encryption key comes from APP_KEY in .env.
 *
 * How it works:
 *   - We use AES-256-CBC, a symmetric cipher. Same key encrypts and decrypts.
 *   - Each encryption generates a random IV (Initialisation Vector).
 *     This means encrypting the same value twice gives DIFFERENT ciphertexts.
 *   - The IV is prepended to the ciphertext and stored together, so we can
 *     extract it during decryption.
 *   - The final stored value is: base64( iv_bytes + encrypted_bytes )
 */
class Crypto
{
    private const CIPHER = 'AES-256-CBC';
    private const IV_LENGTH = 16; // AES-256-CBC always uses a 16-byte IV

    /**
     * Encrypt a plaintext string.
     *
     * Returns a base64-encoded string containing the IV + ciphertext.
     * Safe to store in the database.
     *
     * Usage:
     *   $encrypted = Crypto::encrypt('sk_live_abc123');
     */
    public static function encrypt(string $plaintext): string
    {
        $key = static::getKey();

        // Generate a fresh random IV for every encryption
        // This is critical — reusing IVs with the same key is a security vulnerability
        $iv = random_bytes(static::IV_LENGTH);

        $ciphertext = openssl_encrypt($plaintext, static::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed. Check that OpenSSL is available.');
        }

        // Combine: IV bytes + ciphertext bytes, then base64-encode the whole thing
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Decrypt a previously encrypted value.
     *
     * Accepts the base64 string produced by encrypt().
     * Returns the original plaintext.
     *
     * Usage:
     *   $apiKey = Crypto::decrypt($credential->config);
     */
    public static function decrypt(string $encrypted): string
    {
        $key = static::getKey();

        // Decode from base64 back to raw bytes
        $decoded = base64_decode($encrypted, strict: true);

        if ($decoded === false) {
            throw new \RuntimeException('Decryption failed: invalid base64 data.');
        }

        if (strlen($decoded) <= static::IV_LENGTH) {
            throw new \RuntimeException('Decryption failed: data too short to contain an IV.');
        }

        // Split: first 16 bytes = IV, rest = ciphertext
        $iv         = substr($decoded, 0, static::IV_LENGTH);
        $ciphertext = substr($decoded, static::IV_LENGTH);

        $plaintext = openssl_decrypt($ciphertext, static::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($plaintext === false) {
            throw new \RuntimeException(
                'Decryption failed. The data may be corrupted or the APP_KEY may have changed.'
            );
        }

        return $plaintext;
    }

    /**
     * Derive the 32-byte encryption key from APP_KEY in .env.
     *
     * APP_KEY is stored as "base64:..." in .env.
     * We decode it to get the raw 32 bytes needed by AES-256.
     */
    private static function getKey(): string
    {
        $appKey = config('app.key', '');

        if (empty($appKey)) {
            throw new \RuntimeException(
                'APP_KEY is not set in .env. ' .
                'Generate one with: php -r "echo \'base64:\' . base64_encode(random_bytes(32));"'
            );
        }

        // Strip the "base64:" prefix and decode
        if (str_starts_with($appKey, 'base64:')) {
            $key = base64_decode(substr($appKey, 7), strict: true);
        } else {
            // If no prefix, assume it's already raw (not recommended)
            $key = $appKey;
        }

        if ($key === false || strlen($key) < 32) {
            throw new \RuntimeException(
                'APP_KEY must be a base64-encoded 32-byte key. ' .
                'Regenerate it with: php -r "echo \'base64:\' . base64_encode(random_bytes(32));"'
            );
        }

        return $key;
    }
}
