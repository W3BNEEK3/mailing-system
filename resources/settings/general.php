<?php
/**
 * resources/settings/general.php
 *
 * The General Settings page. Rendered inside resources/layouts/app.php.
 *
 * Variables provided by SettingsController::index():
 *   @var array  $settings           All settings as key → value (may include nulls)
 *   @var array  $supportedLanguages Language code → display name
 *   @var array  $timezones          PHP timezone identifier strings
 *
 * Form behaviour:
 *   - Standard HTML form POST to /settings/general
 *   - The Save button also has hx-post for HTMX-enhanced saves (no full page reload)
 *   - Logo file inputs are always separate from the HTMX form include, so
 *     file uploads fall back to a standard multipart POST (HTMX 2.x supports
 *     multipart via hx-encoding="multipart/form-data" on the form element)
 *   - Validation errors are flashed by the controller and rendered via errors()
 */

// Convenience: read current values with defaults for first-time setup
$s = $settings; // shorthand

// Build logo URLs if paths are stored
$siteLogoUrl  = ($s['site_logo_path']  ?? null) ? storageUrl($s['site_logo_path'])  : null;
$emailLogoUrl = ($s['email_logo_path'] ?? null) ? storageUrl($s['email_logo_path']) : null;

// Check for field-level validation errors from the flash
$fieldErrors = errors(); // returns [] or keyed array
?>

<div class="mx-auto max-w-3xl px-4 py-8">

    <?php // Page header ?>
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">General Settings</h1>
        <p class="mt-1 text-sm text-slate-500">
            Configure your platform's identity, branding, and sending defaults.
        </p>
    </div>

    <?php // Flash toast from a non-HTMX save ?>
    <?php
    $flashToast = session()->getFlash('_toast');
    if ($flashToast):
    ?>
        <script>
            // Dispatch the toast event so app.js picks it up on page load
            document.addEventListener('DOMContentLoaded', () => {
                document.dispatchEvent(new CustomEvent('showToast', {
                    detail: <?= json_encode($flashToast) ?>
                }));
            });
        </script>
    <?php endif; ?>

    <?php // Main settings form ?>
    <form
        id="settings-form"
        method="POST"
        action="/settings/general"
        enctype="multipart/form-data"
        hx-post="/settings/general"
        hx-encoding="multipart/form-data"
        hx-include="#settings-form"
        hx-indicator="#save-indicator"
        class="space-y-10"
    >
        <?= csrf_field() ?>

        <?php // ── Section 1: Platform Identity ──────────────────────────────── ?>
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-800">Platform Identity</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    How this application identifies itself in the browser and in the admin interface.
                </p>
            </div>

            <div class="px-6 py-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <?= component('forms/_input', [
                    'name'        => 'site_name',
                    'label'       => 'Platform Name',
                    'type'        => 'text',
                    'value'       => old('site_name', $s['site_name'] ?? ''),
                    'placeholder' => 'Emirates',
                    'hint'        => 'Shown in the browser title bar and admin header.',
                    'required'    => true,
                    'error'       => $fieldErrors['site_name'] ?? null,
                ]) ?>

                <?= component('forms/_input', [
                    'name'        => 'site_url',
                    'label'       => 'Platform URL',
                    'type'        => 'url',
                    'value'       => old('site_url', $s['site_url'] ?? ''),
                    'placeholder' => 'https://yourdomain.com',
                    'hint'        => 'The base URL where this admin panel is hosted.',
                    'required'    => true,
                    'error'       => $fieldErrors['site_url'] ?? null,
                ]) ?>

                <div class="sm:col-span-2">
                    <?= component('forms/_file-upload', [
                        'name'        => 'site_logo',
                        'label'       => 'Site Logo',
                        'currentUrl'  => $siteLogoUrl,
                        'currentPath' => $s['site_logo_path'] ?? null,
                        'accept'      => 'image/png,image/jpeg,image/svg+xml',
                        'hint'        => 'PNG, JPEG, or SVG. Max 2MB. Displayed in the admin navigation — does not appear in emails.',
                        'isImage'     => true,
                        'error'       => $fieldErrors['site_logo'] ?? null,
                    ]) ?>
                </div>
            </div>
        </section>

        <?php // ── Section 2: Email Sending Defaults ──────────────────────────── ?>
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-800">Email Sending Defaults</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    These values populate the "From" field on all outgoing emails. The sender email must
                    match your verified domain in your email provider.
                </p>
            </div>

            <div class="px-6 py-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <?= component('forms/_input', [
                    'name'        => 'sender_name',
                    'label'       => 'Default Sender Name',
                    'type'        => 'text',
                    'value'       => old('sender_name', $s['sender_name'] ?? ''),
                    'placeholder' => 'Acme Mailer',
                    'hint'        => 'The name recipients see in their inbox "From" field.',
                    'required'    => true,
                    'error'       => $fieldErrors['sender_name'] ?? null,
                ]) ?>

                <?= component('forms/_input', [
                    'name'        => 'sender_email',
                    'label'       => 'Default Sender Email',
                    'type'        => 'email',
                    'value'       => old('sender_email', $s['sender_email'] ?? ''),
                    'placeholder' => 'hello@yourdomain.com',
                    'hint'        => 'Must be a verified address in your active email provider.',
                    'required'    => true,
                    'error'       => $fieldErrors['sender_email'] ?? null,
                ]) ?>
            </div>
        </section>

        <?php // ── Section 3: Email Branding ──────────────────────────────────── ?>
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-800">Email Branding</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    The global logo and colour theme injected into all email templates via
                    <code class="font-mono bg-slate-100 px-1 rounded text-xs">{{LOGO_URL}}</code>,
                    <code class="font-mono bg-slate-100 px-1 rounded text-xs">{{PRIMARY_COLOR}}</code>, and
                    <code class="font-mono bg-slate-100 px-1 rounded text-xs">{{SECONDARY_COLOR}}</code>
                    placeholders. Individual emails can override these values.
                </p>
            </div>

            <div class="px-6 py-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <?= component('forms/_file-upload', [
                        'name'        => 'email_logo',
                        'label'       => 'Global Email Logo',
                        'currentUrl'  => $emailLogoUrl,
                        'currentPath' => $s['email_logo_path'] ?? null,
                        'accept'      => 'image/png,image/jpeg,image/svg+xml',
                        'hint'        => 'PNG, JPEG, or SVG. Max 2MB. This logo is injected into all email templates that contain the {{LOGO_URL}} placeholder.',
                        'isImage'     => true,
                        'error'       => $fieldErrors['email_logo'] ?? null,
                    ]) ?>
                </div>

                <?= component('forms/_color-picker', [
                    'name'  => 'primary_color',
                    'label' => 'Primary Colour',
                    'value' => old('primary_color', $s['primary_color'] ?? '#1d4ed8'),
                    'hint'  => 'Injected as {{PRIMARY_COLOR}} — used for buttons, headings, backgrounds.',
                    'error' => $fieldErrors['primary_color'] ?? null,
                ]) ?>

                <?= component('forms/_color-picker', [
                    'name'  => 'secondary_color',
                    'label' => 'Secondary Colour',
                    'value' => old('secondary_color', $s['secondary_color'] ?? '#0f172a'),
                    'hint'  => 'Injected as {{SECONDARY_COLOR}} — used for accents, footers, borders.',
                    'error' => $fieldErrors['secondary_color'] ?? null,
                ]) ?>
            </div>
        </section>

        <?php // ── Section 4: Localisation ─────────────────────────────────────── ?>
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
                <h2 class="text-base font-semibold text-slate-800">Localisation</h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Default language for email composition and timezone used for log timestamps.
                </p>
            </div>

            <div class="px-6 py-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <?php // Default Language ?>
                <div class="flex flex-col gap-1">
                    <label for="default_language" class="text-sm font-medium text-slate-700">
                        Default Language
                    </label>
                    <select
                        id="default_language"
                        name="default_language"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                               text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1
                               <?= isset($fieldErrors['default_language']) ? 'border-red-400 bg-red-50' : '' ?>"
                    >
                        <?php foreach ($supportedLanguages as $code => $label): ?>
                            <option
                                value="<?= e($code) ?>"
                                <?= (old('default_language', $s['default_language'] ?? 'en') === $code) ? 'selected' : '' ?>
                            >
                                <?= e($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($err = ($fieldErrors['default_language'] ?? null)): ?>
                        <p class="text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>

                <?php // Timezone ?>
                <div class="flex flex-col gap-1">
                    <label for="timezone" class="text-sm font-medium text-slate-700">
                        Timezone
                    </label>
                    <select
                        id="timezone"
                        name="timezone"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                               text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1
                               <?= isset($fieldErrors['timezone']) ? 'border-red-400 bg-red-50' : '' ?>"
                    >
                        <?php foreach ($timezones as $tz): ?>
                            <option
                                value="<?= e($tz) ?>"
                                <?= (old('timezone', $s['timezone'] ?? 'UTC') === $tz) ? 'selected' : '' ?>
                            >
                                <?= e($tz) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-slate-500">Used for all log timestamps in the admin panel.</p>
                    <?php if ($err = ($fieldErrors['timezone'] ?? null)): ?>
                        <p class="text-xs text-red-600"><?= e($err) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php // ── Save bar ───────────────────────────────────────────────────── ?>
        <div class="flex items-center justify-end gap-3 rounded-xl border border-slate-200
                    bg-white px-6 py-4 shadow-sm sticky bottom-4">
            <span
                id="save-indicator"
                class="htmx-indicator flex items-center gap-1.5 text-sm text-slate-500"
            >
                <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                Saving…
            </span>

            <button
                type="submit"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5
                       text-sm font-medium text-white shadow-sm transition
                       hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500
                       focus:ring-offset-2 active:scale-95"
            >
                <i class="bi bi-floppy"></i>
                Save Changes
            </button>
        </div>

    </form>
</div>
