<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use Closure;

class SuperAdminMiddleware
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->auth->isSuperAdmin()) {
            if ($request->isHtmx()) {
                return Response::html('')->htmxTrigger('showToast', [
                    'type' => 'error',
                    'message' => 'Access denied. Super Admin privileges required.'
                ]);
            }
            return Response::redirect('/compose');
        }

        return $next($request);
    }
}
