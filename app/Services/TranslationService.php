<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\TranslationException;

class TranslationService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('translation.base_url', 'https://libretranslate.com'), '/');
        $this->apiKey  = config('translation.api_key', '');
    }

    public function translateBody(string $html, string $targetLang): string
    {
        return $this->requestTranslation($html, $targetLang, 'html');
    }

    public function translatePlaintext(string $text, string $targetLang): string
    {
        return $this->requestTranslation($text, $targetLang, 'text');
    }

    private function requestTranslation(string $q, string $targetLang, string $format): string
    {
        if (empty(trim($q))) return $q;

        $data = [
            'q'      => $q,
            'source' => 'auto',
            'target' => $targetLang,
            'format' => $format,
        ];

        if (!empty($this->apiKey)) {
            $data['api_key'] = $this->apiKey;
        }

        $ch = curl_init($this->baseUrl . '/translate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new TranslationException("Translation connection failed: " . $error);
        }

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = $errData['error'] ?? "HTTP $httpCode";
            throw new TranslationException("Translation API Error: " . $errMsg);
        }

        $result = json_decode($response, true);
        if (!isset($result['translatedText'])) {
            throw new TranslationException("Invalid response from translation API.");
        }

        return $result['translatedText'];
    }
}