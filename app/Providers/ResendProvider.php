<?php

declare(strict_types=1);

namespace App\Providers;

use App\Providers\Contracts\EmailProviderInterface;
use App\DTOs\EmailPayload;
use App\DTOs\SendResult;
use App\Exceptions\ProviderException;

class ResendProvider implements EmailProviderInterface
{
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = trim($apiKey);
    }

    public function send(EmailPayload $payload): SendResult
    {
        if (empty($this->apiKey)) {
            throw new ProviderException("Resend API Key is missing. Please configure credentials.");
        }

        $ch = curl_init('https://api.resend.com/emails');

        $senderEmail = trim($payload->senderEmail);
        $senderName  = trim($payload->senderName);

        if (empty($senderEmail)) {
            throw new ProviderException(
                'Sender email is not configured. Go to Settings and set a default sender email address.'
            );
        }

        $from = $senderName !== '' ? "{$senderName} <{$senderEmail}>" : $senderEmail;

        $postData = [
            'from'    => $from,
            'to'      => $payload->recipients,
            'subject' => $payload->subject,
            'html'    => $payload->html,
        ];

        if (!empty($payload->replyTo)) $postData['reply_to'] = $payload->replyTo;
        if (!empty($payload->cc)) $postData['cc'] = $payload->cc;
        if (!empty($payload->bcc)) $postData['bcc'] = $payload->bcc;

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);

        if ($response === false) {
            throw new ProviderException("cURL Error: " . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $msg = $data['message'] ?? 'Unknown Resend Error';
            throw new ProviderException("Resend API Error ($httpCode): $msg");
        }

        return new SendResult($data['id'] ?? uniqid(), 'sent', $response);
    }

    public function testConnection(): bool
    {
        if (empty($this->apiKey)) {
            return false;
        }

        // Resend /domains endpoint is a safe way to test if an API key is valid
        $ch = curl_init('https://api.resend.com/domains');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return $httpCode === 200;
    }
}