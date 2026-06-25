<?php
/**
 * resources/logs/index.php
 * Phase 10 — Email Logs
 *
 * @var string $type      Current tab: 'sent' | 'error' | 'received'
 * @var array  $rows      Current page rows
 * @var array  $paginated Pagination metadata
 * @var array  $filters   Active filter values
 */
$type      = $type ?? 'sent';
$rows      = $rows ?? [];
$paginated = $paginated ?? ['rows' => [], 'total' => 0, 'page' => 1, 'lastPage' => 1, 'perPage' => 25];
$filters   = $filters ?? [];
?>

<div class="max-w-6xl mx-auto">

    <!-- ── Page header ────────────────────────────────────────────────── -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Email Logs</h1>
            <p class="text-sm text-slate-500 mt-0.5">History of sent emails, delivery errors, and received messages.</p>
        </div>

        <!-- Clear button -->
        <button
            type="button"
            onclick="document.getElementById('clear-modal').classList.remove('hidden'); document.getElementById('clear-modal').classList.add('flex');"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-red-200 text-sm font-medium text-red-600 hover:bg-red-50 transition"
        >
            <i class="bi bi-trash3"></i> Clear Logs
        </button>
    </div>

    <!-- ── Tab strip ──────────────────────────────────────────────────── -->
    <div class="flex items-center gap-1 mb-5 border-b border-slate-200">
        <?php foreach (['sent' => ['Sent', 'bi-send'], 'error' => ['Errors', 'bi-exclamation-circle'], 'received' => ['Received', 'bi-inbox']] as $tab => [$label, $icon]): ?>
            <button
                type="button"
                class="flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition
                       <?= $type === $tab
                           ? 'border-blue-600 text-blue-700'
                           : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>"
                hx-get="/logs?type=<?= $tab ?>"
                hx-target="#log-table"
                hx-swap="innerHTML"
                hx-push-url="true"
                hx-indicator="#log-loader"
                onclick="setActiveTab(this)"
            >
                <i class="bi <?= $icon ?>"></i> <?= $label ?>
            </button>
        <?php endforeach; ?>
    </div>

    <!-- ── Filter bar ─────────────────────────────────────────────────── -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3 mb-4 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[180px]">
            <label class="block text-xs font-medium text-slate-500 mb-1">Recipient / Subject</label>
            <input
                type="text"
                id="filter-search"
                placeholder="Search…"
                value="<?= e($filters['recipient'] ?? '') ?>"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                hx-get="/logs"
                hx-vals='{"type": "<?= e($type) ?>", "recipient": "#filter-search.value", "subject": "#filter-search.value"}'
                hx-trigger="keyup changed delay:400ms"
                hx-target="#log-table"
                hx-swap="innerHTML"
                hx-indicator="#log-loader"
            >
        </div>

        <?php if ($type === 'sent'): ?>
        <div class="min-w-[120px]">
            <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
            <select
                id="filter-status"
                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                hx-get="/logs"
                hx-include="#filter-search,#filter-date-from,#filter-date-to"
                hx-vals='{"type": "sent"}'
                hx-trigger="change"
                hx-target="#log-table"
                hx-swap="innerHTML"
            >
                <option value="">All statuses</option>
                <option value="sent" <?= ($filters['status'] ?? '') === 'sent' ? 'selected' : '' ?>>Sent</option>
                <option value="delivered" <?= ($filters['status'] ?? '') === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="bounced" <?= ($filters['status'] ?? '') === 'bounced' ? 'selected' : '' ?>>Bounced</option>
                <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="flex gap-2">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From</label>
                <input type="date" id="filter-date-from" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    hx-get="/logs"
                    hx-include="#filter-search,#filter-status,#filter-date-to"
                    hx-vals='{"type": "<?= e($type) ?>"}'
                    hx-trigger="change"
                    hx-target="#log-table"
                    hx-swap="innerHTML">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">To</label>
                <input type="date" id="filter-date-to" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>"
                    class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    hx-get="/logs"
                    hx-include="#filter-search,#filter-status,#filter-date-from"
                    hx-vals='{"type": "<?= e($type) ?>"}'
                    hx-trigger="change"
                    hx-target="#log-table"
                    hx-swap="innerHTML">
            </div>
        </div>
    </div>

    <!-- ── HTMX loader indicator ──────────────────────────────────────── -->
    <div id="log-loader" class="htmx-indicator text-center py-4 text-slate-400 text-sm">
        <i class="bi bi-arrow-repeat animate-spin mr-1"></i> Loading…
    </div>

    <!-- ── Log table (HTMX target) ───────────────────────────────────── -->
    <div id="log-table">
        <?php include __DIR__ . "/_{$type}-table.php"; ?>
    </div>

</div>

<!-- ── Clear Logs Confirmation Modal ─────────────────────────────────── -->
<div
    id="clear-modal"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 px-4"
    role="dialog" aria-modal="true"
>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
        <div class="px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900 mb-2">Clear Logs</h2>
            <p class="text-sm text-slate-600 mb-4">This will permanently delete all <strong><?= ucfirst($type) ?></strong> log records. This cannot be undone.</p>
            <input type="hidden" id="clear-type" value="<?= e($type) ?>">
        </div>
        <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-100">
            <button
                type="button"
                onclick="document.getElementById('clear-modal').classList.add('hidden'); document.getElementById('clear-modal').classList.remove('flex');"
                class="text-sm font-medium text-slate-500 hover:text-slate-700 transition"
            >Cancel</button>
            <button
                type="button"
                id="clear-confirm-btn"
                hx-post="/logs/clear"
                hx-vals='js:{"type": document.getElementById("clear-type").value}'
                hx-target="#log-table"
                hx-swap="innerHTML"
                hx-on::after-request="document.getElementById('clear-modal').classList.add('hidden'); document.getElementById('clear-modal').classList.remove('flex');"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-sm font-semibold text-white hover:bg-red-700 transition"
            >
                <i class="bi bi-trash3"></i> Clear Logs
            </button>
        </div>
    </div>
</div>

<!-- ── Log detail slide-in panel ─────────────────────────────────────── -->
<div
    id="log-detail-panel"
    class="hidden fixed inset-y-0 right-0 z-40 w-full max-w-2xl bg-white shadow-2xl border-l border-slate-200 overflow-y-auto"
    role="complementary"
>
    <div id="log-detail-content" class="p-6">
        <!-- Populated by HTMX GET /logs/{id} -->
    </div>
</div>

<script>
function setActiveTab(btn) {
    // Update tab styles immediately on click (before HTMX response)
    document.querySelectorAll('[hx-get^="/logs?type="]').forEach(function (t) {
        t.classList.remove('border-blue-600', 'text-blue-700');
        t.classList.add('border-transparent', 'text-slate-500');
    });
    btn.classList.add('border-blue-600', 'text-blue-700');
    btn.classList.remove('border-transparent', 'text-slate-500');

    // Update the clear modal's type value
    var typeParam = new URL(btn.getAttribute('hx-get'), window.location.href).searchParams.get('type');
    var clearType = document.getElementById('clear-type');
    if (clearType && typeParam) clearType.value = typeParam;
}

function openLogDetail(id) {
    var panel = document.getElementById('log-detail-panel');
    panel.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    htmx.ajax('GET', '/logs/' + id, {target: '#log-detail-content', swap: 'innerHTML'});
}

function closeLogDetail() {
    document.getElementById('log-detail-panel').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('logsCleared', function () {
    // Reload the current tab after clearing
    var activeTab = document.querySelector('[hx-get^="/logs?type="].border-blue-600');
    if (activeTab) htmx.trigger(activeTab, 'click');
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLogDetail();
});
</script>