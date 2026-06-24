<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * TranslationException — Thrown when the LibreTranslate API call fails.
 */
class TranslationException extends AppException
{
    public function __construct(string $message = 'Translation failed')
    {
        parent::__construct($message, 500);
    }
}
