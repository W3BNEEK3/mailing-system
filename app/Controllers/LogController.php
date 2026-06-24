<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * LogController — Stub
 *
 * Full implementation in Phase 10.
 */
class LogController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('logs/index', [
            'pageTitle' => 'Email Logs — ' . setting('site_name', 'Emirates'),
        ]);
    }
}