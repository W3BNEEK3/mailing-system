<?php
/**
 * resources/components/tables/_sortable-header.php
 *
 * A <th> element that triggers an HTMX sort request when clicked.
 *
 * Variables:
 *   string $label      Column display label (e.g. 'Date Added')
 *   string $column     Column identifier passed to the server (e.g. 'created_at')
 *   string $currentCol The currently active sort column
 *   string $currentDir The current sort direction ('asc' or 'desc')
 *   string $sortUrl    Base URL to POST/GET with sort params (e.g. '/recipients')
 *   string $target     HTMX hx-target (e.g. '#recipient-table')
 */

$label      = $label      ?? '';
$column     = $column     ?? '';
$currentCol = $currentCol ?? '';
$currentDir = $currentDir ?? 'asc';
$sortUrl    = $sortUrl    ?? '';
$target     = $target     ?? 'body';

// Determine the new direction when this column is clicked
$isActive = $currentCol === $column;
$newDir   = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';

// Build the full sort URL
$url = url($sortUrl . '?' . http_build_query([
    'sort' => $column,
    'dir'  => $newDir,
]));
?>

<th
    scope="col"
    class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider
           whitespace-nowrap select-none"
>
    <button
        type="button"
        hx-get="<?= e($url) ?>"
        hx-target="<?= e($target) ?>"
        hx-push-url="true"
        class="inline-flex items-center gap-1 hover:text-slate-800 transition-colors
               <?= $isActive ? 'text-slate-800' : 'text-slate-500' ?>"
    >
        <?= e($label) ?>

        <!-- Sort direction indicator -->
        <?php if ($isActive): ?>
            <i class="bi bi-arrow-<?= $currentDir === 'asc' ? 'up' : 'down' ?> text-xs"></i>
        <?php else: ?>
            <i class="bi bi-arrow-down-up text-xs opacity-30"></i>
        <?php endif; ?>
    </button>
</th>