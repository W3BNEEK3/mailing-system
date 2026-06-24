<?php

declare(strict_types=1);

/**
 * routes/web.php
 *
 * All application HTTP routes.
 *
 * $router is injected by bootstrap/app.php before this file is loaded.
 * Controllers don't exist yet in Phase 0 — they'll be built in Phase 2 onwards.
 * Registering routes here just means they're in the table; they won't throw
 * until someone actually requests them.
 *
 * Middleware:
 *   'auth'  => User must be logged in
 *   'guest' => User must NOT be logged in (for login page)
 *   'csrf'  => Validate CSRF token on state-changing requests
 */

use App\Controllers\{
    AuthController,
    ComposeController,
    DraftController,
    RecipientController,
    LogController,
    TemplateController,
    CredentialController,
    SettingsController,
    TranslationController,
    StorageController,
    };


// ── Guest-only routes (redirect to /compose if already logged in) ──────────
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/login',  [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// ── Logout (any authenticated user can log out) ────────────────────────────
$router->post('/logout', [AuthController::class, 'logout']);

// ── Authenticated routes ───────────────────────────────────────────────────
$router->group(['middleware' => ['auth', 'csrf']], function ($router) {
    
    // Root redirect to compose
    $router->get('/', [ComposeController::class, 'index']);

    // ── Compose ──────────────────────────────────────────────────────────
    $router->get('/compose',                  [ComposeController::class, 'index']);
    $router->post('/compose/send',            [ComposeController::class, 'send']);
    $router->post('/compose/preview',         [ComposeController::class, 'preview']);
    $router->post('/compose/load-template',   [ComposeController::class, 'loadTemplate']);
    $router->post('/compose/translate',       [TranslationController::class, 'translate']);
    $router->post('/compose/translate/revert',[TranslationController::class, 'revert']);

    // ── Drafts ────────────────────────────────────────────────────────────
    $router->get('/drafts',              [DraftController::class, 'index']);
    $router->post('/drafts',             [DraftController::class, 'store']);
    $router->post('/drafts/autosave',    [DraftController::class, 'autosave']);
    $router->get('/drafts/{id}/load',    [DraftController::class, 'load']);
    $router->put('/drafts/{id}',         [DraftController::class, 'update']);
    $router->delete('/drafts/{id}',      [DraftController::class, 'destroy']);

    // ── Recipients ────────────────────────────────────────────────────────
    $router->get('/recipients',              [RecipientController::class, 'index']);
    $router->get('/recipients/create',       [RecipientController::class, 'create']);
    $router->post('/recipients',             [RecipientController::class, 'store']);
    $router->get('/recipients/import',       [RecipientController::class, 'importPage']);
    $router->post('/recipients/import',      [RecipientController::class, 'import']);
    $router->get('/recipients/{id}/edit',    [RecipientController::class, 'edit']);
    $router->put('/recipients/{id}',         [RecipientController::class, 'update']);
    $router->delete('/recipients/{id}',      [RecipientController::class, 'destroy']);
    $router->post('/recipients/{id}/suppress',[RecipientController::class, 'suppress']);

    // ── Email Logs ────────────────────────────────────────────────────────
    $router->get('/logs',              [LogController::class, 'index']);
    $router->get('/logs/{id}',         [LogController::class, 'show']);
    $router->delete('/logs/clear',     [LogController::class, 'clear']);

    // ── Settings: Email Templates ─────────────────────────────────────────
    $router->get('/settings/templates',                   [TemplateController::class, 'index']);
    $router->get('/settings/templates/create',            [TemplateController::class, 'create']);
    $router->post('/settings/templates',                  [TemplateController::class, 'store']);
    $router->post('/settings/templates/preview-draft',    [TemplateController::class, 'previewDraft']);
    $router->get('/settings/templates/{id}/edit',         [TemplateController::class, 'edit']);
    $router->put('/settings/templates/{id}',              [TemplateController::class, 'update']);
    $router->delete('/settings/templates/{id}',           [TemplateController::class, 'destroy']);
    $router->post('/settings/templates/{id}/duplicate',   [TemplateController::class, 'duplicate']);
    $router->get('/settings/templates/{id}/preview',      [TemplateController::class, 'preview']);

    // ── Settings: Email Credentials ───────────────────────────────────────
    $router->get('/settings/credentials',          [CredentialController::class, 'index']);
    $router->post('/settings/credentials',         [CredentialController::class, 'store']);
    $router->post('/settings/credentials/test',    [CredentialController::class, 'test']);

    // ── Settings: General ─────────────────────────────────────────────────
    $router->get('/settings/general',  [SettingsController::class, 'index']);
    $router->post('/settings/general', [SettingsController::class, 'update']);

    // ── Storage: Serve uploaded files ─────────────────────────────────────
    // Files in storage/ are outside the web root, so we serve them through PHP
    $router->get('/storage/{type}/{filename}', [StorageController::class, 'serve']);
});
