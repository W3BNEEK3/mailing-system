<?php
/**
 * resources/components/tables/_pagination.php
 *
 * Renders page navigation for any paginated listing.
 * Uses HTMX to load pages without a full page reload.
 *
 * Variables:
 *   int    $page      Current page number (1-indexed)
 *   int    $lastPage  Total number of pages
 *   string $baseUrl   Base URL for page links, e.g. '/recipients'
 *                     The ?page=N query string is appended automatically.
 *   string $target    HTMX hx-target value (e.g. '#recipient-table').
 *                     If null, falls back to standard href links.
 */

$page     = (int) ($page     ?? 1);
$lastPage = (int) ($lastPage ?? 1);
$baseUrl  = $baseUrl ?? '';
$target   = $target  ?? null;

// Don't render pagination if there's only one page
if ($lastPage <= 1) return;

/**
 * Build the URL for a given page number.
 */
$pageUrl = function (int $n) use ($baseUrl): string {
    return url($baseUrl . '?page=' . $n);
};

/**
 * Build the HTMX attributes string for a page link.
 * If $target is set, page switches happen asynchronously via HTMX.
 */
$htmxAttrs = function (int $n) use ($pageUrl, $target): string {
    if ($target === null) return '';
    return 'hx-get="' . e($pageUrl($n)) . '" hx-target="' . e($target) . '" hx-push-url="true"';
};

// Build a compact window of page numbers around the current page
// e.g. for page 7 of 20: [1, …, 5, 6, 7, 8, 9, …, 20]
$window    = 2; // pages on each side of the current page
$pagesToShow = [];

for ($i = 1; $i <= $lastPage; $i++) {
    if (
        $i === 1 ||
        $i === $lastPage ||
        ($i >= $page - $window && $i <= $page + $window)
    ) {
        $pagesToShow[] = $i;
    }
}
?>

<nav
    class="flex items-center justify-between px-1 py-4"
    aria-label="Pagination"
>
    <!-- Page count summary -->
    <p class="text-sm text-slate-500">
        Page <span class="font-medium text-slate-700"><?= $page ?></span>
        of <span class="font-medium text-slate-700"><?= $lastPage ?></span>
    </p>

    <!-- Page number links -->
    <div class="flex items-center gap-1" role="list">

        <!-- Previous -->
        <?php if ($page > 1): ?>
            <a href="<?= e($pageUrl($page - 1)) ?>"
               <?= $htmxAttrs($page - 1) ?>
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                      text-sm text-slate-500 hover:bg-slate-100 transition"
               aria-label="Previous page">
                <i class="bi bi-chevron-left text-xs"></i>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                         text-sm text-slate-300 cursor-not-allowed" aria-hidden="true">
                <i class="bi bi-chevron-left text-xs"></i>
            </span>
        <?php endif; ?>

        <!-- Page numbers -->
        <?php
        $prev = null;
        foreach ($pagesToShow as $n):
            // Insert ellipsis for gaps in the page range
            if ($prev !== null && $n - $prev > 1):
        ?>
                <span class="inline-flex items-center justify-center w-8 h-8 text-sm text-slate-400"
                      aria-hidden="true">…</span>
        <?php
            endif;
            $prev = $n;
        ?>

            <?php if ($n === $page): ?>
                <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm
                           font-semibold text-white"
                    style="background-color: var(--color-primary);"
                    aria-current="page"
                >
                    <?= $n ?>
                </span>
            <?php else: ?>
                <a href="<?= e($pageUrl($n)) ?>"
                   <?= $htmxAttrs($n) ?>
                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm
                          text-slate-600 hover:bg-slate-100 transition">
                    <?= $n ?>
                </a>
            <?php endif; ?>

        <?php endforeach; ?>

        <!-- Next -->
        <?php if ($page < $lastPage): ?>
            <a href="<?= e($pageUrl($page + 1)) ?>"
               <?= $htmxAttrs($page + 1) ?>
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                      text-sm text-slate-500 hover:bg-slate-100 transition"
               aria-label="Next page">
                <i class="bi bi-chevron-right text-xs"></i>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                         text-sm text-slate-300 cursor-not-allowed" aria-hidden="true">
                <i class="bi bi-chevron-right text-xs"></i>
            </span>
        <?php endif; ?>

    </div>
</nav>