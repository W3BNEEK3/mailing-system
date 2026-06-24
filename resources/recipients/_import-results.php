<?php
/*
 * resources/recipients/_import-results.php
 *
 * HTMX partial returned by RecipientController::import() after processing a CSV.
 * Replaces the content of #import-results in the DOM.
 *
 * Shows a summary of imported, skipped, and error rows.
 * If there are errors, each row's issue is listed so the user knows
 * exactly which rows to fix before re-importing.
 *
 * @var int   $imported  Number of new contacts inserted
 * @var int   $skipped   Number of rows skipped (email already existed)
 * @var array $errors    Array of ['row' => n, 'email' => '...', 'reason' => '...']
 */

$hasErrors = !empty($errors);
?>

<div class="rounded-xl border <?= $hasErrors ? 'border-amber-200 bg-amber-50' : 'border-emerald-200 bg-emerald-50' ?> p-5 space-y-4">

    <!-- Summary line -->
    <div class="flex items-start gap-3">
        <i class="bi <?= $hasErrors ? 'bi-exclamation-triangle-fill text-amber-500' : 'bi-check-circle-fill text-emerald-500' ?> text-xl flex-shrink-0 mt-0.5"></i>
        <div>
            <p class="font-semibold text-sm <?= $hasErrors ? 'text-amber-800' : 'text-emerald-800' ?>">
                Import complete
            </p>
            <ul class="mt-1 text-sm <?= $hasErrors ? 'text-amber-700' : 'text-emerald-700' ?> space-y-0.5">
                <li><i class="bi bi-check2 mr-1"></i><strong><?= (int) $imported ?></strong> contact(s) imported</li>
                <?php if ($skipped > 0): ?>
                    <li><i class="bi bi-skip-forward mr-1"></i><strong><?= (int) $skipped ?></strong> skipped (email already exists)</li>
                <?php endif; ?>
                <?php if ($hasErrors): ?>
                    <li><i class="bi bi-x mr-1"></i><strong><?= count($errors) ?></strong> row(s) had errors</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Error detail table -->
    <?php if ($hasErrors): ?>
        <div class="mt-2">
            <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-2">
                Rows with errors
            </p>
            <div class="overflow-x-auto rounded-lg border border-amber-200">
                <table class="w-full text-xs text-left">
                    <thead class="bg-amber-100 text-amber-700">
                        <tr>
                            <th class="px-3 py-2 font-semibold w-16">Row</th>
                            <th class="px-3 py-2 font-semibold">Email</th>
                            <th class="px-3 py-2 font-semibold">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-amber-100">
                        <?php foreach ($errors as $err): ?>
                            <tr>
                                <td class="px-3 py-2 text-amber-600 font-mono"><?= (int) $err['row'] ?></td>
                                <td class="px-3 py-2 font-mono text-slate-600"><?= e($err['email'] ?: '(empty)') ?></td>
                                <td class="px-3 py-2 text-slate-600"><?= e($err['reason']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- Link to recipients list -->
    <?php if ($imported > 0): ?>
        <a
            href="/recipients"
            class="inline-flex items-center gap-1.5 text-sm font-medium
                   <?= $hasErrors ? 'text-amber-700 hover:text-amber-900' : 'text-emerald-700 hover:text-emerald-900' ?>
                   underline-offset-2 hover:underline transition"
        >
            <i class="bi bi-people"></i>
            View imported contacts
        </a>
    <?php endif; ?>

</div>
