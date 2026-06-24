<?php

use App\Controllers\AuthController;
use App\Controllers\ComposeController;
use App\Controllers\RecipientController;
use App\Controllers\LogController;
use App\Controllers\TemplateController;
use App\Controllers\CredentialController;
use App\Controllers\SettingsController;
use App\Controllers\HomeController;

// ── Guest-only routes ─────────────────────────────────────────────────────
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/login',  [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// ── Authenticated routes (auth + CSRF on all state-changing requests) ─────
$router->group(['middleware' => ['auth', 'csrf']], function ($router) {

    // Auth
    $router->post('/logout', [AuthController::class, 'logout']);

    // Root redirect → compose (the main landing page after login)
    $router->get('/', [HomeController::class, 'index']);

    // ── Core pages ────────────────────────────────────────────────────────
    $router->get('/compose',    [ComposeController::class,   'index']);
    $router->get('/recipients', [RecipientController::class, 'index']);
    $router->get('/logs',       [LogController::class,       'index']);

    // ── Settings sub-pages ────────────────────────────────────────────────
    $router->get('/settings/general',     [SettingsController::class,    'index']);
    $router->get('/settings/templates',   [TemplateController::class,    'index']);
    $router->get('/settings/credentials', [CredentialController::class,  'index']);

    /*
     * ── Routes registered for future phases ──────────────────────────────
     * These are declared here (commented in) early so the Router knows
     * about them and the middleware is applied automatically.
     * Uncomment each block as the relevant phase is implemented.
     */

    // Phase 4 — General Settings
    // $router->post('/settings/general', [SettingsController::class, 'update']);

    // Phase 5 — Templates CRUD
    // $router->get('/settings/templates/create',          [TemplateController::class, 'create']);
    // $router->post('/settings/templates',                [TemplateController::class, 'store']);
    // $router->get('/settings/templates/{id}/edit',       [TemplateController::class, 'edit']);
    // $router->post('/settings/templates/{id}',           [TemplateController::class, 'update']);
    // $router->delete('/settings/templates/{id}',         [TemplateController::class, 'destroy']);
    // $router->post('/settings/templates/{id}/duplicate', [TemplateController::class, 'duplicate']);
    // $router->get('/settings/templates/{id}/preview',    [TemplateController::class, 'preview']);
    // $router->post('/settings/templates/preview-draft',  [TemplateController::class, 'previewDraft']);

    // Phase 6 — Credentials
    // $router->post('/settings/credentials',      [CredentialController::class, 'store']);
    // $router->post('/settings/credentials/test', [CredentialController::class, 'test']);

    // Phase 7 — Recipients CRUD
    // $router->get('/recipients/create',     [RecipientController::class, 'create']);
    // $router->post('/recipients',           [RecipientController::class, 'store']);
    // $router->get('/recipients/{id}/edit',  [RecipientController::class, 'edit']);
    // $router->post('/recipients/{id}',      [RecipientController::class, 'update']);
    // $router->delete('/recipients/{id}',    [RecipientController::class, 'destroy']);
    // $router->get('/recipients/import',     [RecipientController::class, 'import']);
    // $router->post('/recipients/import',    [RecipientController::class, 'import']);
    // $router->post('/recipients/{id}/suppress', [RecipientController::class, 'suppress']);

    // Phase 8 — Compose & Drafts
    // $router->post('/compose/send',          [ComposeController::class, 'send']);
    // $router->post('/compose/preview',       [ComposeController::class, 'preview']);
    // $router->post('/compose/load-template', [ComposeController::class, 'loadTemplate']);
    // ... (draft routes, translation routes)

    // Phase 10 — Logs
    // $router->get('/logs/{id}',   [LogController::class, 'show']);
    // $router->post('/logs/clear', [LogController::class, 'clear']);
});

// ── Webhook routes (no auth — validated by signature) ─────────────────────
// $router->post('/webhooks/resend', [WebhookController::class, 'resend']);

// ── Storage file serving (auth-protected) ─────────────────────────────────
// Uncomment in Phase 4 when FileUploadService and StorageController are built.
// $router->get('/storage/{type}/{filename}', [StorageController::class, 'serve']);