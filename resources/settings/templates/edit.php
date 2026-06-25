<?php
/**
 * resources/settings/templates/edit.php
 *
 * Same structure as create.php but pre-populated with the existing template's data.
 *
 * @var \App\Models\EmailTemplate   $template   The template being edited
 * @var array<string,string>        $categories  Slug → label
 */

$fieldErrors = errors();
?>

<div class="mx-auto max-w-7xl px-4 py-8">

    <?php // Page header ?>
    <div class="mb-6 flex items-center gap-4">
        <a href="/settings/templates" class="text-slate-400 hover:text-slate-600 transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Edit Template</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                Editing: <span class="font-medium text-slate-700"><?= e($template->name) ?></span>
            </p>
        </div>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">

        <?php // ── Left: Form ────────────────────────────────────────────────── ?>
        <div class="w-full lg:w-[480px] flex-shrink-0">

            <form
                id="template-form"
                method="POST"
                action="/settings/templates/<?= $template->id ?>"
                enctype="multipart/form-data"
                class="space-y-6"
            >
                <?= csrf_field() ?>
                <input type="hidden" name="_method" value="PUT">

                <?php // Template Name ?>
                <?= component('forms/_input', [
                    'name'     => 'name',
                    'label'    => 'Template Name',
                    'type'     => 'text',
                    'value'    => old('name', $template->name),
                    'required' => true,
                    'error'    => $fieldErrors['name'] ?? null,
                ]); ?>

                <?php // Category ?>
                <div class="flex flex-col gap-1">
                    <label for="category" class="text-sm font-medium text-slate-700">Category</label>
                    <select id="category" name="category"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                                   text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($categories as $slug => $label): ?>
                            <option value="<?= e($slug) ?>"
                                <?= (old('category', $template->category) === $slug) ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php // Placeholder support display (read-only info) ?>
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
                        Detected Placeholders
                    </p>
                    <div class="flex gap-4">
                        <span class="flex items-center gap-1.5 text-sm
                            <?= $template->supportsLogo ? 'text-emerald-600' : 'text-slate-400' ?>">
                            <i class="bi <?= $template->supportsLogo ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle text-slate-300' ?>"></i>
                            Logo ({{LOGO_URL}})
                        </span>
                        <span class="flex items-center gap-1.5 text-sm
                            <?= $template->supportsColors ? 'text-emerald-600' : 'text-slate-400' ?>">
                            <i class="bi <?= $template->supportsColors ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle text-slate-300' ?>"></i>
                            Colours ({{PRIMARY_COLOR}})
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">
                        These are re-detected automatically when you save a new version of the HTML.
                    </p>
                </div>

                <?php // HTML editor (always in paste mode for edit) ?>
                <div class="flex flex-col gap-1">
                    <label for="html_content" class="text-sm font-medium text-slate-700">
                        HTML Content
                    </label>
                    <textarea
                        id="html_content"
                        name="html_content"
                        rows="20"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5
                               text-xs font-mono text-slate-800 shadow-sm resize-none
                               focus:outline-none focus:ring-2 focus:ring-blue-500"
                        hx-post="/settings/templates/preview-draft"
                        hx-trigger="keyup changed delay:600ms"
                        hx-include="[name='html_content']"
                        hx-target="#preview-iframe"
                        hx-swap="none"
                        hx-on::after-request="updatePreviewSrcdoc(event)"
                    ><?= e(old('html_content', $template->htmlContent)) ?></textarea>
                    <p class="text-xs text-slate-500">Or replace the HTML by uploading a new file below:</p>

                    <?= component('forms/_file-upload', [
                        'name'    => 'template_file',
                        'label'   => 'Replace with File Upload (optional)',
                        'accept'  => 'text/html,application/zip,.html,.zip',
                        'hint'    => 'Upload a new .html or .zip to replace the HTML above.',
                        'isImage' => false,
                        'error'   => $fieldErrors['template_file'] ?? null,
                    ]); ?>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <a href="/settings/templates" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5
                                   text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition">
                        <i class="bi bi-floppy"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <?php // ── Right: Live preview ─────────────────────────────────────── ?>
        <div class="flex-1 hidden lg:flex flex-col gap-3">
            <p class="text-sm font-medium text-slate-600">
                <i class="bi bi-eye mr-1"></i> Live Preview
            </p>
            <div class="flex-1 rounded-xl border border-slate-200 bg-slate-100 p-4 flex justify-center overflow-auto">
                <iframe
                    id="preview-iframe"
                    class="w-full border-0 rounded-lg bg-white shadow-sm"
                    style="max-width:600px;min-height:600px;"
                    sandbox="allow-same-origin"
                    title="Template preview"
                ></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function updatePreviewSrcdoc(event) {
    const iframe = document.getElementById('preview-iframe');
    if (event.detail.successful) {
        iframe.srcdoc = event.detail.xhr.responseText;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('html_content');
    const iframe   = document.getElementById('preview-iframe');
    
    if (!textarea) return;

    // TinyMCE removed for raw HTML editing

    // Load the initial preview on page load using the existing HTML
    if (iframe && textarea.value.trim()) {
        fetch('/settings/templates/preview-draft', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: 'html_content=' + encodeURIComponent(textarea.value),
        })
        .then(r => r.text())
        .then(html => { iframe.srcdoc = html; })
        .catch(() => {});
    }
});
</script>
