<?php
/**
 * resources/components/forms/_input.php
 *
 * Reusable text/email/url/number input field.
 *
 * Expected variables (pass via extract or direct scope):
 *
 * @var string      $name         Input name attribute — also used as the id
 * @var string      $label        Human-readable label text
 * @var string      $type         HTML input type: 'text', 'email', 'url', 'number', 'password'
 * @var mixed       $value        Current value (from DB or old() flash)
 * @var string      $placeholder  Placeholder text (optional)
 * @var string|null $hint         Optional helper text shown below the field
 * @var string|null $error        Validation error message for this field (optional)
 * @var bool        $required     Whether to show the required indicator (default: false)
 * @var bool        $readonly     Whether the field is readonly (default: false)
 *
 * Usage:
 *   <?= component('forms/_input', [
 *       'name'  => 'site_name',
 *       'label' => 'Website Name',
 *       'type'  => 'text',
 *       'value' => $settings['site_name'] ?? '',
 *   ]); ?>
 */

$type        = $type        ?? 'text';
$placeholder = $placeholder ?? '';
$hint        = $hint        ?? null;
$error       = $error       ?? null;
$required    = $required    ?? false;
$readonly    = $readonly    ?? false;
$value       = $value       ?? '';

$inputClasses = 'w-full rounded-lg border px-3 py-2 text-sm text-slate-800 shadow-sm
    placeholder-slate-400 transition focus:outline-none focus:ring-2 focus:ring-offset-1 '
    . ($error
        ? 'border-red-400 bg-red-50 focus:ring-red-400'
        : 'border-slate-300 bg-white focus:ring-blue-500');
?>

<div class="flex flex-col gap-1">
    <label for="<?= e($name) ?>" class="text-sm font-medium text-slate-700">
        <?= e($label) ?>
        <?php if ($required): ?>
            <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>

    <input
        type="<?= e($type) ?>"
        id="<?= e($name) ?>"
        name="<?= e($name) ?>"
        value="<?= e($value) ?>"
        placeholder="<?= e($placeholder) ?>"
        <?= $required ? 'required' : '' ?>
        <?= $readonly  ? 'readonly aria-readonly="true"' : '' ?>
        class="<?= $inputClasses ?>"
        <?= $error ? 'aria-describedby="' . e($name) . '-error" aria-invalid="true"' : '' ?>
    >

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
