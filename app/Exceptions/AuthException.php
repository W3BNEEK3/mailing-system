<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * AuthException — Thrown when a user is not authenticated or not authorised.
 * Maps to HTTP 401.
 */
class AuthException extends AppException
{
    public function __construct(string $message = 'Unauthorised')
    {
        parent::__construct($message, 401);
    }
}
