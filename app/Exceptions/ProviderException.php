<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * ProviderException — Thrown when an email provider (Resend/SMTP) returns an error.
 *
 * Used in ResendProvider and SmtpProvider to signal send failures.
 * The ErrorHandler logs these and shows a toast notification.
 */
class ProviderException extends AppException
{
    private string $providerName;

    public function __construct(string $message, string $providerName = 'unknown', int $code = 500)
    {
        parent::__construct($message, $code);
        $this->providerName = $providerName;
    }

    public function getProviderName(): string
    {
        return $this->providerName;
    }
}
