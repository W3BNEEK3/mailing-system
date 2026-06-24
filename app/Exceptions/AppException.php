<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * AppException — Base exception for all application-level exceptions.
 *
 * All custom exceptions extend this so we can distinguish our own exceptions
 * from PHP's built-in exceptions in the ErrorHandler.
 */
class AppException extends \RuntimeException
{
    // No additional code needed — inherits everything from RuntimeException.
    // The $code property (from the parent) maps to HTTP status codes in ErrorHandler.
}
