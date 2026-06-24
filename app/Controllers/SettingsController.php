<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * SettingsController — Stub
 *
 * Full implementation in Phase 4.
 */
class SettingsController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('settings/general', [
            'pageTitle' => 'General Settings — ' . setting('site_name', 'Emirates'),
        ]);
    }
}