<?php
/**
 * resources/logs/_log-detail.php
 *
 * HTMX partial: detail panel for a single sent email log entry.
 * Loaded via GET /logs/{id} when a table row is clicked.
 *
 * @var array $log  A single email_logs row (associative array)
 */
$log = $log ?? [];
$recipients = json_decode($log['recipients_json'] ?? '[]', true);
$recipients = is_array($recipients) ? $recipients : [];
$sentAt     = !empty($log['sent_at']) ? date('d M Y, H:i:s', strtotime($log['sent_at'])) : '—';
$status     = $log['status'] ?? 'sent';
$statusCls  = match ($status) {
    'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'bounced', 'failed' => 'bg-red-50 text-red-700 border-red-200',
    default => 'bg-blue-50 text-blue-700 border-blue-200',
};
$statusIcon = match ($status) {
    'delivered' => 'bi-check-circle-fill',
    'bounced', 'failed' => 'bi-x-circle-fill',
    default => 'bi-send-fill',
};
?>

<div class="flex items-center justify-between mb-6">
    <h2 class="text-lg font-semibold text-slate-900">Email Details</h2>
    <button
        type="button"
        onclick="closeLogDetail()"
        class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
        aria-label="Close"
    >
        <i class="bi bi-x-lg text-sm"></i>
    </button>
</div>

<!-- Metadata grid -->
<dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="bg-slate-50 rounded-xl px-4 py-3">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Sent At</dt>
        <dd class="text-sm text-slate-800"><?= e($sentAt) ?></dd>
    </div>
    <div class="bg-slate-50 rounded-xl px-4 py-3">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</dt>
        <dd>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border <?= $statusCls ?>">
                <i class="bi <?= $statusIcon ?>"></i> <?= e(ucfirst($status)) ?>
            </span>
        </dd>
    </div>
    <div class="bg-slate-50 rounded-xl px-4 py-3">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Provider</dt>
        <dd class="text-sm text-slate-800"><?= e(ucfirst($log['provider'] ?? 'unknown')) ?></dd>
    </div>
    <div class="bg-slate-50 rounded-xl px-4 py-3">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Message ID</dt>
        <dd class="text-xs text-slate-500 font-mono truncate"><?= e($log['provider_message_id'] ?? '—') ?></dd>
    </div>
    <div class="bg-slate-50 rounded-xl px-4 py-3 sm:col-span-2">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Subject</dt>
        <dd class="text-sm text-slate-800 font-medium"><?= e($log['subject'] ?? '(No subject)') ?></dd>
    </div>
    <div class="bg-slate-50 rounded-xl px-4 py-3 sm:col-span-2">
        <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            Recipients (<?= count($recipients) ?>)
        </dt>
        <dd class="flex flex-wrap gap-1.5">
            <?php foreach ($recipients as $email): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-200 text-xs text-slate-700 font-medium">
                    <i class="bi bi-envelope text-slate-400 text-xs"></i>
                    <?= e($email) ?>
                </span>
            <?php endforeach; ?>
            <?php if (empty($recipients)): ?>
                <span class="text-sm text-slate-400">—</span>
            <?php endif; ?>
        </dd>
    </div>
</dl>

<!-- Email body preview -->
<div>
    <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Email Preview</h3>
    <?php if (!empty($log['body_html'])): ?>
        <div class="rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="flex items-center gap-2 px-4 py-2 bg-slate-100 border-b border-slate-200">
                <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                <span class="ml-2 text-xs text-slate-500 font-mono truncate">
                    <?= e($log['subject'] ?? 'Email Preview') ?>
                </span>
            </div>
            <iframe
                id="log-preview-frame-<?= (int) $log['id'] ?>"
                class="w-full border-0"
                style="height: 480px;"
                sandbox="allow-same-origin"
                srcdoc="<?= htmlspecialchars($log['body_html'], ENT_QUOTES, 'UTF-8') ?>"
                title="Email preview"
            ></iframe>
        </div>
    <?php else: ?>
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-400">
            No email body stored for this entry.
        </div>
    <?php endif; ?>
</div>
