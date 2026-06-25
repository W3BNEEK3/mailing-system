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
    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// ── Authenticated routes (auth + CSRF on all state-changing requests) ─────
$router->group(['middleware' => ['auth', 'csrf']], function ($router) {

    // Auth
    $router->post('/logout', [AuthController::class, 'logout']);

    // Root redirect → compose (the main landing page after login)
    $router->get('/', [HomeController::class, 'index']);

    // ── Core pages ────────────────────────────────────────────────────────
    $router->get('/compose', [ComposeController::class, 'index']);
    $router->get('/recipients', [RecipientController::class, 'index']);
    // ── Super Admin Only ──────────────────────────────────────────────────
    $router->group(['middleware' => ['super_admin']], function ($router) {
        $router->get('/logs', [LogController::class, 'index']);

        // ── Settings sub-pages ────────────────────────────────────────────────
        $router->get('/settings/general', [SettingsController::class, 'index']);
        
        // NOTE: /preview-draft must appear BEFORE /{id} routes to avoid the router
        // matching 'preview-draft' as an integer id parameter. Register it first.
        $router->post('/settings/templates/preview-draft', [\App\Controllers\TemplateController::class, 'previewDraft']);

        $router->get ('/settings/templates', [\App\Controllers\TemplateController::class, 'index']);
        $router->get ('/settings/templates/create', [\App\Controllers\TemplateController::class, 'create']);
        $router->post('/settings/templates', [\App\Controllers\TemplateController::class, 'store']);
        $router->get ('/settings/templates/{id}/edit', [\App\Controllers\TemplateController::class, 'edit']);
        $router->post('/settings/templates/{id}', [\App\Controllers\TemplateController::class, 'update']);
        $router->post('/settings/templates/{id}/delete', [\App\Controllers\TemplateController::class, 'destroy']);
        $router->post('/settings/templates/{id}/duplicate', [\App\Controllers\TemplateController::class, 'duplicate']);
        $router->get ('/settings/templates/{id}/preview', [\App\Controllers\TemplateController::class, 'preview']);
        
        $router->post('/settings/credentials/test', [\App\Controllers\CredentialController::class, 'test']);
        $router->get ('/settings/credentials', [\App\Controllers\CredentialController::class, 'index']);
        $router->post('/settings/credentials', [\App\Controllers\CredentialController::class, 'store']);
    });


    $router->get ('/recipients', [\App\Controllers\RecipientController::class, 'index']);
    $router->get ('/recipients/create', [\App\Controllers\RecipientController::class, 'create']);
    $router->post('/recipients', [\App\Controllers\RecipientController::class, 'store']);
    $router->get ('/recipients/import', [\App\Controllers\RecipientController::class, 'importPage']);
    $router->post('/recipients/import', [\App\Controllers\RecipientController::class, 'import']);
    $router->post('/recipients/batch-save', [\App\Controllers\RecipientController::class, 'batchSave']);
    $router->get ('/recipients/{id}/edit', [\App\Controllers\RecipientController::class, 'edit']);
    $router->post('/recipients/{id}', [\App\Controllers\RecipientController::class, 'update']);
    $router->post('/recipients/{id}/delete', [\App\Controllers\RecipientController::class, 'destroy']);
    $router->post('/recipients/{id}/suppress', [\App\Controllers\RecipientController::class, 'suppress']);
    $router->post('/recipients/{id}/unsuppress', [\App\Controllers\RecipientController::class, 'unsuppress']);


    $router->get ('/compose', [\App\Controllers\ComposeController::class, 'index']);
    $router->post('/compose/send', [\App\Controllers\ComposeController::class, 'send']);
    $router->post('/compose/preview', [\App\Controllers\ComposeController::class, 'preview']);
    $router->post('/compose/load-template', [\App\Controllers\ComposeController::class, 'loadTemplate']);
    $router->get ('/compose/recipient-hints', [\App\Controllers\ComposeController::class, 'recipientHints']);
    $router->post('/compose/translate', [\App\Controllers\TranslationController::class, 'translate']);
    $router->post('/compose/translate/revert', [\App\Controllers\TranslationController::class, 'revert']);

    // ── Drafts ────────────────────────────────────────────────────────────────────

    // NOTE: /autosave must be registered BEFORE /{id} routes to prevent the router
    // from matching the string 'autosave' as an integer id parameter.
    $router->post('/drafts/autosave', [\App\Controllers\DraftController::class, 'autosave']);
    $router->get ('/drafts', [\App\Controllers\DraftController::class, 'index']);
    $router->post('/drafts', [\App\Controllers\DraftController::class, 'store']);
    $router->get ('/drafts/{id}/load', [\App\Controllers\DraftController::class, 'load']);
    $router->post('/drafts/{id}/delete', [\App\Controllers\DraftController::class, 'destroy']);
    /*
     * ── Routes registered for future phases ──────────────────────────────
     * These are declared here (commented in) early so the Router knows
     * about them and the middleware is applied automatically.
     * Uncomment each block as the relevant phase is implemented.
     */

    // Phase 4 — General Settings
    $router->post('/settings/general', [SettingsController::class, 'update']);

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
    $router->get ('/logs/{id}',   [LogController::class, 'show']);
    $router->post('/logs/clear',  [LogController::class, 'clear']);
});

// ── Webhook routes (no auth — validated by signature) ─────────────────────
// $router->post('/webhooks/resend', [WebhookController::class, 'resend']);

// ── Storage file serving (auth-protected) ─────────────────────────────────
// Uncomment in Phase 4 when FileUploadService and StorageController are built.
// $router->get('/storage/{type}/{filename}', [StorageController::class, 'serve']);