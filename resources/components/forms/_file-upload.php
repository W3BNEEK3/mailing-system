<?php
/**
 * resources/components/forms/_file-upload.php
 *
 * Styled file upload field with a current-file preview section.
 *
 * When an existing file is present ($currentUrl is set), the component shows:
 *   - A thumbnail preview (for images) or a filename label (for other files)
 *   - A "Remove" checkbox that, when checked, signals the controller to delete
 *     the existing file on save (even if no new file is uploaded)
 *   - A "Replace" label nudging the user that uploading a new file will
 *     automatically replace the old one
 *
 * When no existing file is present ($currentUrl is null), the component shows
 * only the upload input.
 *
 * @var string      $name        Form field name for the file input
 * @var string      $label       Label text shown above the field
 * @var string|null $currentUrl  Full URL to the currently stored file (or null)
 * @var string|null $currentPath Relative storage path stored in DB (for the remove checkbox value)
 * @var string      $accept      Accept attribute string: 'image/png,image/jpeg,image/svg+xml'
 * @var string|null $hint        Optional helper text
 * @var string|null $error       Validation error message
 * @var bool        $isImage     Whether to render an <img> preview (true) or a filename label (false)
 *
 * Usage:
 *   <?= component('forms/_file-upload', [
 *       'name'        => 'site_logo',
 *       'label'       => 'Site Logo',
 *       'currentUrl'  => $settings['site_logo_path'] ? storageUrl($settings['site_logo_path']) : null,
 *       'currentPath' => $settings['site_logo_path'] ?? null,
 *       'accept'      => 'image/png,image/jpeg,image/svg+xml',
 *       'hint'        => 'PNG, JPEG or SVG. Max 2MB.',
 *       'isImage'     => true,
 *   ]); ?>
 */

$currentUrl  = $currentUrl  ?? null;
$currentPath = $currentPath ?? null;
$accept      = $accept      ?? '*/*';
$hint        = $hint        ?? null;
$error       = $error       ?? null;
$isImage     = $isImage     ?? true;
$hasFile     = $currentUrl !== null && $currentUrl !== '';
?>

<div class="flex flex-col gap-2">
    <label class="text-sm font-medium text-slate-700">
        <?= e($label) ?>
    </label>

    <?php if ($hasFile): ?>
        <?php // Current file preview ?>
        <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
            <?php if ($isImage): ?>
                <img
                    src="<?= e($currentUrl) ?>"
                    alt="Current <?= e($label) ?>"
                    class="h-12 w-auto max-w-[8rem] rounded border border-slate-200 object-contain bg-white"
                >
            <?php else: ?>
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <i class="bi bi-file-earmark-code text-slate-400 text-lg"></i>
                    <span class="font-mono truncate max-w-[14rem]"><?= e(basename($currentPath ?? '')) ?></span>
                </div>
            <?php endif; ?>

            <div class="ml-auto flex flex-col gap-1 text-right">
                <span class="text-xs text-slate-500">Current file</span>
                <label class="flex items-center gap-1.5 cursor-pointer text-xs text-red-600 hover:text-red-700">
                    <input
                        type="checkbox"
                        name="remove_<?= e($name) ?>"
                        value="1"
                        class="rounded border-slate-300 text-red-500 focus:ring-red-400"
                    >
                    Remove
                </label>
            </div>
        </div>

        <p class="text-xs text-slate-500">Upload a new file below to replace it.</p>
    <?php endif; ?>

    <?php // File input ?>
    <label
        for="<?= e($name) ?>_input"
        class="flex cursor-pointer items-center justify-center gap-2 rounded-lg border-2 border-dashed
               border-slate-300 bg-white px-4 py-5 text-sm text-slate-500
               transition hover:border-blue-400 hover:bg-blue-50 hover:text-blue-600
               <?= $error ? 'border-red-400 bg-red-50' : '' ?>"
    >
        <i class="bi bi-cloud-arrow-up text-xl"></i>
        <span>
            <?= $hasFile ? 'Choose a replacement file' : 'Click to upload' ?>
        </span>
        <input
            type="file"
            id="<?= e($name) ?>_input"
            name="<?= e($name) ?>"
            accept="<?= e($accept) ?>"
            class="sr-only"
            onchange="previewFileUpload(this, '<?= e($name) ?>_preview')"
        >
    </label>

    <?php // Instant preview of newly selected file (before submit) ?>
    <?php if ($isImage): ?>
        <img
            id="<?= e($name) ?>_preview"
            src=""
            alt="Preview"
            class="hidden h-12 w-auto max-w-[8rem] rounded border border-slate-200 object-contain bg-white"
        >
    <?php endif; ?>

    <?php if ($hint && !$error): ?>
        <p class="text-xs text-slate-500"><?= e($hint) ?></p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p class="text-xs text-red-600 flex items-center gap-1">
            <i class="bi bi-exclamation-circle-fill text-red-500"></i>
            <?= e($error) ?>
        </p>
    <?php endif; ?>
</div>

<script>
/**
 * Show an instant preview of a selected image file before the form is submitted.
 * Only runs for image files — silently skips non-image selections.
 */
function previewFileUpload(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    const file = input.files[0];
    if (!file || !file.type.startsWith('image/')) {
        preview.classList.add('hidden');
        preview.src = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        preview.src = e.target.result;
        preview.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
}
</script>
