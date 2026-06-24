<?php
/**
 * resources/settings/templates/create.php
 *
 * Two-panel layout: form on the left, live preview iframe on the right.
 *
 * @var array<string,string> $categories  Slug → label (from TemplateController::categories())
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
            <h1 class="text-2xl font-bold text-slate-900">Add Template</h1>
            <p class="mt-0.5 text-sm text-slate-500">Upload an HTML file or paste your template code directly.</p>
        </div>
    </div>

    <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">

        <?php // ── Left: Form ────────────────────────────────────────────────── ?>
        <div class="w-full lg:w-[480px] flex-shrink-0">

            <form
                id="template-form"
                method="POST"
                action="/settings/templates"
                enctype="multipart/form-data"
                class="space-y-6"
            >
                <?= csrf_field() ?>

                <?php // Template Name ?>
                <?= component('forms/_input', [
                    'name'        => 'name',
                    'label'       => 'Template Name',
                    'type'        => 'text',
                    'value'       => old('name', ''),
                    'placeholder' => 'e.g. Monthly Newsletter',
                    'required'    => true,
                    'error'       => $fieldErrors['name'] ?? null,
                ]); ?>

                <?php // Category ?>
                <div class="flex flex-col gap-1">
                    <label for="category" class="text-sm font-medium text-slate-700">
                        Category <span class="text-red-500 ml-0.5">*</span>
                    </label>
                    <select
                        id="category"
                        name="category"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                               text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500
                               <?= isset($fieldErrors['category']) ? 'border-red-400 bg-red-50' : '' ?>"
                    >
                        <?php foreach ($categories as $slug => $label): ?>
                            <option value="<?= e($slug) ?>" <?= old('category') === $slug ? 'selected' : '' ?>>
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = ($fieldErrors['category'] ?? null)): ?>
                        <p class="text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <?php // Input mode tabs ?>
                <div>
                    <div class="flex rounded-lg border border-slate-200 overflow-hidden mb-4">
                        <button
                            type="button"
                            id="tab-upload"
                            class="flex-1 px-4 py-2.5 text-sm font-medium bg-slate-800 text-white transition"
                            onclick="switchTab('upload')"
                        >
                            <i class="bi bi-upload mr-1.5"></i> Upload File
                        </button>
                        <button
                            type="button"
                            id="tab-paste"
                            class="flex-1 px-4 py-2.5 text-sm font-medium bg-white text-slate-600 hover:bg-slate-50 transition"
                            onclick="switchTab('paste')"
                        >
                            <i class="bi bi-code-slash mr-1.5"></i> Paste HTML
                        </button>
                    </div>

                    <?php // Upload panel ?>
                    <div id="panel-upload">
                        <?= component('forms/_file-upload', [
                            'name'    => 'template_file',
                            'label'   => 'Template File',
                            'accept'  => 'text/html,application/zip,.html,.zip',
                            'hint'    => '.html or .zip (containing HTML + assets). Max 5MB.',
                            'isImage' => false,
                            'error'   => $fieldErrors['template_file'] ?? null,
                        ]); ?>
                    </div>

                    <?php // Paste panel (hidden by default) ?>
                    <div id="panel-paste" class="hidden">
                        <div class="flex flex-col gap-1">
                            <label for="html_content" class="text-sm font-medium text-slate-700">
                                HTML Content
                            </label>
                            <textarea
                                id="html_content"
                                name="html_content"
                                rows="18"
                                placeholder="Paste your email HTML here…"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5
                                       text-xs font-mono text-slate-800 shadow-sm resize-none
                                       focus:outline-none focus:ring-2 focus:ring-blue-500
                                       <?= isset($fieldErrors['html_content']) ? 'border-red-400 bg-red-50' : '' ?>"
                                hx-post="/settings/templates/preview-draft"
                                hx-trigger="keyup changed delay:600ms"
                                hx-include="[name='html_content']"
                                hx-target="#preview-iframe"
                                hx-swap="none"
                                hx-on::after-request="updatePreviewSrcdoc(event)"
                            ><?= e(old('html_content', '')) ?></textarea>
                            <p class="text-xs text-slate-500">
                                Use <code class="font-mono bg-slate-100 px-1 rounded">{{'{{'}}LOGO_URL{{'}}'}}</code>,
                                <code class="font-mono bg-slate-100 px-1 rounded">{{'{{'}}PRIMARY_COLOR{{'}}'}}</code>,
                                <code class="font-mono bg-slate-100 px-1 rounded">{{'{{'}}SECONDARY_COLOR{{'}}'}}</code>
                                for dynamic branding.
                            </p>
                            <?php if ($err = ($fieldErrors['html_content'] ?? null)): ?>
                                <p class="text-xs text-red-600"><?= e($err) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php // Save button ?>
                <div class="flex items-center justify-between pt-2">
                    <a href="/settings/templates" class="text-sm text-slate-500 hover:text-slate-700">
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5
                               text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
                    >
                        <i class="bi bi-floppy"></i> Save Template
                    </button>
                </div>
            </form>
        </div>

        <?php // ── Right: Live preview ─────────────────────────────────────── ?>
        <div class="flex-1 hidden lg:flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-600">
                    <i class="bi bi-eye mr-1"></i> Live Preview
                </p>
                <div class="flex rounded-lg border border-slate-200 overflow-hidden">
                    <button type="button"
                            class="px-3 py-1.5 text-xs font-medium bg-slate-800 text-white"
                            onclick="setCreatePreviewWidth('600px')">Desktop</button>
                    <button type="button"
                            class="px-3 py-1.5 text-xs font-medium bg-white text-slate-600 hover:bg-slate-50"
                            onclick="setCreatePreviewWidth('375px')">Mobile</button>
                </div>
            </div>

            <div class="flex-1 rounded-xl border border-slate-200 bg-slate-100 p-4 flex justify-center overflow-auto">
                <iframe
                    id="preview-iframe"
                    class="w-full border-0 rounded-lg bg-white shadow-sm transition-all"
                    style="max-width:600px;min-height:600px;"
                    sandbox="allow-same-origin"
                    srcdoc="<p style='font-family:Arial;color:#94a3b8;text-align:center;padding:60px 20px;'>
                        Paste HTML on the left to see a live preview here.
                    </p>"
                    title="Template preview"
                ></iframe>
            </div>
        </div>
    </div>
</div>

<script>
/** Switch between the Upload File and Paste HTML input tabs. */
function switchTab(mode) {
    const uploadPanel = document.getElementById('panel-upload');
    const pastePanel  = document.getElementById('panel-paste');
    const uploadBtn   = document.getElementById('tab-upload');
    const pasteBtn    = document.getElementById('tab-paste');

    if (mode === 'paste') {
        uploadPanel.classList.add('hidden');
        pastePanel.classList.remove('hidden');
        pasteBtn.classList.add('bg-slate-800', 'text-white');
        pasteBtn.classList.remove('bg-white', 'text-slate-600');
        uploadBtn.classList.remove('bg-slate-800', 'text-white');
        uploadBtn.classList.add('bg-white', 'text-slate-600');
    } else {
        pastePanel.classList.add('hidden');
        uploadPanel.classList.remove('hidden');
        uploadBtn.classList.add('bg-slate-800', 'text-white');
        uploadBtn.classList.remove('bg-white', 'text-slate-600');
        pasteBtn.classList.remove('bg-slate-800', 'text-white');
        pasteBtn.classList.add('bg-white', 'text-slate-600');
    }
}

/**
 * HTMX fires hx-on::after-request after the preview-draft POST completes.
 * The response body is the rendered HTML — we write it to the iframe's srcdoc.
 */
function updatePreviewSrcdoc(event) {
    const iframe = document.getElementById('preview-iframe');
    if (event.detail.successful) {
        iframe.srcdoc = event.detail.xhr.responseText;
    }
}

/** Resize the live preview iframe width (desktop/mobile toggle). */
function setCreatePreviewWidth(width) {
    document.getElementById('preview-iframe').style.maxWidth = width;
}
</script>
