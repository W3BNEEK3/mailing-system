<?php
$bodyHtml = $bodyHtml ?? '';
$subject  = $subject ?? '';
$originalBody = $originalBody ?? null;
$originalSubject = $originalSubject ?? null;
?>

<?= component('compose/_editor', [
    'bodyHtml'   => $bodyHtml,
    'templateId' => null,
]) ?>

<input
    type="text"
    id="subject-input"
    name="subject"
    value="<?= e($subject) ?>"
    placeholder="Email Subject"
    class="w-full bg-transparent text-lg font-semibold text-slate-900 placeholder-slate-300 focus:outline-none"
    hx-swap-oob="true"
>

<div id="translation-controls" hx-swap-oob="true" class="px-6 pb-4 bg-white">
    <?php if ($originalBody !== null): ?>
        <div class="flex items-center gap-3 bg-indigo-50 border border-indigo-100 rounded-xl p-3 mt-2">
            <i class="bi bi-translate text-indigo-500 text-lg"></i>
            <span class="text-sm font-medium text-indigo-800 flex-1">
                Viewing translated version.
            </span>

            <input type="hidden" name="original_body" value="<?= e($originalBody) ?>">
            <input type="hidden" name="original_subject" value="<?= e($originalSubject) ?>">

            <button
                type="button"
                class="text-xs font-semibold text-indigo-700 hover:text-indigo-900 bg-white px-3 py-1.5 rounded-lg shadow-sm border border-indigo-200 transition hover:bg-indigo-50"
                hx-post="/compose/translate/revert"
                hx-include="#compose-form"
                hx-target="#compose-area"
            >
                <i class="bi bi-arrow-counterclockwise mr-1"></i> Undo
            </button>
        </div>
    <?php endif; ?>
</div>