<?php
use App\Helpers\Html;

$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

$tabActive = function (string $path) use ($currentUri): bool {
    return str_starts_with(strtok($currentUri, '?'), $path);
};
?>

<style>
    /* Hide scrollbar for a clean mobile swipe experience */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<nav
    class="fixed bottom-0 inset-x-0 z-30 flex bg-white border-t border-slate-200 h-16 shadow-[0_-1px_6px_rgba(0,0,0,0.06)] overflow-x-auto hide-scrollbar"
    role="navigation"
    aria-label="Mobile navigation"
>
    <div class="flex items-stretch min-w-full px-2">
        <?php
        $tabs = [
            ['Compose',    'bi-pencil-square', '/compose',              '/compose'],
            ['Recipients', 'bi-people',        '/recipients',           '/recipients'],
        ];

        if (session()->get('user_role') === 'super_admin') {
            $tabs = array_merge($tabs, [
                ['Logs',       'bi-clock-history', '/logs',                 '/logs'],
                ['Templates',  'bi-grid-1x2',      '/settings/templates',   '/settings/templates'],
                ['Credentials','bi-key',           '/settings/credentials', '/settings/credentials'],
                ['General',    'bi-gear',          '/settings/general',     '/settings/general'],
            ]);
        }
        ?>

        <?php foreach ($tabs as [$label, $icon, $prefix, $href]): ?>
            <?php $isActive = $tabActive($prefix); ?>
            <a
                href="<?= url($href) ?>"
                class="flex flex-col items-center justify-center gap-1 min-h-[44px] px-3 sm:px-4
                       text-[10px] font-medium transition-colors whitespace-nowrap
                       <?= $isActive
                           ? 'text-[color:var(--color-primary)]'
                           : 'text-slate-400 hover:text-slate-600'
                       ?>"
                aria-current="<?= $isActive ? 'page' : 'false' ?>"
            >
                <i class="bi <?= e($icon) ?> text-lg leading-none
                    <?= $isActive ? 'text-[color:var(--color-primary)]' : '' ?>"></i>
                <span><?= e($label) ?></span>
            </a>
        <?php endforeach; ?>

        <!-- Logout Button -->
        <form method="POST" action="<?= url('/logout') ?>" class="flex">
            <?= csrf_field() ?>
            <button
                type="submit"
                class="flex flex-col items-center justify-center gap-1 min-h-[44px] px-3 sm:px-4
                       text-[10px] font-medium transition-colors whitespace-nowrap text-slate-400 hover:text-red-500"
                aria-label="Sign Out"
            >
                <i class="bi bi-box-arrow-left text-lg leading-none"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</nav>