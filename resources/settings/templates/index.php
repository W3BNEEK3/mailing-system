<?php
/**
 * resources/settings/templates/index.php
 *
 * Template gallery — lists built-in and custom templates.
 *
 * @var \App\Models\EmailTemplate[] $builtIn  Built-in templates from TemplateRepository::findBuiltIn()
 * @var \App\Models\EmailTemplate[] $custom   Custom templates from TemplateRepository::findCustom()
 */

$flashToast = session()->getFlash('_toast');
?>

<div class="mx-auto max-w-6xl px-4 py-8">

    <?php // Page header ?>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Email Templates</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage the templates used when composing emails. Built-in templates cannot be deleted.
            </p>
        </div>
        <a
            href="/settings/templates/create"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5
                   text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
        >
            <i class="bi bi-plus-lg"></i> Add Template
        </a>
    </div>

    <?php // Flash toast bridge ?>
    <?php if ($flashToast): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.dispatchEvent(new CustomEvent('showToast', { detail: <?= json_encode($flashToast) ?> }));
            });
        </script>
    <?php endif; ?>

    <?php // ── Built-in templates ───────────────────────────────────────────── ?>
    <section class="mb-10">
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-500 uppercase tracking-wide">
            <i class="bi bi-stars text-amber-500"></i>
            Built-in Templates
        </h2>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($builtIn as $template): ?>
                <?= component('cards/_template-card', ['template' => $template]); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <?php // ── Custom templates ────────────────────────────────────────────── ?>
    <section>
        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-500 uppercase tracking-wide">
            <i class="bi bi-person-fill text-slate-400"></i>
            Your Templates
        </h2>

        <?php if (empty($custom)): ?>
            <?= component('ui/_empty-state', [
                'icon'    => 'bi-file-earmark-code',
                'title'   => 'No custom templates yet',
                'message' => 'Upload an HTML file, paste raw HTML, or duplicate a built-in template to get started.',
                'action'  => ['label' => 'Add Template', 'href' => '/settings/templates/create'],
            ]); ?>
        <?php else: ?>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($custom as $template): ?>
                    <?= component('cards/_template-card', ['template' => $template]); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

</div>

<?php // Preview modal (rendered once, opened via JS) ?>
<?= view('settings/templates/_preview-modal'); ?>
