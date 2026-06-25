<?php
/**
 * resources/logs/_sent-table.php
 *
 * HTMX partial: paginated sent email logs.
 *
 * @var array  $rows      Array of email_logs rows (associative)
 * @var array  $paginated Pagination metadata
 * @var string $type      Current tab type
 * @var array  $filters   Active filters
 */
$rows      = $rows ?? [];
$paginated = $paginated ?? ['total' => 0, 'page' => 1, 'lastPage' => 1, 'perPage' => 25];
?>

<?php if (empty($rows)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-envelope-check text-3xl text-slate-300"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-1">No sent emails yet</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">Emails you send will appear here with their delivery status.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm block sm:table">
            <thead class="hidden sm:table-header-group">
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Sent</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Recipients</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Subject</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden md:table-cell">Provider</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="block sm:table-row-group divide-y divide-slate-100">
                <?php foreach ($rows as $row): ?>
                    <?php
                    $status     = $row['status'] ?? 'sent';
                    $statusCls  = match ($status) {
                        'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'bounced'   => 'bg-red-50 text-red-700 border-red-200',
                        'failed'    => 'bg-red-50 text-red-700 border-red-200',
                        default     => 'bg-blue-50 text-blue-700 border-blue-200',
                    };
                    $statusIcon = match ($status) {
                        'delivered' => 'bi-check-circle-fill',
                        'bounced', 'failed' => 'bi-x-circle-fill',
                        default => 'bi-send-fill',
                    };
                    $recipients = json_decode($row['recipients_json'] ?? '[]', true);
                    $recipCount = is_array($recipients) ? count($recipients) : 0;
                    $sentAt     = !empty($row['sent_at']) ? date('d M Y, H:i', strtotime($row['sent_at'])) : '—';
                    ?>
                    <tr class="block sm:table-row hover:bg-slate-50 transition cursor-pointer p-4 sm:p-0" onclick="openLogDetail(<?= (int) $row['id'] ?>)">
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-500 whitespace-nowrap">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Sent</span>
                            <?= e($sentAt) ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-700">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Recipients</span>
                            <?php if ($recipCount === 1 && !empty($recipients[0])): ?>
                                <span class="truncate max-w-[160px] block" title="<?= e($recipients[0]) ?>"><?= e($recipients[0]) ?></span>
                            <?php else: ?>
                                <span class="text-slate-500"><?= $recipCount ?> recipient<?= $recipCount !== 1 ? 's' : '' ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-800 max-w-[240px]">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Subject</span>
                            <span class="truncate sm:block" title="<?= e($row['subject'] ?? '') ?>"><?= e($row['subject'] ?? '(No subject)') ?></span>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-500 hidden md:table-cell">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Provider</span>
                            <?= e(ucfirst($row['provider'] ?? 'unknown')) ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Status</span>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border <?= $statusCls ?>">
                                <i class="bi <?= $statusIcon ?>"></i> <?= e(ucfirst($status)) ?>
                            </span>
                        </td>
                        <td class="hidden sm:table-cell px-4 py-3 text-right">
                            <i class="bi bi-chevron-right text-slate-300"></i>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($paginated['lastPage'] > 1): ?>
        <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 bg-slate-50">
            <span class="text-xs text-slate-500">
                Page <?= $paginated['page'] ?> of <?= $paginated['lastPage'] ?>
                &middot; <?= $paginated['total'] ?> total
            </span>
            <div class="flex gap-1">
                <?php if ($paginated['page'] > 1): ?>
                    <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white transition"
                        hx-get="/logs?type=sent&page=<?= $paginated['page'] - 1 ?>"
                        hx-target="#log-table" hx-swap="innerHTML">← Prev</button>
                <?php endif; ?>
                <?php if ($paginated['page'] < $paginated['lastPage']): ?>
                    <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white transition"
                        hx-get="/logs?type=sent&page=<?= $paginated['page'] + 1 ?>"
                        hx-target="#log-table" hx-swap="innerHTML">Next →</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
