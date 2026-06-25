<?php
$flashToast = session()->getFlash('_toast');
$resendIsActive = $activeProvider === 'resend';
$smtpIsActive   = $activeProvider === 'smtp';
$smtpConfig = $smtp ? $smtp->decryptedConfig() : [];
$fieldErrors = errors();
?>

<div class="mx-auto max-w-3xl px-4 py-8">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-900">Email Credentials</h1>
        <p class="mt-1 text-sm text-slate-500">
            Configure the email provider used to send messages from Emirates.
            Only one provider can be active at a time.
        </p>
    </div>

    <?php if ($flashToast): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.dispatchEvent(new CustomEvent('showToast', { detail: <?= json_encode($flashToast) ?> }));
            });
        </script>
    <?php endif; ?>

    <?php if ($activeProvider): ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3.5 text-sm text-emerald-800">
            <i class="bi bi-send-check-fill text-emerald-500 text-base"></i>
            <span>Active provider: <strong class="font-semibold capitalize"><?= e($activeProvider) ?></strong></span>
        </div>
    <?php else: ?>
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3.5 text-sm text-amber-800">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-base"></i>
            <span>No active provider. Save credentials and check <strong>Set as active</strong> to enable email sending.</span>
        </div>
    <?php endif; ?>

    <section class="mb-8 rounded-xl border <?= $resendIsActive ? 'border-blue-300 shadow-blue-100' : 'border-slate-200' ?> bg-white shadow-sm overflow-hidden">
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
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                </span>
            <?php elseif ($resend): ?>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">Saved</span>
            <?php endif; ?>
        </div>

        <form method="POST" action="/settings/credentials" class="px-6 py-6 space-y-5" hx-post="/settings/credentials" hx-indicator="#resend-save-indicator">
            <?= csrf_field() ?>
            <input type="hidden" name="provider" value="resend">

            <div class="flex flex-col gap-1">
                <label for="api_key" class="text-sm font-medium text-slate-700">API Key <span class="text-red-500 ml-0.5">*</span></label>
                <?php if ($resend && $resend->maskField('api_key') !== ''): ?>
                    <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 mb-1">
                        <i class="bi bi-key-fill text-slate-400 text-sm"></i>
                        <span class="font-mono text-sm text-slate-500"><?= e($resend->maskField('api_key')) ?></span>
                    </div>
                <?php endif; ?>
                <div class="relative">
                    <input type="password" id="api_key" name="api_key" placeholder="<?= $resend ? 'Leave blank to keep existing key' : 're_abc123...' ?>"
                           class="w-full rounded-lg border px-3 py-2 pr-10 text-sm text-slate-800 shadow-sm focus:outline-none focus:ring-2 <?= isset($fieldErrors['api_key']) ? 'border-red-400 bg-red-50 focus:ring-red-400' : 'border-slate-300 bg-white focus:ring-blue-500' ?>">
                    <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" onclick="togglePasswordVisibility('api_key', this)">
                        <i class="bi bi-eye text-sm" id="api_key_eye"></i>
                    </button>
                </div>
            </div>

            <?= component('forms/_input', ['name' => 'from_email', 'label' => 'Verified From Email (optional)', 'type' => 'email', 'value' => old('from_email', $resend ? ($resend->decryptedConfig()['from_email'] ?? '') : ''), 'placeholder' => 'hello@yourdomain.com']); ?>

            <div class="flex items-center gap-2.5">
                <input type="checkbox" id="resend_set_active" name="set_active" value="1" <?= $resendIsActive ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                <label for="resend_set_active" class="text-sm text-slate-700 cursor-pointer">Set Resend as the active provider</label>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                <button type="button" hx-post="/settings/credentials/test" hx-vals='{"provider": "resend"}' hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}' hx-target="#test-result-resend" hx-swap="innerHTML" hx-indicator="#test-resend-indicator"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    <span id="test-resend-indicator" class="htmx-indicator inline-flex items-center"><svg class="animate-spin h-3.5 w-3.5 text-slate-500 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg></span>
                    <i class="bi bi-wifi"></i> Test Connection
                </button>

                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition">
                    <span id="resend-save-indicator" class="htmx-indicator"><svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg></span>
                    <i class="bi bi-floppy"></i> Save
                </button>
            </div>
            <div id="test-result-resend" class="min-h-[2rem]"></div>
        </form>
    </section>

    <section class="rounded-xl border <?= $smtpIsActive ? 'border-blue-300 shadow-blue-100' : 'border-slate-200' ?> bg-white shadow-sm overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0"><i class="bi bi-envelope-fill text-orange-600 text-sm"></i></div>
                <div>
                    <h2 class="text-base font-semibold text-slate-800">SMTP</h2>
                    <p class="text-xs text-slate-500">Generic SMTP — works with Gmail, Zoho, Mailgun, Brevo, etc.</p>
                </div>
            </div>
        </div>

        <form method="POST" action="/settings/credentials" class="px-6 py-6 space-y-5" hx-post="/settings/credentials" hx-indicator="#smtp-save-indicator">
            <?= csrf_field() ?>
            <input type="hidden" name="provider" value="smtp">

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="sm:col-span-2"><?= component('forms/_input', ['name' => 'host', 'label' => 'SMTP Host', 'type' => 'text', 'value' => old('host', $smtpConfig['host'] ?? '')]); ?></div>
                <div><?= component('forms/_input', ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'value' => old('port', $smtpConfig['port'] ?? '587')]); ?></div>
            </div>

            <div class="flex flex-col gap-1">
                <label for="encryption" class="text-sm font-medium text-slate-700">Encryption</label>
                <select id="encryption" name="encryption" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="tls">TLS (port 587)</option>
                    <option value="ssl">SSL (port 465)</option>
                    <option value="">None</option>
                </select>
            </div>

            <?= component('forms/_input', ['name' => 'username', 'label' => 'Username / Email', 'type' => 'email', 'value' => old('username', $smtpConfig['username'] ?? '')]); ?>

            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium text-slate-700">Password</label>
                <div class="relative">
                    <input type="password" id="smtp_password" name="password" placeholder="Enter password or app password" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="button" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" onclick="togglePasswordVisibility('smtp_password', this)">
                        <i class="bi bi-eye text-sm"></i>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <input type="checkbox" id="smtp_set_active" name="set_active" value="1" <?= $smtpIsActive ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                <label for="smtp_set_active" class="text-sm text-slate-700 cursor-pointer">Set SMTP as the active provider</label>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                <button type="button" hx-post="/settings/credentials/test" hx-vals='{"provider": "smtp"}' hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}' hx-target="#test-result-smtp" hx-swap="innerHTML" hx-indicator="#test-smtp-indicator"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition">
                    <span id="test-smtp-indicator" class="htmx-indicator inline-flex items-center"><svg class="animate-spin h-3.5 w-3.5 text-slate-500 mr-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg></span>
                    <i class="bi bi-wifi"></i> Test Connection
                </button>

                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition">
                    <span id="smtp-save-indicator" class="htmx-indicator"><svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle></svg></span>
                    <i class="bi bi-floppy"></i> Save
                </button>
            </div>
            <div id="test-result-smtp" class="min-h-[2rem]"></div>
        </form>
    </section>
</div>

<script>
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (!input || !icon) return;
    if (input.type === 'password') { input.type = 'text'; icon.className = 'bi bi-eye-slash text-sm'; } 
    else { input.type = 'password'; icon.className = 'bi bi-eye text-sm'; }
}
</script>