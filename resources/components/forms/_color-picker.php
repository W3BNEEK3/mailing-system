<?php
/**
 * resources/components/forms/_color-picker.php
 *
 * A colour picker that pairs a native <input type="color"> with a hex text
 * input, kept in sync by a small inline script. Submits as a standard form
 * field — no JavaScript framework required.
 *
 * The native colour picker gives users a visual palette while the hex input
 * lets power users type an exact value. Both inputs share the same name
 * attribute, but only the hex input is submitted (the colour picker is
 * visually driven only).
 *
 * @var string      $name   Form field name — the hex value is submitted under this name
 * @var string      $label  Label text
 * @var string      $value  Current hex colour value (e.g. '#1d4ed8')
 * @var string|null $hint   Optional helper text
 * @var string|null $error  Validation error message
 *
 * Usage:
 *   <?= component('forms/_color-picker', [
 *       'name'  => 'primary_color',
 *       'label' => 'Primary Colour',
 *       'value' => $settings['primary_color'] ?? '#1d4ed8',
 *   ]); ?>
 */

$hint  = $hint  ?? null;
$error = $error ?? null;
$value = $value ?? '#1d4ed8';

// Ensure the value has a # prefix
if ($value && !str_starts_with($value, '#')) {
    $value = '#' . $value;
}

$pickerId  = 'picker_' . e($name);
$inputId   = 'hex_'    . e($name);
?>

<div class="flex flex-col gap-1">
    <label for="<?= $inputId ?>" class="text-sm font-medium text-slate-700">
        <?= e($label) ?>
    </label>

    <div class="flex items-center gap-2">
        <?php /**
            The native colour input drives the swatch preview.
            It does NOT have a name attribute — only the hex text input is submitted.
        */ ?>
        <input
            type="color"
            id="<?= $pickerId ?>"
            value="<?= e($value) ?>"
            class="h-9 w-12 cursor-pointer rounded border border-slate-300 bg-white p-0.5 shadow-sm"
            aria-label="Colour picker for <?= e($label) ?>"
            oninput="document.getElementById('<?= $inputId ?>').value = this.value"
        >

        <?php // Hex text input — this is what gets submitted ?>
        <input
            type="text"
            id="<?= $inputId ?>"
            name="<?= e($name) ?>"
            value="<?= e($value) ?>"
            maxlength="7"
            pattern="#[0-9a-fA-F]{6}"
            placeholder="#1d4ed8"
            class="w-32 rounded-lg border px-3 py-2 text-sm font-mono text-slate-800 shadow-sm
                   transition focus:outline-none focus:ring-2 focus:ring-offset-1
                   <?= $error ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 bg-white focus:ring-blue-500' ?>"
            oninput="syncColorPicker(this, '<?= $pickerId ?>')"
            <?= $error ? 'aria-describedby="' . e($name) . '-error" aria-invalid="true"' : '' ?>
        >

        <?php // Live colour swatch ?>
        <div
            class="h-9 w-9 rounded-full border border-slate-200 shadow-inner"
            id="swatch_<?= e($name) ?>"
            style="background-color: <?= e($value) ?>;"
            aria-hidden="true"
        ></div>
    </div>

    <?php if ($hint && !$error): ?>
        <p class="text-xs text-slate-500"><?= e($hint) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p id="<?= e($name) ?>-error" class="text-xs text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle-fill text-red-500"></i>
            <?= e($error) ?>
        </p>
    <?php endif; ?>
</div>

<?php /**
    syncColorPicker: called when the user types in the hex input.
    Updates the native colour picker only when the value is a valid 6-digit hex.
    Also updates the swatch div in real time.
*/ ?>
<script>
function syncColorPicker(hexInput, pickerId) {
    const val   = hexInput.value.trim();
    const valid = /^#[0-9a-fA-F]{6}$/.test(val);
    const picker = document.getElementById(pickerId);
    const swatch = document.getElementById('swatch_' + hexInput.name);

    if (valid) {
        if (picker) picker.value = val;
        if (swatch) swatch.style.backgroundColor = val;
    }
}
</script>
