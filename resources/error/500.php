<?php
/**
 * resources/error/500.php
 * Internal Server Error — shown in production (APP_DEBUG=false).
 *
 * Variables:
 *   string $errorRef  An optional short error reference for support (e.g. a timestamp)
 */
$errorRef = $errorRef ?? date('YmdHis');
?>
<style>
    .error-code    { font-size: 6rem; font-weight: 900; color: #e2e8f0; line-height: 1; }
    .error-heading { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #0f172a; }
    .error-body    { color: #64748b; font-size: 0.9375rem; line-height: 1.6; max-width: 30rem; text-align: center; }
    .error-ref     { font-family: monospace; font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem; }
    .error-actions { display: flex; gap: 0.75rem; margin-top: 2rem; flex-wrap: wrap; justify-content: center; }
    .btn-primary   {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #1d4ed8; color: #fff;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none;
    }
</style>

<div style="text-align:center;">
    <p class="error-code">500</p>
    <h1 class="error-heading">Something went wrong</h1>
    <p class="error-body">
        An unexpected error occurred. The error has been logged.
        Try refreshing the page — if the problem persists, check the application logs.
    </p>
    <p class="error-ref">Error ref: <?= htmlspecialchars($errorRef, ENT_QUOTES, 'UTF-8') ?></p>
    <div class="error-actions">
        <a href="/" class="btn-primary">
            <i class="bi bi-arrow-repeat"></i>
            Try again
        </a>
    </div>
</div>