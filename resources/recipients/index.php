<?php
/*
 * resources/recipients/index.php
 *
 * Main recipients listing page.
 *
 * Features:
 *   - HTMX live search (updates the table body without a full page reload)
 *   - Pagination (via the _pagination.php component from Phase 3)
 *   - Add Recipient and Import CSV buttons
 *   - Row-level actions: Edit, Suppress/Restore, Delete (all HTMX-enhanced)
 *
 * @var \App\Models\Recipient[] $recipients   Current page of recipients
 * @var array                   $paginated    Pagination metadata
 * @var string                  $search       Current search query
 * @var \App\Models\RecipientGroup[] $groups  All groups (for info display)
 */

$flashToast = session()->getFlash('_toast');
?>

<div class="mx-auto max-w-6xl px-4 py-8">

    <!-- Page header -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Recipients</h1>
            <p class="mt-1 text-sm text-slate-500">
                <?= (int) $paginated['total'] ?> contact<?= $paginated['total'] !== 1 ? 's' : '' ?> saved.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="/recipients/import"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white
                       px-3.5 py-2 text-sm font-medium text-slate-700 shadow-sm
                       hover:bg-slate-50 transition"
            >
                <i class="bi bi-cloud-upload"></i>
                Import CSV
            </a>
            <a
                href="/recipients/create"
                class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2
                       text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
            >
                <i class="bi bi-person-plus-fill"></i>
                Add Contact
            </a>
        </div>
    </div>

    <!-- Flash toast bridge -->
    <?php if ($flashToast): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.dispatchEvent(new CustomEvent('showToast', { detail: <?= json_encode($flashToast) ?> }));
            });
        </script>
    <?php endif; ?>

    <!-- Search bar -->
    <div class="mb-4">
        <div class="relative">
            <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
            <input
                type="search"
                id="recipient-search"
                name="q"
                value="<?= e($search) ?>"
                placeholder="Search by name, email, or company…"
                class="w-full rounded-lg border border-slate-300 bg-white pl-9 pr-4 py-2.5
                       text-sm text-slate-800 shadow-sm placeholder-slate-400
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition"
                hx-get="/recipients"
                hx-trigger="keyup changed delay:300ms, search"
                hx-target="#recipient-table"
                hx-swap="innerHTML"
                hx-push-url="true"
                hx-indicator="#search-indicator"
                autocomplete="off"
            >
            <!-- Search loading indicator -->
            <span id="search-indicator" class="htmx-indicator absolute right-3 top-1/2 -translate-y-1/2">
                <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </span>
        </div>
    </div>

    <!-- Recipients table -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3 hidden sm:table-cell">Company</th>
                    <th class="px-4 py-3 hidden md:table-cell">Added</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="recipient-table">
                <?php include BASE_PATH . '/resources/recipients/_table-rows.php'; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($paginated['last_page'] > 1): ?>
        <div class="mt-6">
            <?php include component('tables/_pagination', [
                'paginated' => $paginated,
                'baseUrl'   => '/recipients',
                'search'    => $search,
            ]); ?>
        </div>
    <?php endif; ?>

</div>
