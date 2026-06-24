<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Core\Request;
use App\Core\Response;

/**
 * MiddlewareInterface
 *
 * All middleware must implement this interface.
 *
 * The handle() method receives:
 *   $request — the current HTTP request
 *   $next    — a callable that runs the next middleware (or the controller)
 *
 * To allow the request to continue:   return $next($request);
 * To block the request:               return Response::redirect('/login');
 */
interface MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response;
}
