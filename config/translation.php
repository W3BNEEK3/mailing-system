<?php

// LibreTranslate API configuration.

return [
    // Base URL of the LibreTranslate instance (public or self-hosted)
    'base_url' => rtrim($_ENV['LIBRETRANSLATE_URL'] ?? 'https://libretranslate.com', '/'),

    // API key (required for the public instance; may be empty for self-hosted)
    'api_key' => $_ENV['LIBRETRANSLATE_API_KEY'] ?? '',

    // Supported languages shown in the Translate dropdown
    // Format: 'language_code' => 'Display Name'
    'supported_languages' => [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'pt' => 'Portuguese',
        'ar' => 'Arabic',
        'de' => 'German',
        'zh' => 'Chinese (Simplified)',
        'yo' => 'Yoruba',
        'ha' => 'Hausa',
        'ig' => 'Igbo',
    ],
];
