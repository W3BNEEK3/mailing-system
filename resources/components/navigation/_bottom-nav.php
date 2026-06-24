<?php
/**
 * resources/components/navigation/_bottom-nav.php
 *
 * Mobile-only bottom navigation bar.
 * Fixed to the bottom of the viewport (fixed bottom-0).
 * Hidden on md+ breakpoints (the sidebar takes over).
 *
 * Touch targets are 48px tall minimum (py-3 + icon + label), meeting WCAG 2.5.5.
 * The Settings tab routes to /settings/general as the default settings sub-page.
 */

use App\Helpers\Html;

$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

/**
 * Returns 'active' if the given path is a prefix of the current URI.
 * Used for the bottom nav tab active state.
 */
$tabActive = function (string $path) use ($currentUri): bool {
    return str_starts_with(strtok($currentUri, '?'), $path);
};
?>

<nav
    class="fixed bottom-0 inset-x-0 z-30 flex items-stretch
           bg-white border-t border-slate-200 h-16 shadow-[0_-1px_6px_rgba(0,0,0,0.06)]"
    role="navigation"
    aria-label="Mobile navigation"
>
    <?php
    /*
     * Tab definitions: [label, icon-class, route-prefix, href]
     * route-prefix is used for active detection — /settings matches both
     * /settings/general, /settings/templates, and /settings/credentials.
     */
    $tabs = [
        ['Compose',    'bi-pencil-square', '/compose',    '/compose'],
        ['Recipients', 'bi-people',        '/recipients', '/recipients'],
        ['Logs',       'bi-clock-history', '/logs',       '/logs'],
        ['Settings',   'bi-gear',          '/settings',   '/settings/general'],
    ];
    ?>

    <?php foreach ($tabs as [$label, $icon, $prefix, $href]): ?>
        <?php $isActive = $tabActive($prefix); ?>
        <a
            href="<?= url($href) ?>"
            class="flex-1 flex flex-col items-center justify-center gap-1 min-h-[44px]
                   text-xs font-medium transition-colors
                   <?= $isActive
                       ? 'text-[color:var(--color-primary)]'
                       : 'text-slate-400 hover:text-slate-600'
                   ?>"
            aria-current="<?= $isActive ? 'page' : 'false' ?>"
        >
            <i class="bi <?= e($icon) ?> text-xl leading-none
                <?= $isActive ? 'text-[color:var(--color-primary)]' : '' ?>"></i>
            <span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>