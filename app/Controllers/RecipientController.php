<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * RecipientController — Stub
 *
 * Full implementation in Phase 7.
 */
class RecipientController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('recipients/index', [
            'pageTitle' => 'Recipients — ' . setting('site_name', 'Emirates'),
        ]);
    }
}