<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * StorageException — Thrown when a file upload or storage operation fails.
 */
class StorageException extends AppException
{
    public function __construct(string $message = 'File storage operation failed')
    {
        parent::__construct($message, 500);
    }
}
