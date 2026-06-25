<?php
/**
 * resources/components/compose/_toolbar.php
 *
 * The compose toolbar: template selector, logo override, colour picker,
 * and the autosave status indicator.
 *
 * @var \App\Models\EmailTemplate[]  $templates      All available templates
 * @var \App\DTOs\RenderContext      $globalContext   Global brand colours (for picker defaults)
 * @var \App\Models\EmailDraft|null  $draft           Active draft (or null)
 */

$activePrimary   = $draft?->primaryColor   ?? $globalContext->primaryColor;
$activeSecondary = $draft?->secondaryColor ?? $globalContext->secondaryColor;
$activeTemplate  = $draft?->templateId     ?? null;
?>

<div class="flex items-center gap-2 px-5 py-3 border-b border-slate-100 bg-slate-50 flex-wrap">

    <!-- Template Selector -->
    <div class="flex items-center gap-2 flex-1 min-w-0">
        <label for="template-selector" class="text-xs font-medium text-slate-500 whitespace-nowrap flex-shrink-0">
            Template
        </label>
        <select
            id="template-selector"
            class="flex-1 min-w-0 max-w-[220px] rounded-lg border border-slate-200 bg-white px-3 py-1.5
                   text-sm text-slate-700 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            hx-post="/compose/load-template"
            hx-include="[name='template_id']"
            hx-target="#compose-area"
            hx-swap="innerHTML"
            hx-confirm="This will replace your current email body. Are you sure?"
            onchange="document.getElementById('template-id-input').value = this.value; htmx.trigger(this, 'change')"
        >
            <option value="">— Blank (no template) —</option>
            <?php foreach ($templates as $tmpl): ?>
                <option
                    value="<?= $tmpl->id ?>"
                    <?= $activeTemplate == $tmpl->id ? 'selected' : '' ?>
                >
                    <?= e($tmpl->name) ?>
                    <?= $tmpl->isBuiltIn ? '(built-in)' : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="flex items-center gap-1.5 flex-shrink-0">

        <!-- Colour Picker Toggle -->
        <div class="relative">
            <button
                id="colour-picker-trigger"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200
                       bg-white text-sm font-medium text-slate-600 hover:bg-slate-50 transition"
                onclick="toggleColourPicker()"
                title="Override email colours"
            >
                <!-- Live colour swatch showing the active primary colour -->
                <span
                    id="primary-swatch"
                    class="inline-block w-3 h-3 rounded-full border border-slate-300"
                    style="background-color: <?= e($activePrimary) ?>;"
                ></span>
                <i class="bi bi-palette text-sm"></i>
                Colours
            </button>
        </div>

        <!-- Divider -->
        <div class="w-px h-5 bg-slate-200 mx-1"></div>

        <!-- Recipient count indicator (updated by JS when chips change) -->
        <span id="recipient-count-badge" class="text-xs text-slate-400 whitespace-nowrap">
            0 recipients
        </span>

    </div>
</div>