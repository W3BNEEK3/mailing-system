<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * NotFoundException — Thrown when a requested resource does not exist.
 * Maps to HTTP 404.
 */
class NotFoundException extends AppException
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct($message, 404);
    }
}
