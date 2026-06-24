<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * TemplateController — Stub
 *
 * Full implementation in Phase 5.
 */
class TemplateController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('settings/templates/index', [
            'pageTitle' => 'Templates — ' . setting('site_name', 'Emirates'),
        ]);
    }
}