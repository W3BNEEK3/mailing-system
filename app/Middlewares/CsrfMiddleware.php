<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AppException;
use App\Interfaces\MiddlewareInterface;

/**
 * CsrfMiddleware
 *
 * Validates the CSRF token on all state-changing requests (POST, PUT, PATCH, DELETE).
 * GET and HEAD requests are skipped — they should never change state.
 *
 * The CSRF token is checked in two places:
 *   1. The '_csrf' hidden form field (for regular HTML form submissions)
 *   2. The 'X-CSRF-Token' request header (set by app.js for HTMX requests)
 *
 * Both are compared against the token stored in the user's session.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    // These HTTP methods don't change state, so no CSRF check is needed
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function handle(Request $request, callable $next): Response
    {
        // Skip CSRF check for safe (read-only) methods
        if (in_array($request->method(), static::SAFE_METHODS, true)) {
            return $next($request);
        }

        // Get the token that was submitted with the request
        // Check both the form field and the HTMX header
        $submittedToken = $request->post('_csrf')           // hidden form field
                       ?? $request->header('X-CSRF-Token'); // HTMX header

        // Get the token we stored in the session
        $sessionToken = session()->csrfToken();

        // Compare the two tokens securely
        // hash_equals prevents timing attacks (where comparing character-by-character
        // leaks information about how long the tokens are)
        if ($submittedToken === null || !hash_equals($sessionToken, $submittedToken)) {
            throw new AppException('CSRF token mismatch. Please refresh the page and try again.', 419);
        }

        // Token is valid — continue
        return $next($request);
    }
}
