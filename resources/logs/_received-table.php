<?php
/**
 * resources/logs/_received-table.php
 *
 * HTMX partial: inbound received emails.
 * Gracefully degrades if the received_emails table does not exist.
 *
 * @var array  $rows      Array of received_emails rows (associative)
 * @var array  $paginated Pagination metadata
 */
$rows      = $rows ?? [];
$paginated = $paginated ?? ['total' => 0, 'page' => 1, 'lastPage' => 1];
?>

<div class="mb-4 flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
    <i class="bi bi-info-circle-fill text-blue-400 mt-0.5 flex-shrink-0"></i>
    <p>
        Inbound email receiving requires a Resend inbound routing webhook to be configured.
        Once active, messages sent to your domain will appear here.
    </p>
</div>

<?php if (empty($rows)): ?>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-inbox text-3xl text-slate-300"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-1">No received emails</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">Inbound emails will appear here once the webhook is configured.</p>
    </div>
<?php else: ?>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm block sm:table">
            <thead class="hidden sm:table-header-group">
                <tr class="border-b border-slate-100 bg-slate-50">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Received</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">From</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wide">Subject</th>
                </tr>
            </thead>
            <tbody class="block sm:table-row-group divide-y divide-slate-100">
                <?php foreach ($rows as $row): ?>
                    <tr class="block sm:table-row hover:bg-slate-50 transition p-4 sm:p-0">
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-500 whitespace-nowrap">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Received</span>
                            <?= e(!empty($row['received_at']) ? date('d M Y, H:i', strtotime($row['received_at'])) : '—') ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-700">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">From</span>
                            <?= e($row['from_email'] ?? '—') ?>
                        </td>
                        <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-800 max-w-[320px]">
                            <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-24">Subject</span>
                            <span class="truncate sm:block"><?= e($row['subject'] ?? '(No subject)') ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
