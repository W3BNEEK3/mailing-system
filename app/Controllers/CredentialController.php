<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * CredentialController — Stub
 *
 * Full implementation in Phase 6.
 */
class CredentialController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('settings/credentials', [
            'pageTitle' => 'Credentials — ' . setting('site_name', 'Emirates'),
        ]);
    }
}