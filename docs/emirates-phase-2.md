# Emirates — Phase 2: Authentication

**Version:** 1.0
**Phase:** 2 of 14
**Depends On:** Phase 0 complete (framework running, session, helpers, middleware stubs) + Phase 1 complete (User model, UserSeeder, repositories)
**Goal:** The platform has a working login/logout flow with session management and full route protection. No page is accessible without an active session, and the login page is inaccessible to already-authenticated users.

---

## How to Use This Document

Work through every section in order. Each section builds on the previous one. All file paths are relative to your project root `emirates/`.

Before starting, confirm these Phase 0 and Phase 1 outputs exist:
- `app/Core/Session.php` — session management
- `app/Core/Request.php` — input helpers including `post()` and `header()`
- `app/Core/Response.php` — `html()`, `redirect()`, `back()`
- `app/Controllers/BaseController.php` — `view()`, `validate()`, `withErrors()`, `redirect()`
- `app/Middlewares/AuthMiddleware.php` — redirects unauthenticated users to `/login`
- `app/Middlewares/GuestMiddleware.php` — redirects authenticated users to `/compose`
- `app/Middlewares/CsrfMiddleware.php` — validates CSRF token on POST/PUT/DELETE
- `app/Models/User.php` — `findBy(string $column, mixed $value): ?static`
- `database/seeders/UserSeeder.php` — seeds the admin user from `.env`
- `bootstrap/helpers.php` — `session()`, `view()`, `csrf_token()`, `csrf_field()`, `old()`, `e()`
- `routes/web.php` — already has `GET /login`, `POST /login`, `POST /logout` stubs

**Checklist notation:**
- `[ ]` Not started
- `[x]` Complete

---

## 2.1 — Auth Service

The `AuthService` encapsulates all authentication logic so the controller stays thin and the logic is reusable and testable in isolation.

- [ ] **2.1.1** Create `app/Services/AuthService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * AuthService
 *
 * Handles all authentication operations:
 *   - Verifying credentials against the database
 *   - Writing and reading the user session
 *   - Logging out
 *
 * The service never touches HTTP — no redirects, no response building.
 * That belongs in the controller. The service just returns true/false or data.
 *
 * Usage:
 *   $auth = new AuthService();
 *   $ok   = $auth->attempt('user@example.com', 'secret');
 *   $user = $auth->user();
 *   $auth->logout();
 */
class AuthService
{
    // ─── Authentication ───────────────────────────────────────────────────────

    /**
     * Attempt to authenticate with the given credentials.
     *
     * Steps:
     *   1. Look up the user by email.
     *   2. Verify the submitted password against the stored bcrypt hash.
     *   3. On success: regenerate the session ID (prevents session fixation),
     *      then write the user's id and name into the session.
     *   4. Return true on success, false on any failure.
     *
     * We regenerate the session ID on login so that a session ID obtained
     * before login (e.g. by an attacker who set a fixed session cookie)
     * cannot be used after login.
     */
    public function attempt(string $email, string $password): bool
    {
        // 1. Find the user — returns null if no matching email
        $user = User::findBy('email', trim($email));

        if ($user === null) {
            // No user found. Return false without leaking whether the email
            // exists — the same generic "Invalid credentials" message will show.
            return false;
        }

        // 2. Verify the password against the bcrypt hash stored in the database.
        //    password_verify() is timing-safe and handles bcrypt automatically.
        if (!password_verify($password, $user->password_hash)) {
            return false;
        }

        // 3. Regenerate the session ID to prevent session fixation attacks.
        //    'true' means the old session file is deleted immediately.
        session()->regenerate();

        // 4. Store the authenticated user's data in the session.
        session()->set('user_id',   (int) $user->id);
        session()->set('user_name', (string) $user->name);
        session()->set('user_email', (string) $user->email);

        return true;
    }

    // ─── Status checks ────────────────────────────────────────────────────────

    /**
     * Check whether the current request has an authenticated session.
     *
     * Used by AuthMiddleware and anywhere you need to know if a user is logged in.
     */
    public function check(): bool
    {
        return session()->has('user_id');
    }

    /**
     * Retrieve the authenticated user's full record from the database.
     *
     * Returns null if no user is in the session or the user ID is not found.
     * This performs a DB query, so use it sparingly — prefer session data
     * for displaying the user's name.
     */
    public function user(): ?User
    {
        $userId = session()->get('user_id');

        if ($userId === null) {
            return null;
        }

        return User::find((int) $userId);
    }

    /**
     * Get the authenticated user's ID from the session.
     * Returns null if no user is logged in.
     */
    public function id(): ?int
    {
        $id = session()->get('user_id');
        return $id !== null ? (int) $id : null;
    }

    /**
     * Get the authenticated user's name from the session.
     * Returns an empty string if not available.
     */
    public function name(): string
    {
        return (string) session()->get('user_name', '');
    }

    // ─── Logout ───────────────────────────────────────────────────────────────

    /**
     * Log the current user out.
     *
     * Destroys the session completely (deletes session data and the cookie).
     * After this, the user is unauthenticated.
     */
    public function logout(): void
    {
        session()->destroy();
    }
}
```

---

## 2.2 — Auth Controller

The `AuthController` handles the two HTTP flows: showing the login form and processing the login submission.

- [ ] **2.2.1** Create `app/Controllers/AuthController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;

/**
 * AuthController
 *
 * Handles:
 *   GET  /login   — show the login form
 *   POST /login   — process credentials
 *   POST /logout  — destroy the session
 *
 * Routes are defined in routes/web.php.
 * The GuestMiddleware on GET /login and POST /login ensures authenticated
 * users are redirected to /compose before this controller runs.
 * The AuthMiddleware on POST /logout ensures only authenticated users can log out.
 */
class AuthController extends BaseController
{
    private AuthService $auth;

    public function __construct()
    {
        // Instantiate the auth service once for all methods in this controller.
        $this->auth = new AuthService();
    }

    // ─── GET /login ───────────────────────────────────────────────────────────

    /**
     * Show the login form.
     *
     * The GuestMiddleware redirects authenticated users to /compose before
     * this method runs, so we don't need to check that here.
     */
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', [
            // Pass the page title for use in the <title> tag
            'pageTitle' => 'Sign In — Emirates',
        ]);
    }

    // ─── POST /login ──────────────────────────────────────────────────────────

    /**
     * Process the login form submission.
     *
     * Validation is intentionally minimal for a single-user system:
     * we just need a valid email format and a non-empty password.
     * The "Invalid credentials" message is deliberately generic — we
     * never tell the user whether the email or password was wrong.
     */
    public function login(Request $request): Response
    {
        // 1. Validate the submitted fields.
        //    $this->validate() throws ValidationException on failure,
        //    which BaseController::withErrors() catches and handles.
        try {
            $data = $this->validate($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
            ]);
        } catch (\App\Exceptions\ValidationException $e) {
            return $this->withErrors($e);
        }

        // 2. Attempt to authenticate.
        $success = $this->auth->attempt($data['email'], $data['password']);

        if (!$success) {
            // Flash a generic error message. We also re-flash the email value
            // so the input field repopulates on the next page load (better UX).
            session()->flash('errors', ['email' => 'Invalid email or password. Please try again.']);
            session()->flash('old', ['email' => $data['email']]);

            // Redirect back to the login form.
            return $this->back();
        }

        // 3. Authentication succeeded — send the user to the compose page.
        //    This is the app's main entry point after login.
        return $this->redirect('/compose');
    }

    // ─── POST /logout ─────────────────────────────────────────────────────────

    /**
     * Log the user out and redirect to the login page.
     *
     * This is a POST endpoint (not GET) to protect against logout CSRF attacks
     * where a malicious link on another site could log the user out.
     * The CsrfMiddleware validates the CSRF token before this runs.
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout();

        // Redirect to login with a flash message.
        // We flash directly into the new session (which starts fresh after destroy).
        session()->flash('success', 'You have been signed out successfully.');

        return $this->redirect('/login');
    }
}
```

---

## 2.3 — Auth Views

### 2.3.1 — Auth Layout

The auth layout is a minimal shell used only for the login page. It has no sidebar, no nav, no session dependencies — just enough to render the login card cleanly.

- [ ] **2.3.1** Create `resources/layouts/auth.php`:

```php
<?php
/**
 * resources/layouts/auth.php
 *
 * Minimal layout for unauthenticated pages (login only in MVP).
 * No sidebar, no top nav, no session checks.
 *
 * Variables available:
 *   $content   string  — The rendered view content (injected by view())
 *   $pageTitle string  — The <title> tag content
 */
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= e($pageTitle ?? 'Emirates') ?></title>

    {{-- Tailwind CSS (CDN — replace with compiled asset in production for performance) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /*
         * Custom properties for the Emirates brand.
         * These are overridden at runtime by the user's saved settings,
         * but we need sensible defaults for the login page where settings
         * aren't yet loaded.
         */
        :root {
            --color-primary:   #1d4ed8; /* Default blue — Emirates brand primary */
            --color-secondary: #0f172a; /* Default dark navy */
        }

        /* Prevent FOUC (flash of unstyled content) before Tailwind loads */
        body { opacity: 0; transition: opacity 0.15s ease; }
        body.ready { opacity: 1; }
    </style>
</head>
<body class="h-full bg-slate-50 flex items-center justify-center px-4 py-12">

    {{-- Centered card wrapper --}}
    <div class="w-full max-w-sm">
        <?= $content ?>
    </div>

    <script>
        // Reveal body after styles load to prevent flash of unstyled content
        document.body.classList.add('ready');
    </script>
</body>
</html>
```

> **Note on template comments:** The `{{-- ... --}}` style comments above are illustrative only. In actual PHP view files, use standard `<!-- ... -->` HTML comments or `<?php /* ... */ ?>` PHP comments. Replace them before saving.

---

### 2.3.2 — Login View

The login view is the only page a non-authenticated user can see. It is clean, focused, and intentionally has no registration or password reset links.

- [ ] **2.3.2** Create `resources/auth/login.php`:

```php
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

{{-- Emirates Logo / Brand --}}
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

{{-- Success message (e.g. after logout) --}}
<?php if ($success): ?>
<div class="mb-4 flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
    <i class="bi bi-check-circle-fill flex-shrink-0"></i>
    <span><?= e($success) ?></span>
</div>
<?php endif; ?>

{{-- Login Card --}}
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-6 py-8">

    {{-- Error Alert --}}
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

    {{-- Form --}}
    <form
        method="POST"
        action="/login"
        novalidate
        class="space-y-5"
    >
        <?= csrf_field() ?>

        {{-- Email Field --}}
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

        {{-- Password Field --}}
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
                {{--
                    Password reveal toggle button.
                    The JS at the bottom of this file toggles type="password" / type="text".
                --}}
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

        {{-- Submit Button --}}
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

{{-- Footer note --}}
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
```

> **Key points about the login view:**
> - Uses `e()` to HTML-escape all output — never echo user input raw.
> - `old('email')` re-populates the email field after a failed attempt so the user doesn't have to retype it.
> - The error alert uses `role="alert"` and `aria-live="assertive"` so screen readers announce it.
> - The password toggle is implemented without any external library.
> - The submit button is disabled on submit to prevent accidental double-posting.
> - There is no "Forgot password?" or "Register" link — this is a single-user system.
> - The site logo and name are read from `setting()` — they default to the Emirates brand until the user configures their own.

---

## 2.4 — Routes Confirmation

The routes for Phase 2 should already be declared in `routes/web.php` from Phase 0. Verify they are correct:

- [ ] **2.4.1** Open `routes/web.php` and confirm the following routes exist exactly as shown:

```php
<?php
// routes/web.php

use App\Controllers\AuthController;

// ── Guest-only routes (redirect to /compose if already logged in) ──────────
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/login',  [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// ── Auth-required routes ───────────────────────────────────────────────────
$router->group(['middleware' => ['auth', 'csrf']], function ($router) {
    $router->post('/logout', [AuthController::class, 'logout']);

    // ── These routes are stubs until Phase 3 ──────────────────────────────
    // $router->get('/compose',             [ComposeController::class, 'index']);
    // $router->get('/recipients',          [RecipientController::class, 'index']);
    // ... etc.
});

// ── Root redirect ──────────────────────────────────────────────────────────
// Visiting / should redirect authenticated users to /compose,
// guests to /login. The AuthMiddleware handles this if /compose is guarded.
$router->get('/', function () {
    return \App\Core\Response::redirect('/compose');
});
```

> **Note on the CSRF middleware:** The `csrf` middleware in the group above applies to **all POST requests inside the group** including `/logout`. The logout form must include `<?= csrf_field() ?>`. The guest group (`/login`) does NOT include `csrf` in the middleware list — that would block the login form itself before the CSRF token is ever set. The CsrfMiddleware skips GET requests, so the `showLogin` route is safe regardless.

---

## 2.5 — Logout Form in Views

The logout button will live in the sidebar (built in Phase 3), but we need to understand the correct pattern now:

- [ ] **2.5.1** Understand the logout HTML pattern — the logout button is a small POST form, not a link:

```php
{{-- Correct: POST form with CSRF token --}}
<form method="POST" action="/logout" class="inline">
    <?= csrf_field() ?>
    <button
        type="submit"
        class="flex items-center gap-2 text-slate-500 hover:text-slate-700 transition text-sm"
    >
        <i class="bi bi-box-arrow-left"></i>
        Sign Out
    </button>
</form>
```

> **Why a form, not a link?** An `<a href="/logout">` would expose logout to CSRF attacks — any website could embed an `<img src="/logout">` and log the user out without their knowledge. POST + CSRF token is the correct pattern.

---

## 2.6 — Helper Function Review

Phase 2 depends on these global helpers from `bootstrap/helpers.php`. Confirm they are implemented correctly before testing:

- [ ] **2.6.1** Verify `old(string $key, string $default = ''): string`:

```php
/**
 * Get a previously submitted input value from the session flash.
 * Used to repopulate form fields after a failed form submission.
 */
function old(string $key, string $default = ''): string
{
    $old = session()->getFlash('old');
    return isset($old[$key]) ? e($old[$key]) : $default;
}
```

- [ ] **2.6.2** Verify `errors(string $key = null): mixed`:

```php
/**
 * Get validation/auth error messages from the session flash.
 *
 * If $key is provided, returns the error string for that field or null.
 * If $key is null, returns the entire errors array.
 */
function errors(?string $key = null): mixed
{
    $errors = session()->getFlash('errors') ?? [];

    if ($key === null) {
        return $errors;
    }

    return $errors[$key] ?? null;
}
```

- [ ] **2.6.3** Verify `flash(string $key, mixed $default = null): mixed`:

```php
/**
 * Get a flash message from the session (one-time read).
 * Unlike errors(), this is for general flash messages like 'success'.
 */
function flash(string $key, mixed $default = null): mixed
{
    return session()->getFlash($key, $default);
}
```

- [ ] **2.6.4** Verify `csrf_token(): string`:

```php
/**
 * Get the current CSRF token string.
 * Used in JavaScript (e.g. for HTMX header injection).
 */
function csrf_token(): string
{
    return session()->csrfToken();
}
```

- [ ] **2.6.5** Verify `csrf_field(): string`:

```php
/**
 * Render a hidden CSRF input field for use in HTML forms.
 * Always call this inside any form that posts data.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . csrf_token() . '">';
}
```

- [ ] **2.6.6** Verify `setting(string $key, mixed $default = null): mixed`:

```php
/**
 * Get a saved application setting from the settings table.
 * The SettingRepository caches results so this is safe to call repeatedly.
 */
function setting(string $key, mixed $default = null): mixed
{
    static $repo = null;

    if ($repo === null) {
        $repo = new \App\Repositories\SettingRepository();
    }

    return $repo->get($key, $default);
}
```

> **Why static $repo?** `setting()` may be called many times per request (in every view that reads brand colors, names, etc.). The static variable means the `SettingRepository` is only instantiated once per request, and the repository itself caches the DB result, so the database is only queried once.

---

## 2.7 — BaseController::withErrors Review

The `withErrors()` method in `BaseController` is the central handler for form validation failures. Confirm it is implemented correctly — Phase 2 depends on it:

- [ ] **2.7.1** Open `app/Controllers/BaseController.php` and confirm `withErrors()` looks like this:

```php
/**
 * Handle a validation failure.
 *
 * Flashes the validation errors and the old input data into the session,
 * then redirects back to the form. On the next page load, the view
 * reads these flash values via errors() and old() helpers.
 *
 * Usage in a controller method:
 *   try {
 *       $data = $this->validate($request->all(), [...rules...]);
 *   } catch (ValidationException $e) {
 *       return $this->withErrors($e);
 *   }
 */
protected function withErrors(\App\Exceptions\ValidationException $e, ?array $oldInput = null): Response
{
    // Flash the errors array so errors() helper can read it on next request
    session()->flash('errors', $e->errors());

    // Flash the old input so old() helper can repopulate form fields
    // We omit sensitive fields like 'password' to avoid storing them in session
    $input = $oldInput ?? array_diff_key(
        request()->all(),
        array_flip(['password', 'password_confirmation', '_csrf', '_method'])
    );

    session()->flash('old', $input);

    return $this->back();
}
```

> **Note:** The `request()` global helper must be available in `bootstrap/helpers.php` and return the current `Request` instance. If it does not exist yet, add it:
>
> ```php
> function request(): \App\Core\Request
> {
>     return \App\Core\Request::capture();
> }
> ```

---

## 2.8 — Middleware Review

Phase 2 depends on all three middleware classes working correctly. Verify each one:

### AuthMiddleware

- [ ] **2.8.1** Open `app/Middlewares/AuthMiddleware.php` and confirm it matches this implementation:

```php
<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Interfaces\MiddlewareInterface;

/**
 * AuthMiddleware
 *
 * Protects routes that require authentication.
 * If the user is not logged in:
 *   - For regular requests: redirect to /login
 *   - For HTMX requests: set HX-Redirect header (so HTMX does a client-side redirect)
 *
 * Attach this to any route group that requires login:
 *   $router->group(['middleware' => ['auth']], function ($r) { ... });
 */
class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // Check if the user has an active session
        if (!session()->has('user_id')) {
            if ($request->isHtmx()) {
                // For HTMX requests, we can't do a standard redirect.
                // Instead, we send an HX-Redirect header telling HTMX
                // to navigate the browser to /login.
                return Response::html('', 401)
                    ->withHeader('HX-Redirect', '/login');
            }

            // For standard HTTP requests, a normal redirect works fine.
            return Response::redirect('/login');
        }

        // User is authenticated — pass the request to the next middleware or controller.
        return $next($request);
    }
}
```

### GuestMiddleware

- [ ] **2.8.2** Open `app/Middlewares/GuestMiddleware.php` and confirm it matches this implementation:

```php
<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Interfaces\MiddlewareInterface;

/**
 * GuestMiddleware
 *
 * Protects routes that should only be accessible to guests (unauthenticated users).
 * If the user IS logged in, redirect them away from the login page.
 *
 * Attach this to /login:
 *   $router->group(['middleware' => ['guest']], function ($r) {
 *       $r->get('/login',  [AuthController::class, 'showLogin']);
 *       $r->post('/login', [AuthController::class, 'login']);
 *   });
 */
class GuestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        // If the user is already logged in, redirect to the app's main page.
        if (session()->has('user_id')) {
            return Response::redirect('/compose');
        }

        // User is a guest — allow them through.
        return $next($request);
    }
}
```

### CsrfMiddleware

- [ ] **2.8.3** Open `app/Middlewares/CsrfMiddleware.php` and confirm it matches this implementation:

```php
<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;
use App\Exceptions\AppException;
use App\Interfaces\MiddlewareInterface;

/**
 * CsrfMiddleware
 *
 * Validates the CSRF token on all state-changing requests (POST, PUT, PATCH, DELETE).
 * GET and HEAD requests are always safe (no state changes, so no CSRF risk).
 *
 * The token is looked for in two places (in order):
 *   1. POST body field:  $_POST['_csrf']
 *   2. Request header:   X-CSRF-Token  (used by HTMX via app.js header injection)
 *
 * On mismatch, throws an AppException with HTTP 419.
 */
class CsrfMiddleware implements MiddlewareInterface
{
    // Methods that change state and therefore need CSRF protection
    private const STATE_CHANGING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next): Response
    {
        // Safe methods (GET, HEAD, OPTIONS) never need CSRF protection.
        if (!in_array($request->method(), self::STATE_CHANGING_METHODS, true)) {
            return $next($request);
        }

        // Read the token from POST body first, then fall back to the header.
        // HTML forms use $_POST['_csrf'], HTMX requests use the X-CSRF-Token header.
        $submittedToken = $request->post('_csrf')
            ?? $request->header('X-CSRF-Token')
            ?? '';

        $sessionToken = session()->csrfToken();

        // hash_equals() is a timing-safe comparison that prevents timing attacks.
        // Do NOT use === for this comparison.
        if (!hash_equals($sessionToken, $submittedToken)) {
            throw new AppException(
                'CSRF token mismatch. Your session may have expired — please refresh and try again.',
                419
            );
        }

        return $next($request);
    }
}
```

---

## 2.9 — Milestone: Authentication End-to-End Test

Work through these checks **in order**. Each test builds on the previous one. Do not skip ahead.

### Setup

- [ ] **2.9.1** Ensure the dev server is running:
  ```bash
  php -S localhost:8000 -t public/
  ```

- [ ] **2.9.2** Ensure a seeded user exists in the database. If you have not run the seeder yet:
  ```bash
  php database/migrate.php --seed
  ```
  Then confirm the user exists:
  ```sql
  SELECT id, name, email FROM users;
  ```
  You should see one row with the email from your `.env` `ADMIN_EMAIL`.

---

### Test 1: Login Page Renders

- [ ] **2.9.3** Open `http://localhost:8000/login` in a browser.
  - **Expected:** The login card renders. You see the Emirates name/logo, email field, password field, and "Sign In" button.
  - **Failure:** If you see a PHP error, check that `AuthController`, `AuthService`, the layout, and the view are all in place and that the `guest` middleware is registered in the router.

---

### Test 2: Wrong Credentials

- [ ] **2.9.4** Submit the login form with a valid email format but wrong password (e.g. `wrong@example.com` / `wrongpass`).
  - **Expected:** The page reloads. The red error alert appears: "Invalid email or password. Please try again."
  - **Expected:** The email field is repopulated with what you typed.
  - **Expected:** The password field is empty (never repopulated — security).
  - **Failure:** If no error appears, check that `session()->flash()` is called in `AuthController::login()` and that `errors()` helper reads from `getFlash('errors')` correctly.

---

### Test 3: Empty Fields

- [ ] **2.9.5** Clear both fields and submit an empty form.
  - **Expected:** The page reloads with a validation error. The error message indicates that email and/or password is required.
  - **Failure:** If the form submits with no validation, check that `$this->validate()` in `BaseController` is called and throws `ValidationException`, and that `withErrors()` flashes the errors correctly.

---

### Test 4: Invalid Email Format

- [ ] **2.9.6** Enter `notanemail` in the email field and any text in the password field, then submit.
  - **Expected:** Validation error appears — the email must be a valid format.
  - **Note:** This catches the case before the DB is even queried.

---

### Test 5: Correct Credentials

- [ ] **2.9.7** Submit the login form with the credentials from your `.env` `ADMIN_EMAIL` and `ADMIN_PASSWORD`.
  - **Expected:** You are redirected to `/compose`.
  - **Expected at `/compose`:** A 404 page (the compose controller does not exist yet). This is correct — Phase 3 builds it.
  - **Failure:** If you are not redirected, check that `AuthService::attempt()` calls `password_verify()` against `$user->password_hash` and that the seeder stored the password with `password_hash($pass, PASSWORD_BCRYPT)`.

---

### Test 6: Guest Middleware (Redirect Away from Login)

- [ ] **2.9.8** While still logged in (after the successful login in Test 5), navigate to `http://localhost:8000/login`.
  - **Expected:** You are immediately redirected to `/compose` (the 404 page). You never see the login form.
  - **Failure:** If the login form appears, the `GuestMiddleware` is not running. Check that the `/login` routes are inside a `guest` middleware group in `routes/web.php`.

---

### Test 7: Auth Middleware (Redirect Unauthenticated Users)

- [ ] **2.9.9** Open a private/incognito browser window (or clear cookies) and visit `http://localhost:8000/recipients`.
  - **Expected:** You are redirected to `/login`. The login form appears.
  - **Failure:** If you see a 404 or error instead of a redirect, check that `AuthMiddleware` is applied to the `/recipients` route (even as a stub group).

> **Note:** If `/recipients` is not yet in `routes/web.php` as an auth-protected stub, add it temporarily:
> ```php
> $router->group(['middleware' => ['auth']], function ($router) {
>     $router->get('/recipients', function () {
>         return \App\Core\Response::html('<h1>Recipients placeholder</h1>');
>     });
> });
> ```
> This is just for testing — Phase 3 will replace it with the real controller.

---

### Test 8: Logout

- [ ] **2.9.10** Log in with correct credentials. Then click the Sign Out button (or POST to `/logout` manually via curl if the button doesn't exist yet).

  To test via curl:
  ```bash
  # First get a CSRF token (requires a logged-in session cookie — easier to test via browser)
  ```
  
  Or add a temporary logout link in a test view:
  ```html
  <form method="POST" action="/logout">
      <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
      <button type="submit">Logout</button>
  </form>
  ```

  - **Expected:** After logout, you are redirected to `/login`.
  - **Expected:** A green success message "You have been signed out successfully." appears.
  - **Expected:** Visiting any protected route immediately redirects back to `/login`.
  - **Failure:** If the session persists after logout, check that `session()->destroy()` is calling PHP's `session_destroy()` and clearing the session cookie.

---

### Test 9: Log File Check

- [ ] **2.9.11** After running the above tests, check the log file:
  ```bash
  cat storage/logs/app-$(date +%Y-%m-%d).log
  ```
  - **Expected:** You see log entries for the authentication attempts (if any were added to AuthService — if not, no log entries is also fine for Phase 2).
  - **Failure:** If the log file doesn't exist at all, check that `storage/logs/` directory is writable and that the Logger is instantiated in `bootstrap/app.php`.

---

### Test 10: Commit

- [ ] **2.9.12** All tests pass. Commit Phase 2:
  ```bash
  git add -A
  git commit -m "Phase 2: Authentication — AuthService, AuthController, login/logout flow, CSRF, middleware"
  ```

---

## Phase 2 Complete ✅

**What you have built:**

| Component | Files |
|---|---|
| Auth Service | `app/Services/AuthService.php` — credentials check, session write, logout |
| Auth Controller | `app/Controllers/AuthController.php` — showLogin, login, logout |
| Auth Layout | `resources/layouts/auth.php` — minimal HTML shell for login page |
| Login View | `resources/auth/login.php` — branded form with error display, old input, password toggle |
| Helpers confirmed | `old()`, `errors()`, `flash()`, `csrf_token()`, `csrf_field()`, `setting()` |
| Middleware confirmed | `AuthMiddleware`, `GuestMiddleware`, `CsrfMiddleware` |
| Routes confirmed | `GET /login`, `POST /login`, `POST /logout` with correct middleware |

**Authentication flow:**
```
Guest visits /protected-page
  → AuthMiddleware detects no session
  → Redirect to /login
  → Login form renders (GuestMiddleware allows access)
  → User submits correct credentials
  → AuthService::attempt() verifies password, writes session
  → Redirect to /compose
  → GuestMiddleware now blocks /login (redirects back to /compose)
  → User clicks Sign Out → POST /logout
  → AuthService::logout() destroys session
  → Redirect to /login with success message
```

**Security properties implemented:**
- Passwords verified with `password_verify()` (bcrypt, timing-safe)
- Session ID regenerated on login (`session_regenerate_id(true)`) — prevents session fixation
- Session completely destroyed on logout — no residual data
- CSRF token required on all POST/PUT/PATCH/DELETE requests
- Logout is a POST endpoint, not a GET link — prevents logout CSRF
- No registration, no password reset endpoints — attack surface is minimal
- Error messages never reveal whether the email exists or the password was wrong
- `hash_equals()` used for CSRF token comparison — prevents timing attacks
- `old()` never repopulates password fields — sensitive input never stored in session

**Ready for Phase 3:** Application shell, sidebar, mobile nav, toast system, and stub controllers.

---

*End of Emirates Phase 2 Implementation*
```
