<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Interfaces\MiddlewareInterface;

/**
 * AuthMiddleware
 *
 * Protects routes that require a logged-in user.
 *
 * If the user is NOT authenticated:
 *   - Regular requests: redirect to /login
 *   - HTMX requests: send HX-Redirect header (HTMX handles the navigation)
 *
 * If the user IS authenticated:
 *   - Pass through to the next middleware or controller
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Check if user_id is in the session (set on login)
        if (!session()->has('user_id')) {
            // User is not logged in

            if ($request->isHtmx()) {
                // HTMX request: tell the browser to redirect via the HX-Redirect header
                // HTMX will navigate to /login client-side
                return Response::html('')
                    ->withStatus(200)
                    ->htmxRedirect('/login');
            }

            // Regular request: standard HTTP redirect to login page
            return Response::redirect('/login');
        }

        // User is authenticated — continue to the next middleware or controller
        return $next($request);
    }
}
