<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * ComposeController — Stub
 *
 * Full implementation in Phase 8.
 * This stub allows the /compose route to resolve without a 500 error
 * so the application shell and navigation can be tested in Phase 3.
 */
class ComposeController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('compose/index', [
            'pageTitle' => 'Compose — ' . setting('site_name', 'Emirates'),
        ]);
    }
}