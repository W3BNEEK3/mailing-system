<?php
/**
 * resources/components/navigation/_sidebar.php
 *
 * Desktop sidebar navigation.
 * Included directly by resources/layouts/app.php.
 *
 * Requires:
 *   - Html::active(string $path): string — returns 'active' if URI matches
 *   - setting(string $key): mixed         — reads from settings table
 *   - url(string $path): string           — builds full URL
 *   - e(string $value): string            — HTML-escapes a value
 *   - csrf_field(): string                — renders CSRF hidden input
 */

use App\Helpers\Html;

// Read the current request URI for active link detection
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

/**
 * Helper: returns 'nav-link active' if the given path matches the current URI,
 * otherwise returns 'nav-link'.
 *
 * We do a prefix match for sub-pages (e.g. /settings matches /settings/general).
 * Exact match is used for root paths like /compose.
 */
$navClass = function (string $path) use ($currentUri): string {
    $isActive = $path === '/'
        ? $currentUri === '/'
        : str_starts_with(strtok($currentUri, '?'), $path);

    return 'nav-link ' . ($isActive ? 'active' : '');
};

// Site logo/name from settings
$siteLogo = setting('site_logo_path', null);
$siteName = setting('site_name', 'Emirates');

// The authenticated user's display name from the session
$userName = session()->get('user_name', 'User');
?>

<nav class="flex flex-col h-full bg-white border-r border-slate-200">

    <!-- ── BRAND ─────────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3 h-16 px-5 border-b border-slate-100 flex-shrink-0">
        <?php if ($siteLogo): ?>
            <img
                src="<?= e(url('/storage/logos/site/' . basename((string)$siteLogo))) ?>"
                alt="<?= e($siteName) ?>"
                class="h-8 w-auto object-contain"
            >
        <?php else: ?>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background-color: var(--color-primary);">
                <i class="bi bi-send-fill text-white text-sm"></i>
            </div>
            <span class="font-bold text-slate-900 text-sm tracking-tight truncate">
                <?= e($siteName) ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- ── NAV LINKS ─────────────────────────────────────────────────────── -->
    <!--
        Each link is a full-width flex row: icon + label.
        The active class applies a tinted background and primary-colour text.
        Non-active links are muted slate with a hover state.
    -->
    <div class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">

        <!-- Compose -->
        <a href="<?= url('/compose') ?>"
           class="<?= $navClass('/compose') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-pencil-square text-base text-slate-400 flex-shrink-0"></i>
            <span>Compose</span>
        </a>

        <!-- Recipients -->
        <a href="<?= url('/recipients') ?>"
           class="<?= $navClass('/recipients') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-people text-base text-slate-400 flex-shrink-0"></i>
            <span>Recipients</span>
        </a>

        <?php if (session()->get('user_role') === 'super_admin'): ?>
        <!-- Email Logs -->
        <a href="<?= url('/logs') ?>"
           class="<?= $navClass('/logs') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-clock-history text-base text-slate-400 flex-shrink-0"></i>
            <span>Email Logs</span>
        </a>

        <!-- Divider before Settings section -->
        <div class="pt-3 pb-1 px-3">
            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">
                Settings
            </span>
        </div>

        <!-- Templates -->
        <a href="<?= url('/settings/templates') ?>"
           class="<?= $navClass('/settings/templates') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-grid-1x2 text-base text-slate-400 flex-shrink-0"></i>
            <span>Templates</span>
        </a>

        <!-- Credentials -->
        <a href="<?= url('/settings/credentials') ?>"
           class="<?= $navClass('/settings/credentials') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-key text-base text-slate-400 flex-shrink-0"></i>
            <span>Credentials</span>
        </a>

        <!-- General Settings -->
        <a href="<?= url('/settings/general') ?>"
           class="<?= $navClass('/settings/general') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-gear text-base text-slate-400 flex-shrink-0"></i>
            <span>General</span>
        </a>
        <?php endif; ?>

    </div>

    <!-- ── USER + LOGOUT ─────────────────────────────────────────────────── -->
    <!--
        Pinned to the bottom of the sidebar.
        Shows the logged-in user's name and a sign-out button.
    -->
    <div class="flex-shrink-0 border-t border-slate-100 px-3 py-4">
        <div class="flex items-center gap-3 px-3 py-2 mb-1">
            <!-- User avatar placeholder — first initial in a coloured circle -->
            <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                 style="background-color: var(--color-primary);">
                <?= e(strtoupper(mb_substr($userName, 0, 1))) ?>
            </div>
            <span class="text-sm font-medium text-slate-700 truncate flex-1">
                <?= e($userName) ?>
            </span>
        </div>

        <!-- Logout form: POST is required to prevent CSRF logout attacks. -->
        <form method="POST" action="<?= url('/logout') ?>" class="w-full">
            <?= csrf_field() ?>
            <button
                type="submit"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                       text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors text-left"
            >
                <i class="bi bi-box-arrow-left text-base flex-shrink-0"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>

</nav>