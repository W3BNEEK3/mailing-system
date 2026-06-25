<?php
$activePrimary   = $draft?->primaryColor   ?? $globalContext->primaryColor;
$activeSecondary = $draft?->secondaryColor ?? $globalContext->secondaryColor;
$activeTemplate  = $draft?->templateId     ?? null;
?>

<div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50 flex-wrap">

    <div class="flex items-center gap-2 flex-1 min-w-0">
        <label for="template-selector" class="text-xs font-medium text-slate-500 whitespace-nowrap flex-shrink-0">
            Template
        </label>
        <!-- FIXED: Unconditional hx-confirm -->
        <select
            id="template-selector"
            name="template_id"
            class="flex-1 min-w-0 max-w-[220px] rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            hx-post="/compose/load-template"
            hx-include="#compose-form"
            hx-target="#editor-wrapper"
            hx-swap="outerHTML"
            hx-indicator="#global-loader"
            hx-confirm="Loading a template will overwrite your current email body. Continue?"
        >
            <option value="">-- Blank Email --</option>
            <?php foreach ($templates as $tmpl): ?>
                <option value="<?= $tmpl->id ?>" <?= $activeTemplate === $tmpl->id ? 'selected' : '' ?>>
                    <?= e($tmpl->name) ?> <?= $tmpl->isBuiltIn ? '(built-in)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex items-center gap-1.5 flex-shrink-0">

        <!-- Translate Dropdown -->
        <div class="relative hidden sm:block">
            <select
                name="target_lang"
                class="appearance-none inline-flex items-center gap-1.5 pl-3 pr-8 py-1.5 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500"
                hx-post="/compose/translate"
                hx-include="#compose-form"
                hx-target="#compose-area"
                hx-on::after-request="this.selectedIndex = 0;"
            >
                <option value="" disabled selected>Translate...</option>
                <?php foreach (config('translation.supported_languages', []) as $code => $label): ?>
                    <option value="<?= e($code) ?>"><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <i class="bi bi-translate text-slate-400 text-sm absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
        </div>

        <div class="relative">
            <button id="colour-picker-trigger" type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 transition" onclick="toggleColourPicker()">
                <span id="primary-swatch" class="inline-block w-3 h-3 rounded-full border border-slate-300" style="background-color: <?= e($activePrimary) ?>;"></span>
                <i class="bi bi-palette text-sm"></i> <span class="hidden sm:inline">Colours</span>
            </button>
        </div>

        <div class="w-px h-5 bg-slate-200 mx-1"></div>

        <span id="recipient-count-badge" class="text-xs text-slate-400 whitespace-nowrap">
            0 recipients
        </span>
    </div>
</div>