<?php

declare(strict_types=1);

namespace App\Providers;

use App\DTOs\EmailPayload;
use App\DTOs\SendResult;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\EmailProviderInterface;

/**
 * ResendProvider
 *
 * Sends email via the Resend REST API (https://resend.com/docs/api-reference/emails).
 *
 * Uses PHP's cURL extension to make HTTP requests. No Composer SDK required.
 *
 * API endpoint:   POST https://api.resend.com/emails
 * Auth:           Authorization: Bearer {api_key}
 * Content-Type:   application/json
 * Success status: 200 OK
 *
 * Test endpoint:  GET https://api.resend.com/domains
 * Used by testConnection() to validate the key without sending an email.
 *
 * Error handling:
 *   Non-200 responses throw ProviderException with the API error message.
 *   cURL failures (network errors, timeouts) also throw ProviderException.
 *
 * Usage:
 *   $provider = new ResendProvider('re_abc123...');
 *   $result   = $provider->send($payload);
 *   echo $result->messageId; // 'msg_abc123'
 */
class ResendProvider implements EmailProviderInterface
{
    private const API_BASE    = 'https://api.resend.com';
    private const SEND_URL    = self::API_BASE . '/emails';
    private const DOMAINS_URL = self::API_BASE . '/domains';

    public function __construct(
        private readonly string $apiKey
    ) {}

    // ─── EmailProviderInterface ───────────────────────────────────────────────

    /**
     * Send an email via the Resend API.
     *
     * Request body (JSON):
     *   {
     *     "from":     "Name <email@domain.com>",
     *     "to":       ["recipient@example.com"],
     *     "subject":  "Subject line",
     *     "html":     "<html>...</html>",
     *     "reply_to": "reply@domain.com"    // optional
     *   }
     *
     * Success response (200):
     *   { "id": "msg_abc123" }
     *
     * Error response (4xx/5xx):
     *   { "name": "validation_error", "message": "..." }
     */
    public function send(EmailPayload $payload): SendResult
    {
        // Build the request body
        $body = [
            'from'    => $payload->fromString(),
            'to'      => $payload->recipients,
            'subject' => $payload->subject,
            'html'    => $payload->html,
        ];

        // Optional fields
        if (!empty($payload->replyTo)) {
            $body['reply_to'] = $payload->replyTo;
        }
        if (!empty($payload->cc)) {
            $body['cc'] = $payload->cc;
        }
        if (!empty($payload->bcc)) {
            $body['bcc'] = $payload->bcc;
        }

        // Make the API call
        [$statusCode, $responseBody] = $this->curlPost(self::SEND_URL, $body);

        $data = json_decode($responseBody, associative: true) ?? [];

        // Resend returns 200 on success, 4xx/5xx on failure
        if ($statusCode !== 200) {
            $message = $data['message'] ?? "Resend API error (HTTP {$statusCode})";
            throw new ProviderException($message, 'resend', $statusCode);
        }

        if (empty($data['id'])) {
            throw new ProviderException(
                'Resend returned a 200 response but no message ID.',
                'resend'
            );
        }

        return new SendResult(
            messageId:        $data['id'],
            status:           'sent',
            providerResponse: $responseBody,
        );
    }

    /**
     * Test the Resend API key by calling GET /domains.
     *
     * A 200 response means the key is valid and the account is reachable.
     * Any other status (401, 403, etc.) means the key is invalid.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            [$statusCode] = $this->curlGet(self::DOMAINS_URL);
            return $statusCode === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    // ─── cURL helpers ─────────────────────────────────────────────────────────

    /**
     * Make a POST request to the Resend API.
     *
     * @param string $url
     * @param array  $body  Data to send as JSON
     * @return array{0: int, 1: string}  [HTTP status code, response body]
     *
     * @throws ProviderException  On cURL-level errors (network down, timeout)
     */
    private function curlPost(string $url, array $body): array
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            // Timeout settings — important on shared hosting
            CURLOPT_CONNECTTIMEOUT => 10, // seconds to connect
            CURLOPT_TIMEOUT        => 30, // seconds to complete request
        ]);

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        curl_close($ch);

        if ($response === false || $error !== '') {
            throw new ProviderException(
                "Resend API request failed: {$error}",
                'resend'
            );
        }

        return [$status, (string) $response];
    }

    /**
     * Make a GET request to the Resend API.
     *
     * @param string $url
     * @return array{0: int, 1: string}
     *
     * @throws ProviderException
     */
    private function curlGet(string $url): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET        => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        curl_close($ch);

        if ($response === false || $error !== '') {
            throw new ProviderException(
                "Resend GET request failed: {$error}",
                'resend'
            );
        }

        return [$status, (string) $response];
    }
}
