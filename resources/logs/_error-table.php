<?php
/**
 * resources/logs/_error-table.php
 *
 * HTMX partial: failed send error logs.
 *
 * @var array  $rows      Array of email_error_logs rows (associative)
 * @var array  $paginated Pagination metadata
 */
$rows      = $rows ?? [];
$paginated = $paginated ?? ['total' => 0, 'page' => 1, 'lastPage' => 1];
?>

<?php if (empty($rows)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-emerald-50 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-shield-check text-3xl text-emerald-400"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-1">No errors logged</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">Failed send attempts will appear here.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm block sm:table">
            <thead class="hidden sm:table-header-group">
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Time</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Subject</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Recipients</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide hidden md:table-cell">Provider</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Error</th>
                </tr>
            </thead>
            <tbody class="block sm:table-row-group divide-y divide-slate-100">
                <?php foreach ($rows as $row): ?>
                    <?php
                    $recipients = json_decode($row['recipients_json'] ?? '[]', true);
                    $recipCount = is_array($recipients) ? count($recipients) : 0;
                    $createdAt  = !empty($row['created_at']) ? date('d M Y, H:i', strtotime($row['created_at'])) : '—';
                    ?>
                    <tr class="block sm:table-row hover:bg-red-50/30 transition p-4 sm:p-0">
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-500 whitespace-nowrap">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Time</span>
                            <?= e($createdAt) ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-800 max-w-[200px]">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Subject</span>
                            <span class="truncate sm:block"><?= e($row['subject'] ?? '(No subject)') ?></span>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-600">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Recipients</span>
                            <?= $recipCount ?> recipient<?= $recipCount !== 1 ? 's' : '' ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-500 hidden md:table-cell">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Provider</span>
                            <?= e(ucfirst($row['provider'] ?? 'unknown')) ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-red-600 max-w-[280px]">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Error</span>
                            <span class="truncate sm:block text-xs font-mono" title="<?= e($row['error_message'] ?? '') ?>">
                                <?= e($row['error_message'] ?? 'Unknown error') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($paginated['lastPage'] > 1): ?>
        <div class="flex items-center justify-between px-4 py-3 border-t border-slate-100 bg-slate-50">
            <span class="text-xs text-slate-500">Page <?= $paginated['page'] ?> of <?= $paginated['lastPage'] ?></span>
            <div class="flex gap-1">
                <?php if ($paginated['page'] > 1): ?>
                    <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white transition"
                        hx-get="/logs?type=error&page=<?= $paginated['page'] - 1 ?>"
                        hx-target="#log-table" hx-swap="innerHTML">← Prev</button>
                <?php endif; ?>
                <?php if ($paginated['page'] < $paginated['lastPage']): ?>
                    <button class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-white transition"
                        hx-get="/logs?type=error&page=<?= $paginated['page'] + 1 ?>"
                        hx-target="#log-table" hx-swap="innerHTML">Next →</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
