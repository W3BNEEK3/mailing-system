<?php
/**
 * resources/components/cards/_template-card.php
 *
 * Renders a single template card in the template gallery grid.
 *
 * @var \App\Models\EmailTemplate $template   The template to display
 *
 * Usage:
 *   <?php foreach ($custom as $template): ?>
 *     <?= component('cards/_template-card', ['template' => $template]); ?>
 *   <?php endforeach; ?>
 */
?>

<div
    class="template-card group relative flex flex-col overflow-hidden rounded-xl border border-slate-200
           bg-white shadow-sm transition hover:shadow-md hover:border-slate-300"
    data-template-id="<?= $template->id ?>"
>
    <?php // Colour-coded thumbnail placeholder ?>
    <div
        class="flex h-40 items-center justify-center text-white font-semibold text-sm
               <?= match($template->category) {
                   'newsletter'    => 'bg-gradient-to-br from-blue-500 to-blue-700',
                   'transactional' => 'bg-gradient-to-br from-emerald-500 to-emerald-700',
                   'promotional'   => 'bg-gradient-to-br from-purple-500 to-purple-700',
                   default         => 'bg-gradient-to-br from-slate-400 to-slate-600',
               } ?>"
    >
        <div class="text-center px-4">
            <i class="bi bi-envelope-paper text-3xl mb-2 block opacity-80"></i>
            <?= e($template->name) ?>
        </div>
    </div>

    <?php // Card body ?>
    <div class="flex flex-1 flex-col p-4 gap-3">

        <?php // Name + badges ?>
        <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold text-slate-800 leading-tight">
                <?= e($template->name) ?>
            </h3>
            <div class="flex items-center gap-1 flex-shrink-0">
                <?php if ($template->isBuiltIn): ?>
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                        Built-in
                    </span>
                <?php endif; ?>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= e($template->categoryBadgeClass()) ?>">
                    <?= e($template->categoryLabel()) ?>
                </span>
            </div>
        </div>

        <?php // Placeholder support indicators ?>
        <div class="flex items-center gap-2">
            <?php if ($template->supportsLogo): ?>
                <span class="flex items-center gap-1 text-xs text-emerald-600" title="Supports logo injection">
                    <i class="bi bi-image-fill text-emerald-500"></i> Logo
                </span>
            <?php else: ?>
                <span class="flex items-center gap-1 text-xs text-slate-400" title="No logo placeholder">
                    <i class="bi bi-image text-slate-300"></i> No logo
                </span>
            <?php endif; ?>
            <?php if ($template->supportsColors): ?>
                <span class="flex items-center gap-1 text-xs text-emerald-600" title="Supports colour injection">
                    <i class="bi bi-palette-fill text-emerald-500"></i> Colours
                </span>
            <?php else: ?>
                <span class="flex items-center gap-1 text-xs text-slate-400" title="No colour placeholders">
                    <i class="bi bi-palette text-slate-300"></i> No colours
                </span>
            <?php endif; ?>
            <span class="ml-auto text-xs text-slate-400">
                <?= date('d M Y', strtotime($template->updatedAt)) ?>
            </span>
        </div>

        <?php // Action buttons ?>
        <div class="mt-auto flex items-center gap-2 border-t border-slate-100 pt-3">

            <?php // Preview (all templates) ?>
            <button
                type="button"
                class="flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium
                       text-slate-600 hover:bg-slate-100 transition"
                onclick="openTemplatePreview(<?= $template->id ?>)"
                title="Preview this template"
            >
                <i class="bi bi-eye"></i> Preview
            </button>

            <?php if (!$template->isBuiltIn): ?>
                <?php // Edit (custom only) ?>
                <a
                    href="/settings/templates/<?= $template->id ?>/edit"
                    class="flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium
                           text-slate-600 hover:bg-slate-100 transition"
                    title="Edit template"
                >
                    <i class="bi bi-pencil"></i> Edit
                </a>
            <?php endif; ?>

            <?php // Duplicate (all templates) ?>
            <button
                type="button"
                class="flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium
                       text-slate-600 hover:bg-slate-100 transition"
                hx-post="/settings/templates/<?= $template->id ?>/duplicate"
                hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                title="Duplicate template"
            >
                <i class="bi bi-copy"></i> Duplicate
            </button>

            <?php if (!$template->isBuiltIn): ?>
                <?php // Delete (custom only) ?>
                <button
                    type="button"
                    class="ml-auto flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs font-medium
                           text-red-600 hover:bg-red-50 transition"
                    hx-post="/settings/templates/<?= $template->id ?>/delete"
                    hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                    hx-target="closest .template-card"
                    hx-swap="outerHTML"
                    hx-confirm="Delete '<?= e(addslashes($template->name)) ?>'? This cannot be undone."
                    title="Delete template"
                >
                    <i class="bi bi-trash3"></i> Delete
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>
