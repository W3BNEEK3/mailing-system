<?php
/**
 * resources/components/compose/_colour-picker.php
 *
 * Inline popover for per-email colour overrides.
 * Toggled by toggleColourPicker() in index.php.
 * Changes update the hidden #primary-color-input and #secondary-color-input fields,
 * which are included in every compose-form HTMX request.
 */
?>

<div
    id="colour-picker-popover"
    class="hidden absolute z-40 top-14 right-4 w-72 bg-white rounded-xl
           border border-slate-200 shadow-xl p-5"
>
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-slate-800">Email Colours</h3>
        <button type="button" onclick="toggleColourPicker()"
                class="text-slate-400 hover:text-slate-600">
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>

    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
        Override the brand colours for this email only.
        Leave blank to use your global settings.
    </p>

    <div class="space-y-4">

        <!-- Primary Colour -->
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-slate-700 w-24 flex-shrink-0">Primary</label>
            <div class="flex items-center gap-2 flex-1">
                <input
                    type="color"
                    id="primary-color-picker"
                    class="w-9 h-9 rounded-lg border border-slate-200 cursor-pointer p-0.5"
                    oninput="updateColour('primary-color-input', 'primary-swatch', this.value)"
                >
                <input
                    type="text"
                    placeholder="e.g. #1d4ed8"
                    class="flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm
                           text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="if(this.value.match(/^#[0-9a-fA-F]{6}$/)) {
                        updateColour('primary-color-input', 'primary-swatch', this.value);
                        document.getElementById('primary-color-picker').value = this.value;
                    }"
                >
            </div>
        </div>

        <!-- Secondary Colour -->
        <div class="flex items-center gap-3">
            <label class="text-sm font-medium text-slate-700 w-24 flex-shrink-0">Secondary</label>
            <div class="flex items-center gap-2 flex-1">
                <input
                    type="color"
                    id="secondary-color-picker"
                    class="w-9 h-9 rounded-lg border border-slate-200 cursor-pointer p-0.5"
                    oninput="updateColour('secondary-color-input', null, this.value)"
                >
                <input
                    type="text"
                    placeholder="e.g. #0f172a"
                    class="flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-sm
                           text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="if(this.value.match(/^#[0-9a-fA-F]{6}$/)) {
                        updateColour('secondary-color-input', null, this.value);
                        document.getElementById('secondary-color-picker').value = this.value;
                    }"
                >
            </div>
        </div>
    </div>

    <!-- Reset to global -->
    <button
        type="button"
        class="mt-4 w-full text-xs text-slate-400 hover:text-slate-600 text-center"
        onclick="
            updateColour('primary-color-input', 'primary-swatch', '');
            updateColour('secondary-color-input', null, '');
            document.getElementById('primary-color-picker').value = '';
            document.getElementById('secondary-color-picker').value = '';
        "
    >
        Reset to global settings
    </button>
</div>