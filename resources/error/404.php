<?php
/**
 * resources/error/404.php
 *
 * Page Not Found error view.
 * Rendered inside resources/layouts/error.php.
 */
?>
<style>
    .error-code    { font-size: 6rem; font-weight: 900; color: #e2e8f0; line-height: 1; }
    .error-heading { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #0f172a; }
    .error-body    { color: #64748b; font-size: 0.9375rem; line-height: 1.6; max-width: 30rem; text-align: center; }
    .error-actions { display: flex; gap: 0.75rem; margin-top: 2rem; flex-wrap: wrap; justify-content: center; }
    .btn-primary   {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #1d4ed8; color: #fff;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none; transition: background 0.15s;
    }
    .btn-primary:hover { background: #1e40af; }
    .btn-ghost {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #f1f5f9; color: #475569;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none; transition: background 0.15s;
    }
    .btn-ghost:hover { background: #e2e8f0; }
</style>

<div style="text-align:center;">
    <p class="error-code">404</p>
    <h1 class="error-heading">Page not found</h1>
    <p class="error-body">
        The page you're looking for doesn't exist or may have been moved.
        Check the URL or head back to the compose page.
    </p>
    <div class="error-actions">
        <a href="/" class="btn-primary">
            <i class="bi bi-house-fill"></i>
            Go home
        </a>
        <a href="javascript:history.back()" class="btn-ghost">
            <i class="bi bi-arrow-left"></i>
            Go back
        </a>
    </div>
</div>