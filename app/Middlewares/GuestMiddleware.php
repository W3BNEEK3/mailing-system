<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Interfaces\MiddlewareInterface;

/**
 * GuestMiddleware
 *
 * Applied to routes that should only be accessible to guests (non-logged-in users).
 * Specifically, this prevents authenticated users from revisiting /login.
 *
 * If the user IS authenticated: redirect to /compose
 * If the user is NOT authenticated: pass through
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (session()->has('user_id')) {
            // Already logged in — send them to the compose page
            return Response::redirect('/compose');
        }

        // Not logged in — show the guest page (login form)
        return $next($request);
    }
}
