<?php
/*
 * resources/recipients/_table-row.php
 *
 * Renders a single recipient as a <tr> for the recipients table.
 *
 * This partial is included in two contexts:
 *   1. Inside _table-rows.php (the full table body loop)
 *   2. As an HTMX partial returned by suppress() and unsuppress()
 *      to update a single row in place
 *
 * @var \App\Models\Recipient $recipient
 */
?>

<tr
    id="recipient-<?= (int) $recipient->id ?>"
    class="block sm:table-row border-b border-slate-100 text-sm transition p-4 sm:p-0 <?= $recipient->isSuppressed() ? 'opacity-50 bg-slate-50' : 'hover:bg-slate-50' ?>"
>
    <!-- Name -->
    <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 font-medium text-slate-800">
        <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-20">Name</span>
        <?php if ($recipient->isSuppressed()): ?>
            <span class="line-through text-slate-400"><?= e($recipient->fullName()) ?></span>
            <span class="ml-1.5 inline-flex items-center rounded-full bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600">
                Suppressed
            </span>
        <?php else: ?>
            <?= e($recipient->fullName()) ?>
        <?php endif; ?>
    </td>

    <!-- Email -->
    <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-600">
        <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-20">Email</span>
        <a href="mailto:<?= e($recipient->email) ?>"
           class="hover:text-blue-600 hover:underline transition">
            <?= e($recipient->email) ?>
        </a>
    </td>

    <!-- Company -->
    <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-500">
        <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-20">Company</span>
        <?= e($recipient->company ?: '—') ?>
    </td>

    <!-- Date added -->
    <td class="block sm:table-cell py-1 px-0 sm:py-3 sm:px-4 text-slate-400 text-xs whitespace-nowrap">
        <span class="inline-block sm:hidden text-xs text-slate-400 font-semibold uppercase tracking-wider w-20">Added</span>
        <?= e(\App\Helpers\Date::format((string) $recipient->created_at, 'd M Y')) ?>
    </td>

    <!-- Actions -->
    <td class="block sm:table-cell pt-3 pb-1 px-0 sm:py-3 sm:px-4 mt-2 sm:mt-0 border-t sm:border-0 border-slate-100">
        <div class="flex items-center justify-start sm:justify-end gap-2">

            <!-- Edit -->
            <a
                href="/recipients/<?= (int) $recipient->id ?>/edit"
                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium
                       text-slate-600 hover:bg-slate-100 transition"
                title="Edit contact"
            >
                <i class="bi bi-pencil"></i>
                <span class="hidden sm:inline">Edit</span>
            </a>

            <!-- Suppress / Unsuppress -->
            <?php if ($recipient->isSuppressed()): ?>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium
                           text-emerald-600 hover:bg-emerald-50 transition"
                    hx-post="/recipients/<?= (int) $recipient->id ?>/unsuppress"
                    hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                    hx-target="#recipient-<?= (int) $recipient->id ?>"
                    hx-swap="outerHTML"
                    title="Restore this contact"
                >
                    <i class="bi bi-arrow-counterclockwise"></i>
                    <span class="hidden sm:inline">Restore</span>
                </button>
            <?php else: ?>
                <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium
                           text-amber-600 hover:bg-amber-50 transition"
                    hx-post="/recipients/<?= (int) $recipient->id ?>/suppress"
                    hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                    hx-target="#recipient-<?= (int) $recipient->id ?>"
                    hx-swap="outerHTML"
                    hx-confirm="Suppress '<?= e(addslashes($recipient->fullName())) ?>'? They will be excluded from all future sends."
                    title="Suppress this contact"
                >
                    <i class="bi bi-slash-circle"></i>
                    <span class="hidden sm:inline">Suppress</span>
                </button>
            <?php endif; ?>

            <!-- Delete -->
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium
                       text-red-600 hover:bg-red-50 transition"
                hx-post="/recipients/<?= (int) $recipient->id ?>/delete"
                hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                hx-target="#recipient-<?= (int) $recipient->id ?>"
                hx-swap="outerHTML"
                hx-confirm="Delete '<?= e(addslashes($recipient->fullName())) ?>'? This cannot be undone."
                title="Delete contact"
            >
                <i class="bi bi-trash3"></i>
                <span class="hidden sm:inline">Delete</span>
            </button>

        </div>
    </td>
</tr>
