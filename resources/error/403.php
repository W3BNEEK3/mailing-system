<?php
/**
 * resources/error/403.php
 * Access Denied.
 */
?>
<style>
    .error-code    { font-size: 6rem; font-weight: 900; color: #fee2e2; line-height: 1; }
    .error-heading { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #0f172a; }
    .error-body    { color: #64748b; font-size: 0.9375rem; line-height: 1.6; max-width: 30rem; text-align: center; }
    .error-actions { display: flex; gap: 0.75rem; margin-top: 2rem; flex-wrap: wrap; justify-content: center; }
    .btn-primary   {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #1d4ed8; color: #fff;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none;
    }
</style>

<div style="text-align:center;">
    <p class="error-code">403</p>
    <h1 class="error-heading">Access denied</h1>
    <p class="error-body">
        You don't have permission to view this page.
        If you believe this is a mistake, try signing in again.
    </p>
    <div class="error-actions">
        <a href="/login" class="btn-primary">
            <i class="bi bi-box-arrow-in-right"></i>
            Sign in
        </a>
    </div>
</div>