<?php
/**
 * resources/auth/login.php
 *
 * The login form. Rendered inside resources/layouts/auth.php.
 *
 * Flash data available (via helpers):
 *   errors('email')   — validation or authentication error message
 *   old('email')      — repopulates email field after a failed attempt
 */

// Read error and old input from the flash session
$emailError = errors('email');      // e.g. "Invalid email or password."
$oldEmail   = old('email', '');     // repopulate from previous attempt
$success    = flash('success');     // e.g. "You have been signed out."
?>

<!-- Emirates Logo / Brand -->
<div class="text-center mb-8">
    <?php
    // If a site logo has been saved in settings, show it.
    // Otherwise fall back to the text brand name.
    $siteLogo = setting('site_logo_path');
    if ($siteLogo):
    ?>
        <img
            src="<?= e(url('/storage/logos/site/' . basename($siteLogo))) ?>"
            alt="<?= e(setting('site_name', 'Emirates')) ?>"
            class="h-12 mx-auto mb-3 object-contain"
        >
    <?php else: ?>
        <div class="inline-flex items-center gap-2 mb-3">
            <div class="w-10 h-10 rounded-xl bg-blue-700 flex items-center justify-center">
                <i class="bi bi-send-fill text-white text-lg"></i>
            </div>
            <span class="text-2xl font-bold text-slate-900 tracking-tight">
                <?= e(setting('site_name', 'Emirates')) ?>
            </span>
        </div>
    <?php endif; ?>
    <p class="text-sm text-slate-500">Sign in to your account</p>
</div>

<!-- Success message (e.g. after logout) -->
<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    <span><?= e($success) ?></span>
</div>
<?php endif; ?>

<!-- Login Card -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-6 py-8">

    <!-- Error Alert -->
    <?php if ($emailError): ?>
    <div
        class="mb-5 flex items-start gap-3 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700"
        role="alert"
        aria-live="assertive"
    >
        <i class="bi bi-exclamation-circle-fill flex-shrink-0 mt-0.5"></i>
        <span><?= e($emailError) ?></span>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form
        method="POST"
        action="/login"
        novalidate
        class="space-y-5"
    >
        <?= csrf_field() ?>

        <!-- Email Field -->
        <div>
            <label
                for="email"
                class="block text-sm font-medium text-slate-700 mb-1.5"
            >
                Email address
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($oldEmail) ?>"
                autocomplete="email"
                autofocus
                required
                placeholder="you@company.com"
                class="
                    block w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-900
                    placeholder:text-slate-400 outline-none transition
                    <?= $emailError
                        ? 'border-red-400 bg-red-50 focus:border-red-500 focus:ring-2 focus:ring-red-200'
                        : 'border-slate-300 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100'
                    ?>
                "
            >
        </div>

        <!-- Password Field -->
        <div>
            <label
                for="password"
                class="block text-sm font-medium text-slate-700 mb-1.5"
            >
                Password
            </label>
            <div class="relative">
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                    placeholder="••••••••"
                    class="
                        block w-full rounded-lg border px-3.5 py-2.5 pr-11 text-sm text-slate-900
                        placeholder:text-slate-400 outline-none transition
                        border-slate-300 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100
                    "
                >
                <!--
                    Password reveal toggle button.
                    The JS at the bottom of this file toggles type="password" / type="text".
                -->
                <button
                    type="button"
                    id="toggle-password"
                    aria-label="Toggle password visibility"
                    class="
                        absolute inset-y-0 right-0 flex items-center px-3
                        text-slate-400 hover:text-slate-600 transition
                    "
                >
                    <i class="bi bi-eye text-base" id="toggle-password-icon"></i>
                </button>
            </div>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            class="
                w-full rounded-lg bg-blue-700 px-4 py-2.5 text-sm font-semibold text-white
                hover:bg-blue-800 active:bg-blue-900 transition
                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                disabled:opacity-60 disabled:cursor-not-allowed
            "
        >
            <span class="flex items-center justify-center gap-2">
                <i class="bi bi-box-arrow-in-right"></i>
                Sign In
            </span>
        </button>
    </form>
</div>

<!-- Footer note -->
<p class="mt-6 text-center text-xs text-slate-400">
    Emirates Email Platform &copy; <?= date('Y') ?>
</p>

<script>
    /**
     * Password visibility toggle.
     * Switches the password input between type="password" and type="text".
     */
    (function () {
        const toggleBtn  = document.getElementById('toggle-password');
        const toggleIcon = document.getElementById('toggle-password-icon');
        const pwInput    = document.getElementById('password');

        if (!toggleBtn || !pwInput) return;

        let visible = false;

        toggleBtn.addEventListener('click', function () {
            visible = !visible;
            pwInput.type = visible ? 'text' : 'password';
            toggleIcon.className = visible
                ? 'bi bi-eye-slash text-base'
                : 'bi bi-eye text-base';
        });
    })();

    /**
     * Disable the submit button while the form is submitting.
     * Prevents double-submits on slow connections.
     */
    (function () {
        const form   = document.querySelector('form');
        const submit = form ? form.querySelector('[type="submit"]') : null;

        if (!form || !submit) return;

        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.innerHTML = '<span class="flex items-center justify-center gap-2"><i class="bi bi-arrow-repeat animate-spin"></i>Signing in…</span>';
        });
    })();
</script>
