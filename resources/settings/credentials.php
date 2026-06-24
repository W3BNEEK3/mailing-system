<?php
/**
 * resources/settings/credentials.php
 *
 * Email provider credentials page.
 *
 * @var \App\Models\Credential|null $resend          Resend credential row, or null
 * @var \App\Models\Credential|null $smtp            SMTP credential row, or null
 * @var string|null                 $activeProvider  'resend', 'smtp', or null
 */

$flashToast = session()->getFlash('_toast');

// Determine which provider sections to show as "active" in the UI
$resendIsActive = $activeProvider === 'resend';
$smtpIsActive   = $activeProvider === 'smtp';

// Read SMTP config values for form pre-population (never expose password)
$smtpConfig = $smtp ? $smtp->decryptedConfig() : [];

$fieldErrors = errors(); // keyed by field name
?>

<div class="mx-auto max-w-3xl px-4 py-8">

    <!-- Page header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Email Credentials</h1>
        <p class="mt-1 text-sm text-slate-500">
            Configure the email provider used to send messages from Emirates.
            Only one provider can be active at a time.
            Credentials are stored encrypted and never exposed in logs or API responses.
        </p>
    </div>

    <!-- Flash toast bridge -->
    <?php if ($flashToast): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.dispatchEvent(new CustomEvent('showToast', { detail: <?= json_encode($flashToast) ?> }));
            });
        </script>
    <?php endif; ?>

    <!-- ── Active Provider Indicator ─────────────────────────────────────── -->
    <?php if ($activeProvider): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200
                    bg-emerald-50 px-5 py-3.5 text-sm text-emerald-800">
            <i class="bi bi-send-check-fill text-emerald-500 text-base"></i>
            <span>
                Active provider:
                <strong class="font-semibold capitalize"><?= e($activeProvider) ?></strong>
                — emails will be sent through this provider.
            </span>
        </div>
    <?php else: ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-amber-200
                    bg-amber-50 px-5 py-3.5 text-sm text-amber-800">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-base"></i>
            <span>
                No active provider. Save credentials and check <strong>Set as active</strong>
                to enable email sending.
            </span>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- RESEND SECTION                                                         -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <section class="mb-8 rounded-xl border <?= $resendIsActive ? 'border-blue-300 shadow-blue-100' : 'border-slate-200' ?> bg-white shadow-sm overflow-hidden">

        <!-- Section header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-slate-900 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-send-fill text-white text-sm"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Resend</h2>
                    <p class="text-xs text-slate-500">API-based provider. Recommended for production.</p>
                </div>
            </div>
            <?php if ($resendIsActive): ?>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100
                             px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Active
                </span>
            <?php elseif ($resend): ?>
                <span class="inline-flex items-center rounded-full bg-slate-100
                             px-3 py-1 text-xs font-medium text-slate-500">
                    Saved (inactive)
                </span>
            <?php endif; ?>
        </div>

        <!-- Resend form -->
        <form
            method="POST"
            action="/settings/credentials"
            class="px-6 py-6 space-y-5"
            hx-post="/settings/credentials"
            hx-indicator="#resend-save-indicator"
        >
            <?= csrf_field() ?>
            <input type="hidden" name="provider" value="resend">

            <!-- API Key field -->
            <div class="flex flex-col gap-1">
                <label for="api_key" class="text-sm font-medium text-slate-700">
                    API Key <span class="text-red-500 ml-0.5">*</span>
                </label>

                <!-- Saved credential indicator -->
                <?php if ($resend && $resend->maskField('api_key') !== ''): ?>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-200
                                bg-slate-50 px-3 py-2 mb-1">
                        <i class="bi bi-key-fill text-slate-400 text-sm"></i>
                        <span class="font-mono text-sm text-slate-500">
                            <?= e($resend->maskField('api_key')) ?>
                        </span>
                        <span class="ml-auto text-xs text-slate-400">Saved</span>
                    </div>
                <?php endif; ?>

                <div class="relative">
                    <input
                        type="password"
                        id="api_key"
                        name="api_key"
                        placeholder="<?= $resend ? 'Leave blank to keep existing key' : 're_abc123...' ?>"
                        autocomplete="new-password"
                        class="w-full rounded-lg border px-3 py-2 pr-10 text-sm text-slate-800 shadow-sm
                               placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-offset-1
                               <?= isset($fieldErrors['api_key']) ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 bg-white focus:ring-blue-500' ?>"
                    >
                    <!-- Reveal toggle button -->
                    <button
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        onclick="togglePasswordVisibility('api_key', this)"
                        title="Show/hide API key"
                        aria-label="Toggle API key visibility"
                    >
                        <i class="bi bi-eye text-sm" id="api_key_eye"></i>
                    </button>
                </div>
                <p class="text-xs text-slate-500">
                    Found in your Resend dashboard under API Keys.
                    <a href="https://resend.com/api-keys" target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">Open Resend →</a>
                </p>
                <?php if ($err = ($fieldErrors['api_key'] ?? null)): ?>
                    <p class="text-xs text-red-600 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle-fill text-red-500"></i>
                        <?= e($err) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- From Email (informational) -->
            <?= component('forms/_input', [
                'name'        => 'from_email',
                'label'       => 'Verified From Email (optional)',
                'type'        => 'email',
                'value'       => old('from_email', $resend ? ($resend->decryptedConfig()['from_email'] ?? '') : ''),
                'placeholder' => 'hello@yourdomain.com',
                'hint'        => 'Must be a verified address in your Resend account. Used for informational display — the actual sender is set in General Settings.',
            ]); ?>

            <!-- Set as active checkbox -->
            <div class="flex items-center gap-2.5">
                <input
                    type="checkbox"
                    id="resend_set_active"
                    name="set_active"
                    value="1"
                    <?= $resendIsActive ? 'checked' : '' ?>
                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                >
                <label for="resend_set_active" class="text-sm text-slate-700 cursor-pointer">
                    Set Resend as the active provider
                </label>
            </div>

            <!-- Action row -->
            <div class="flex items-center gap-3 pt-2">

                <!-- Test Connection -->
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white
                           px-4 py-2 text-sm font-medium text-slate-700 shadow-sm
                           hover:bg-slate-50 transition"
                    hx-post="/settings/credentials/test"
                    hx-vals='{"provider": "resend"}'
                    hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                    hx-target="#test-result-resend"
                    hx-swap="innerHTML"
                    hx-indicator="#test-resend-indicator"
                >
                    <span id="test-resend-indicator"
                          class="htmx-indicator inline-flex items-center">
                        <svg class="animate-spin h-3.5 w-3.5 text-slate-500 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </span>
                    <i class="bi bi-wifi"></i>
                    Test Connection
                </button>

                <!-- Save -->
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2
                           text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
                >
                    <span id="resend-save-indicator" class="htmx-indicator">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </span>
                    <i class="bi bi-floppy"></i>
                    Save
                </button>
            </div>

            <!-- Test result area (replaced by HTMX after test) -->
            <div id="test-result-resend" class="min-h-[2rem]"></div>

        </form>
    </section>


    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <!-- SMTP SECTION                                                           -->
    <!-- ══════════════════════════════════════════════════════════════════════ -->
    <section class="rounded-xl border <?= $smtpIsActive ? 'border-blue-300 shadow-blue-100' : 'border-slate-200' ?> bg-white shadow-sm overflow-hidden">

        <!-- Section header -->
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                    <i class="bi bi-envelope-fill text-orange-600 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-slate-800">SMTP</h2>
                    <p class="text-xs text-slate-500">Generic SMTP — works with Gmail, Zoho, Mailgun, Brevo, etc.</p>
                </div>
            </div>
            <?php if ($smtpIsActive): ?>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100
                             px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Active
                </span>
            <?php elseif ($smtp): ?>
                <span class="inline-flex items-center rounded-full bg-slate-100
                             px-3 py-1 text-xs font-medium text-slate-500">
                    Saved (inactive)
                </span>
            <?php endif; ?>
        </div>

        <!-- SMTP form -->
        <form
            method="POST"
            action="/settings/credentials"
            class="px-6 py-6 space-y-5"
            hx-post="/settings/credentials"
            hx-indicator="#smtp-save-indicator"
        >
            <?= csrf_field() ?>
            <input type="hidden" name="provider" value="smtp">

            <!-- Host + Port row -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="sm:col-span-2">
                    <?= component('forms/_input', [
                        'name'        => 'host',
                        'label'       => 'SMTP Host',
                        'type'        => 'text',
                        'value'       => old('host', $smtpConfig['host'] ?? ''),
                        'placeholder' => 'smtp.gmail.com',
                        'required'    => true,
                        'error'       => $fieldErrors['host'] ?? null,
                    ]); ?>
                </div>
                <div>
                    <?= component('forms/_input', [
                        'name'        => 'port',
                        'label'       => 'Port',
                        'type'        => 'number',
                        'value'       => old('port', $smtpConfig['port'] ?? '587'),
                        'placeholder' => '587',
                        'required'    => true,
                        'error'       => $fieldErrors['port'] ?? null,
                    ]); ?>
                </div>
            </div>

            <!-- Encryption dropdown -->
            <div class="flex flex-col gap-1">
                <label for="encryption" class="text-sm font-medium text-slate-700">
                    Encryption <span class="text-red-500 ml-0.5">*</span>
                </label>
                <select
                    id="encryption"
                    name="encryption"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                           text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <?php
                    $encOpts = [
                        ''         => 'None',
                        'ssl'      => 'SSL (port 465)',
                        'tls'      => 'TLS (port 587)',
                        'starttls' => 'STARTTLS (port 587)',
                    ];
                    $currentEnc = old('encryption', $smtpConfig['encryption'] ?? 'tls');
                    foreach ($encOpts as $val => $label):
                    ?>
                        <option value="<?= e($val) ?>" <?= $currentEnc === $val ? 'selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Username -->
            <?= component('forms/_input', [
                'name'        => 'username',
                'label'       => 'Username / Email',
                'type'        => 'email',
                'value'       => old('username', $smtpConfig['username'] ?? ''),
                'placeholder' => 'you@gmail.com',
                'hint'        => 'Usually your full email address.',
                'required'    => true,
                'error'       => $fieldErrors['username'] ?? null,
            ]); ?>

            <!-- Password -->
            <div class="flex flex-col gap-1">
                <label for="smtp_password" class="text-sm font-medium text-slate-700">
                    Password / App Password
                    <?php if (!$smtp): ?>
                        <span class="text-red-500 ml-0.5">*</span>
                    <?php endif; ?>
                </label>

                <!-- Saved indicator -->
                <?php if ($smtp && $smtp->maskField('password') !== ''): ?>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-200
                                bg-slate-50 px-3 py-2 mb-1">
                        <i class="bi bi-key-fill text-slate-400 text-sm"></i>
                        <span class="font-mono text-sm text-slate-500">
                            <?= e($smtp->maskField('password')) ?>
                        </span>
                        <span class="ml-auto text-xs text-slate-400">Saved</span>
                    </div>
                <?php endif; ?>

                <div class="relative">
                    <input
                        type="password"
                        id="smtp_password"
                        name="password"
                        placeholder="<?= $smtp ? 'Leave blank to keep existing password' : 'Enter password or app password' ?>"
                        autocomplete="new-password"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10
                               text-sm text-slate-800 shadow-sm placeholder-slate-400
                               focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-blue-500
                               <?= isset($fieldErrors['password']) ? 'border-red-400 bg-red-50' : '' ?>"
                    >
                    <button
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        onclick="togglePasswordVisibility('smtp_password', this)"
                        aria-label="Toggle password visibility"
                    >
                        <i class="bi bi-eye text-sm" id="smtp_password_eye"></i>
                    </button>
                </div>

                <p class="text-xs text-slate-500">
                    For Gmail: use an
                    <a href="https://support.google.com/accounts/answer/185833" target="_blank" rel="noopener"
                       class="text-blue-600 hover:underline">App Password</a>
                    instead of your account password.
                </p>
                <?php if ($err = ($fieldErrors['password'] ?? null)): ?>
                    <p class="text-xs text-red-600 flex items-center gap-1">
                        <i class="bi bi-exclamation-circle-fill text-red-500"></i>
                        <?= e($err) ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- From Name + From Email -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <?= component('forms/_input', [
                    'name'        => 'from_name',
                    'label'       => 'From Name (optional)',
                    'type'        => 'text',
                    'value'       => old('from_name', $smtpConfig['from_name'] ?? ''),
                    'placeholder' => 'Acme Mailer',
                    'hint'        => 'Display name in the From field.',
                ]); ?>

                <?= component('forms/_input', [
                    'name'        => 'from_email',
                    'label'       => 'From Email (optional)',
                    'type'        => 'email',
                    'value'       => old('from_email', $smtpConfig['from_email'] ?? ''),
                    'placeholder' => 'you@gmail.com',
                    'hint'        => 'Defaults to username if not set.',
                ]); ?>
            </div>

            <!-- Set as active checkbox -->
            <div class="flex items-center gap-2.5">
                <input
                    type="checkbox"
                    id="smtp_set_active"
                    name="set_active"
                    value="1"
                    <?= $smtpIsActive ? 'checked' : '' ?>
                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                >
                <label for="smtp_set_active" class="text-sm text-slate-700 cursor-pointer">
                    Set SMTP as the active provider
                </label>
            </div>

            <!-- Action row -->
            <div class="flex items-center gap-3 pt-2">

                <!-- Test Connection -->
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white
                           px-4 py-2 text-sm font-medium text-slate-700 shadow-sm
                           hover:bg-slate-50 transition"
                    hx-post="/settings/credentials/test"
                    hx-vals='{"provider": "smtp"}'
                    hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                    hx-target="#test-result-smtp"
                    hx-swap="innerHTML"
                    hx-indicator="#test-smtp-indicator"
                >
                    <span id="test-smtp-indicator" class="htmx-indicator inline-flex items-center">
                        <svg class="animate-spin h-3.5 w-3.5 text-slate-500 mr-1" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </span>
                    <i class="bi bi-wifi"></i>
                    Test Connection
                </button>

                <!-- Save -->
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2
                           text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
                >
                    <span id="smtp-save-indicator" class="htmx-indicator">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                    </span>
                    <i class="bi bi-floppy"></i>
                    Save
                </button>
            </div>

            <!-- Test result area -->
            <div id="test-result-smtp" class="min-h-[2rem]"></div>

        </form>
    </section>

</div>

<!-- Password visibility toggle script (self-contained, no build step needed) -->
<script>
/**
 * Toggle a password field between type="password" and type="text".
 * Updates the eye icon to reflect the current state.
 *
 * @param {string} inputId  The id of the <input> element
 * @param {HTMLElement} btn The toggle button element (to update its icon)
 */
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');

    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash text-sm';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye text-sm';
    }
}
</script>
