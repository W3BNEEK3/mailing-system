# Emirates — Phase 3: Application Shell & Navigation

**Version:** 1.0
**Phase:** 3 of 14
**Depends On:** Phase 0 complete (framework, helpers, middleware, base controller) + Phase 1 complete (models, repositories, DTOs) + Phase 2 complete (AuthService, AuthController, login/logout flow)
**Goal:** The authenticated layout, sidebar, mobile bottom nav, toast system, modal system, and all global UI components are in place. Every page can be visited and renders the shell correctly. Stub controllers and placeholder views mean no nav link produces a 500 error.

---

## How to Use This Document

Work through every section in order. Each section builds on the previous one. All file paths are relative to your project root `emirates/`.

Before starting, confirm these Phase 0–2 outputs exist and work:
- `app/Core/App.php` — service container with `run()`, `make()`, `singleton()`
- `app/Core/Session.php` — `csrfToken()`, `flash()`, `getFlash()`
- `app/Core/Request.php` — `isHtmx()`, `uri()`, `method()`
- `app/Core/Response.php` — `html()`, `redirect()`, `htmxTrigger()`, `htmxRedirect()`
- `app/Core/ErrorHandler.php` — maps exception types to HTTP status codes
- `app/Controllers/BaseController.php` — `view()`, `json()`, `redirect()`, `back()`
- `app/Middlewares/AuthMiddleware.php` — running and redirecting unauthenticated users
- `bootstrap/helpers.php` — `e()`, `view()`, `config()`, `session()`, `csrf_token()`, `csrf_field()`, `setting()`, `url()`, `asset()`
- `routes/web.php` — `GET /login`, `POST /login`, `POST /logout` working
- Phase 2 milestone: login, logout, and route protection all confirmed working

**Checklist notation:**
- `[ ]` Not started
- `[x]` Complete

---

## 3.1 — App Layout

The app layout is the authenticated shell every protected page renders inside. It contains the HTML document structure, the sidebar (desktop), the bottom nav (mobile), the toast container, and the global HTMX progress indicator. Views rendered by controllers inject their HTML into the `$content` slot.

### How the layout system works

The `view()` helper in `bootstrap/helpers.php` accepts a second argument for a layout:

```
view('compose/index', ['pageTitle' => 'Compose'])
```

The helper resolves `resources/compose/index.php`, renders it into `$content`, then wraps it with `resources/layouts/app.php`. This is the same pattern used by `resources/layouts/auth.php` in Phase 2.

If your `view()` helper does not yet support a `$layout` parameter, update it now (see 3.1.1 below).

---

- [ ] **3.1.1** Update the `view()` helper in `bootstrap/helpers.php` to support layouts:

```php
/**
 * Render a view file and optionally wrap it in a layout.
 *
 * Usage (no layout — returns the view HTML directly):
 *   $html = view('auth/login', ['pageTitle' => 'Sign In']);
 *
 * Usage (with layout — the view HTML is injected as $content):
 *   $html = view('compose/index', ['pageTitle' => 'Compose'], 'app');
 *
 * @param string      $path    Relative to resources/ — e.g. 'compose/index'
 * @param array       $data    Variables extracted into the view scope
 * @param string|null $layout  Layout name in resources/layouts/ (without .php)
 */
function view(string $path, array $data = [], ?string $layout = null): string
{
    // Resolve the view file path
    $file = BASE_PATH . '/resources/' . ltrim($path, '/') . '.php';

    if (!file_exists($file)) {
        throw new \RuntimeException("View not found: {$file}");
    }

    // Extract variables so they are available inside the view file as local variables.
    // EXTR_SKIP prevents user data from overwriting existing local variables like $file.
    extract($data, EXTR_SKIP);

    // Capture the view output into a string
    ob_start();
    require $file;
    $content = ob_get_clean();

    // If no layout requested, return the raw view output
    if ($layout === null) {
        return $content;
    }

    // Resolve the layout file
    $layoutFile = BASE_PATH . '/resources/layouts/' . $layout . '.php';

    if (!file_exists($layoutFile)) {
        throw new \RuntimeException("Layout not found: {$layoutFile}");
    }

    // Re-extract data so the layout also has access to $pageTitle, etc.
    // $content is already set above — it's injected as the slot.
    extract($data, EXTR_SKIP);

    ob_start();
    require $layoutFile;
    return ob_get_clean();
}
```

> **Important:** If your current `view()` helper already supports the `$layout` parameter with identical behaviour, skip this task. If it uses a different signature, reconcile it now — subsequent sections all call `view('path', $data, 'app')`.

---

- [ ] **3.1.2** Update `app/Controllers/BaseController.php` — the `view()` method should default to the `'app'` layout for all authenticated controllers:

```php
/**
 * Render a view inside the authenticated app layout and return as a Response.
 *
 * Controllers call this for full-page responses. HTMX partial responses
 * should call $this->partial() instead (see below).
 *
 * @param string $template  Path relative to resources/ without .php extension
 * @param array  $data      Variables passed to the view
 * @param string $layout    Layout to wrap the view in (default: 'app')
 */
protected function view(string $template, array $data = [], string $layout = 'app'): Response
{
    $html = view($template, $data, $layout);
    return Response::html($html);
}

/**
 * Render a view with NO layout — for HTMX partial responses.
 *
 * When HTMX swaps only part of the page (e.g. a table, a form, a row),
 * we return only the HTML fragment, not the full document.
 *
 * Usage:
 *   return $this->partial('recipients/_table', ['recipients' => $list]);
 */
protected function partial(string $template, array $data = []): Response
{
    $html = view($template, $data, null);
    return Response::html($html);
}
```

---

- [ ] **3.1.3** Create `resources/layouts/app.php`:

```php
<?php
/**
 * resources/layouts/app.php
 *
 * The main authenticated application layout.
 * Every protected page renders inside this shell.
 *
 * Variables available:
 *   $content   string  — The rendered view HTML (injected by view() helper)
 *   $pageTitle string  — The browser <title> content
 *
 * Structure:
 *   <html>
 *     <head> … </head>
 *     <body>
 *       ┌─────────────────────────────────────────┐
 *       │ #sidebar  (desktop only, md:flex)        │
 *       ├─────────────────────────────────────────┤
 *       │ #main-content  (flex-1, scrollable)      │
 *       └─────────────────────────────────────────┘
 *       #toast-container  (fixed, top-right)
 *       #global-loader    (fixed top progress bar)
 *       #modal-overlay    (fixed backdrop for modals)
 *     </body>
 *   </html>
 */

// Read the active brand colours from settings so the UI itself reflects the brand.
// These are CSS custom properties injected into the <head>, not just email colours.
$primaryColor   = setting('primary_color',   '#1d4ed8');
$secondaryColor = setting('secondary_color', '#0f172a');
$siteName       = setting('site_name',       'Emirates');
$siteLogo       = setting('site_logo_path',  null);
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- CSRF token in meta tag — read by app.js and injected into every HTMX request -->
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <title><?= e($pageTitle ?? $siteName) ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- HTMX -->
    <script src="<?= asset('js/htmx.min.js') ?>" defer></script>

    <!-- App JS (toast, modals, CSRF injection, chip input) -->
    <script src="<?= asset('js/app.js') ?>" defer></script>

    <style>
        /*
         * CSS custom properties derived from the saved brand settings.
         * Using inline style here (not a static file) so the values are
         * always the live values from the database, not a build artifact.
         */
        :root {
            --color-primary:   <?= e($primaryColor) ?>;
            --color-secondary: <?= e($secondaryColor) ?>;
        }

        /*
         * HTMX global progress indicator.
         * The `htmx-request` class is added to the <body> during any HTMX request.
         * We use it to show the top-of-page progress bar.
         */
        #global-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: var(--color-primary);
            z-index: 9999;
            transition: width 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }

        /* Animate the loader when any HTMX request is in flight */
        body.htmx-request #global-loader {
            width: 70%;
            opacity: 1;
        }

        /*
         * Sidebar nav link active state.
         * The active class is applied by the Html::active() helper.
         */
        .nav-link.active {
            background-color: color-mix(in srgb, var(--color-primary) 12%, transparent);
            color: var(--color-primary);
        }

        .nav-link.active .nav-icon {
            color: var(--color-primary);
        }

        /*
         * Toast animation keyframes.
         * The JS toast system adds/removes these classes.
         */
        @keyframes toast-in {
            from { transform: translateX(100%); opacity: 0; }
            to   { transform: translateX(0);    opacity: 1; }
        }

        @keyframes toast-out {
            from { transform: translateX(0);    opacity: 1; }
            to   { transform: translateX(100%); opacity: 0; }
        }

        .toast-enter { animation: toast-in  0.25s ease forwards; }
        .toast-leave { animation: toast-out 0.2s  ease forwards; }

        /*
         * Modal transition.
         * The modal overlay and panel use these classes for open/close.
         */
        #modal-overlay {
            transition: opacity 0.2s ease;
        }

        #modal-overlay.hidden {
            pointer-events: none;
        }

        /* Keep mobile content above the fixed bottom nav */
        @media (max-width: 767px) {
            #main-content {
                padding-bottom: 5rem; /* 80px — height of the bottom nav bar */
            }
        }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-900 flex">

    <!-- ── GLOBAL PROGRESS BAR ──────────────────────────────────────────── -->
    <!--
        Visible during HTMX requests via the body.htmx-request CSS selector above.
        This gives users instant feedback that something is happening.
    -->
    <div id="global-loader" role="progressbar" aria-hidden="true"></div>

    <!-- ── SIDEBAR (desktop) ─────────────────────────────────────────────── -->
    <!--
        Hidden on mobile (hidden), shown as a flex column on md+ (md:flex).
        Width: 240px (w-60). Fixed position via the outer wrapper.
    -->
    <div class="hidden md:flex md:w-60 md:flex-col md:fixed md:inset-y-0 md:z-30">
        <?php require BASE_PATH . '/resources/components/navigation/_sidebar.php'; ?>
    </div>

    <!-- ── MAIN CONTENT AREA ─────────────────────────────────────────────── -->
    <!--
        On desktop: offset left by the sidebar width (md:ml-60).
        On mobile: full width, with bottom padding to clear the bottom nav.
    -->
    <div id="main-content" class="flex-1 md:ml-60 min-h-screen overflow-y-auto">
        <main class="p-4 md:p-6 lg:p-8 max-w-screen-xl mx-auto">
            <?= $content ?>
        </main>
    </div>

    <!-- ── BOTTOM NAV (mobile only) ──────────────────────────────────────── -->
    <!--
        Fixed to the bottom of the viewport.
        Hidden on md+ breakpoint (md:hidden).
    -->
    <div class="md:hidden">
        <?php require BASE_PATH . '/resources/components/navigation/_bottom-nav.php'; ?>
    </div>

    <!-- ── TOAST CONTAINER ───────────────────────────────────────────────── -->
    <!--
        Toasts are appended here by app.js when the showToast HTMX event fires.
        Position: fixed top-right on desktop, top-center on mobile.
    -->
    <div
        id="toast-container"
        aria-live="polite"
        aria-atomic="false"
        class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]
               max-sm:right-1/2 max-sm:translate-x-1/2"
    ></div>

    <!-- ── MODAL OVERLAY & PANEL ─────────────────────────────────────────── -->
    <!--
        Generic modal shell. Content is injected via HTMX into #modal-body.
        Open:  openModal()  — called from JS
        Close: closeModal() — called from JS, backdrop click, or Escape key
    -->
    <div
        id="modal-overlay"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
    >
        <div
            id="modal-panel"
            class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh]
                   flex flex-col overflow-hidden"
        >
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 id="modal-title" class="text-base font-semibold text-slate-900">
                    <!-- Title is set by openModal(id, title) -->
                </h2>
                <button
                    type="button"
                    onclick="closeModal()"
                    aria-label="Close modal"
                    class="flex items-center justify-center w-8 h-8 rounded-lg
                           text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                >
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>

            <!-- Modal Body — HTMX swaps content into here -->
            <div id="modal-body" class="overflow-y-auto p-6">
                <!-- Content is loaded here by HTMX or JS -->
            </div>
        </div>
    </div>

</body>
</html>
```

---

## 3.2 — Sidebar Component

The sidebar is the primary navigation on desktop. It contains the brand identity at the top, nav links in the middle, and the logout button at the bottom.

- [ ] **3.2.1** Create `resources/components/navigation/_sidebar.php`:

```php
<?php
/**
 * resources/components/navigation/_sidebar.php
 *
 * Desktop sidebar navigation.
 * Included directly by resources/layouts/app.php.
 *
 * Requires:
 *   - Html::active(string $path): string — returns 'active' if URI matches
 *   - setting(string $key): mixed         — reads from settings table
 *   - url(string $path): string           — builds full URL
 *   - e(string $value): string            — HTML-escapes a value
 *   - csrf_field(): string                — renders CSRF hidden input
 */

use App\Helpers\Html;

// Read the current request URI for active link detection
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

/**
 * Helper: returns 'nav-link active' if the given path matches the current URI,
 * otherwise returns 'nav-link'.
 *
 * We do a prefix match for sub-pages (e.g. /settings matches /settings/general).
 * Exact match is used for root paths like /compose.
 */
$navClass = function (string $path) use ($currentUri): string {
    $isActive = $path === '/'
        ? $currentUri === '/'
        : str_starts_with(strtok($currentUri, '?'), $path);

    return 'nav-link ' . ($isActive ? 'active' : '');
};

// Site logo/name from settings
$siteLogo = setting('site_logo_path', null);
$siteName = setting('site_name', 'Emirates');

// The authenticated user's display name from the session
$userName = session()->get('user_name', 'User');
?>

<nav class="flex flex-col h-full bg-white border-r border-slate-200">

    <!-- ── BRAND ─────────────────────────────────────────────────────────── -->
    <div class="flex items-center gap-3 h-16 px-5 border-b border-slate-100 flex-shrink-0">
        <?php if ($siteLogo): ?>
            <img
                src="<?= e(url('/storage/logos/site/' . basename((string)$siteLogo))) ?>"
                alt="<?= e($siteName) ?>"
                class="h-8 w-auto object-contain"
            >
        <?php else: ?>
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background-color: var(--color-primary);">
                <i class="bi bi-send-fill text-white text-sm"></i>
            </div>
            <span class="font-bold text-slate-900 text-sm tracking-tight truncate">
                <?= e($siteName) ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- ── NAV LINKS ─────────────────────────────────────────────────────── -->
    <!--
        Each link is a full-width flex row: icon + label.
        The active class applies a tinted background and primary-colour text.
        Non-active links are muted slate with a hover state.
    -->
    <div class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5">

        <!-- Compose -->
        <a href="<?= url('/compose') ?>"
           class="<?= $navClass('/compose') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-pencil-square text-base text-slate-400 flex-shrink-0"></i>
            <span>Compose</span>
        </a>

        <!-- Recipients -->
        <a href="<?= url('/recipients') ?>"
           class="<?= $navClass('/recipients') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-people text-base text-slate-400 flex-shrink-0"></i>
            <span>Recipients</span>
        </a>

        <!-- Email Logs -->
        <a href="<?= url('/logs') ?>"
           class="<?= $navClass('/logs') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-clock-history text-base text-slate-400 flex-shrink-0"></i>
            <span>Email Logs</span>
        </a>

        <!-- Divider before Settings section -->
        <div class="pt-3 pb-1 px-3">
            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-400">
                Settings
            </span>
        </div>

        <!-- Templates -->
        <a href="<?= url('/settings/templates') ?>"
           class="<?= $navClass('/settings/templates') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-grid-1x2 text-base text-slate-400 flex-shrink-0"></i>
            <span>Templates</span>
        </a>

        <!-- Credentials -->
        <a href="<?= url('/settings/credentials') ?>"
           class="<?= $navClass('/settings/credentials') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-key text-base text-slate-400 flex-shrink-0"></i>
            <span>Credentials</span>
        </a>

        <!-- General Settings -->
        <a href="<?= url('/settings/general') ?>"
           class="<?= $navClass('/settings/general') ?> flex items-center gap-3 px-3 py-2.5 rounded-lg
                  text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
            <i class="nav-icon bi bi-gear text-base text-slate-400 flex-shrink-0"></i>
            <span>General</span>
        </a>

    </div>

    <!-- ── USER + LOGOUT ─────────────────────────────────────────────────── -->
    <!--
        Pinned to the bottom of the sidebar.
        Shows the logged-in user's name and a sign-out button.
    -->
    <div class="flex-shrink-0 border-t border-slate-100 px-3 py-4">
        <div class="flex items-center gap-3 px-3 py-2 mb-1">
            <!-- User avatar placeholder — first initial in a coloured circle -->
            <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-white text-xs font-bold"
                 style="background-color: var(--color-primary);">
                <?= e(strtoupper(mb_substr($userName, 0, 1))) ?>
            </div>
            <span class="text-sm font-medium text-slate-700 truncate flex-1">
                <?= e($userName) ?>
            </span>
        </div>

        <!-- Logout form: POST is required to prevent CSRF logout attacks. -->
        <form method="POST" action="<?= url('/logout') ?>" class="w-full">
            <?= csrf_field() ?>
            <button
                type="submit"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                       text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors text-left"
            >
                <i class="bi bi-box-arrow-left text-base flex-shrink-0"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>

</nav>
```

---

## 3.3 — Mobile Bottom Navigation

The bottom nav is visible only on mobile viewports. It covers the four main sections of the app in a thumb-friendly fixed bar.

- [ ] **3.3.1** Create `resources/components/navigation/_bottom-nav.php`:

```php
<?php
/**
 * resources/components/navigation/_bottom-nav.php
 *
 * Mobile-only bottom navigation bar.
 * Fixed to the bottom of the viewport (fixed bottom-0).
 * Hidden on md+ breakpoints (the sidebar takes over).
 *
 * Touch targets are 48px tall minimum (py-3 + icon + label), meeting WCAG 2.5.5.
 * The Settings tab routes to /settings/general as the default settings sub-page.
 */

use App\Helpers\Html;

$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

/**
 * Returns 'active' if the given path is a prefix of the current URI.
 * Used for the bottom nav tab active state.
 */
$tabActive = function (string $path) use ($currentUri): bool {
    return str_starts_with(strtok($currentUri, '?'), $path);
};
?>

<nav
    class="fixed bottom-0 inset-x-0 z-30 flex items-stretch
           bg-white border-t border-slate-200 h-16 shadow-[0_-1px_6px_rgba(0,0,0,0.06)]"
    role="navigation"
    aria-label="Mobile navigation"
>
    <?php
    /*
     * Tab definitions: [label, icon-class, route-prefix, href]
     * route-prefix is used for active detection — /settings matches both
     * /settings/general, /settings/templates, and /settings/credentials.
     */
    $tabs = [
        ['Compose',    'bi-pencil-square', '/compose',    '/compose'],
        ['Recipients', 'bi-people',        '/recipients', '/recipients'],
        ['Logs',       'bi-clock-history', '/logs',       '/logs'],
        ['Settings',   'bi-gear',          '/settings',   '/settings/general'],
    ];
    ?>

    <?php foreach ($tabs as [$label, $icon, $prefix, $href]): ?>
        <?php $isActive = $tabActive($prefix); ?>
        <a
            href="<?= url($href) ?>"
            class="flex-1 flex flex-col items-center justify-center gap-1 min-h-[44px]
                   text-xs font-medium transition-colors
                   <?= $isActive
                       ? 'text-[color:var(--color-primary)]'
                       : 'text-slate-400 hover:text-slate-600'
                   ?>"
            aria-current="<?= $isActive ? 'page' : 'false' ?>"
        >
            <i class="bi <?= e($icon) ?> text-xl leading-none
                <?= $isActive ? 'text-[color:var(--color-primary)]' : '' ?>"></i>
            <span><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
```

---

## 3.4 — Global JavaScript (`assets/js/app.js`)

`app.js` is the single global script that powers every interactive behaviour in the shell: CSRF header injection into HTMX, the toast notification system, modal open/close, the recipient chip input, and post-swap scroll restoration.

- [ ] **3.4.1** Download HTMX 2.x and place it at `assets/js/htmx.min.js`:

```bash
# From your project root:
curl -L https://unpkg.com/htmx.org@2.0.4/dist/htmx.min.js -o assets/js/htmx.min.js
```

Confirm the file exists and is non-empty:
```bash
wc -c assets/js/htmx.min.js
# Expected: > 40000 bytes
```

---

- [ ] **3.4.2** Create `assets/js/app.js`:

```javascript
/**
 * assets/js/app.js
 * Emirates — Global Application Script
 *
 * Responsibilities:
 *   1. CSRF header injection into every HTMX request
 *   2. Toast notification system (triggered by HX-Trigger: showToast)
 *   3. Modal open/close (openModal / closeModal global functions)
 *   4. Recipient chip input initialisation
 *   5. Post-swap scroll restoration
 *   6. HTMX progress bar finalisation (reset after request completes)
 *
 * This file has no build step — it runs as-is in the browser.
 * It must work without any framework or bundler.
 */

'use strict';

/* ═══════════════════════════════════════════════════════════════════════════
   1. CSRF HEADER INJECTION
   ═══════════════════════════════════════════════════════════════════════════
   HTMX sends requests without the CSRF token by default.
   We read the token from the <meta name="csrf-token"> tag and inject it
   as a request header on every HTMX request.

   The CsrfMiddleware in PHP reads this header as X-CSRF-Token, which is
   checked alongside the _csrf POST body field.
   ─────────────────────────────────────────────────────────────────────── */

document.addEventListener('htmx:configRequest', function (event) {
    var token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        event.detail.headers['X-CSRF-Token'] = token.getAttribute('content');
    }
});


/* ═══════════════════════════════════════════════════════════════════════════
   2. TOAST NOTIFICATION SYSTEM
   ═══════════════════════════════════════════════════════════════════════════
   Toasts are triggered by the HX-Trigger response header from the server.
   The server sends:
     HX-Trigger: {"showToast": {"type": "success", "message": "Saved."}}

   HTMX fires a custom DOM event called "showToast" which we listen for here.

   Toast types:
     success — green  — auto-dismiss after 4s
     error   — red    — persistent until user dismisses
     warning — amber  — auto-dismiss after 5s
     info    — blue   — auto-dismiss after 4s

   Usage from PHP (in a controller method):
     return Response::html($partial)
         ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Saved.']);
   ─────────────────────────────────────────────────────────────────────── */

document.addEventListener('showToast', function (event) {
    var detail = event.detail || {};
    showToast(detail.type || 'info', detail.message || '');
});

/**
 * Show a toast notification.
 *
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {string} message
 */
function showToast(type, message) {
    var container = document.getElementById('toast-container');
    if (!container) return;

    // ── Style maps ──────────────────────────────────────────────────────
    var styles = {
        success: {
            bg:   'bg-emerald-50 border-emerald-200',
            text: 'text-emerald-800',
            icon: 'bi-check-circle-fill text-emerald-500',
        },
        error: {
            bg:   'bg-red-50 border-red-200',
            text: 'text-red-800',
            icon: 'bi-exclamation-circle-fill text-red-500',
        },
        warning: {
            bg:   'bg-amber-50 border-amber-200',
            text: 'text-amber-800',
            icon: 'bi-exclamation-triangle-fill text-amber-500',
        },
        info: {
            bg:   'bg-blue-50 border-blue-200',
            text: 'text-blue-800',
            icon: 'bi-info-circle-fill text-blue-500',
        },
    };

    var s = styles[type] || styles.info;

    // ── Build the toast element ──────────────────────────────────────────
    var toast = document.createElement('div');
    toast.setAttribute('role', 'alert');
    toast.className = [
        'flex items-start gap-3 w-full rounded-xl border px-4 py-3',
        'shadow-sm toast-enter',
        s.bg, s.text,
    ].join(' ');

    toast.innerHTML = [
        '<i class="bi ' + s.icon + ' text-base flex-shrink-0 mt-0.5"></i>',
        '<p class="flex-1 text-sm font-medium leading-snug">' + escapeHtml(message) + '</p>',
        '<button type="button" aria-label="Dismiss" ',
        '  class="flex-shrink-0 text-current opacity-50 hover:opacity-80 transition ml-1" ',
        '  onclick="dismissToast(this.parentElement)">',
        '  <i class="bi bi-x-lg text-xs"></i>',
        '</button>',
    ].join('');

    container.appendChild(toast);

    // ── Auto-dismiss ─────────────────────────────────────────────────────
    // Error toasts persist until the user dismisses them manually.
    // All other types auto-dismiss.
    var autoDismissMs = type === 'error' ? null : (type === 'warning' ? 5000 : 4000);

    if (autoDismissMs !== null) {
        setTimeout(function () {
            dismissToast(toast);
        }, autoDismissMs);
    }
}

/**
 * Animate a toast out and remove it from the DOM.
 *
 * @param {HTMLElement} toastEl
 */
function dismissToast(toastEl) {
    if (!toastEl || !toastEl.parentElement) return;

    toastEl.classList.remove('toast-enter');
    toastEl.classList.add('toast-leave');

    // Remove from DOM after the animation completes (200ms)
    setTimeout(function () {
        if (toastEl.parentElement) {
            toastEl.parentElement.removeChild(toastEl);
        }
    }, 220);
}

// Expose dismissToast globally so inline onclick handlers in toast HTML can call it
window.dismissToast = dismissToast;


/* ═══════════════════════════════════════════════════════════════════════════
   3. MODAL SYSTEM
   ═══════════════════════════════════════════════════════════════════════════
   The modal uses a single overlay element (#modal-overlay) and a content
   panel (#modal-panel) defined in the app layout.

   To open a modal with static content (already in the DOM):
     openModal('My Modal Title');

   To open a modal with HTMX-loaded content, use HTMX attributes on the trigger:
     hx-get="/some/partial"
     hx-target="#modal-body"
     hx-swap="innerHTML"
     onclick="openModal('My Title')"

   To close: closeModal() — also triggered by Escape key and backdrop click.
   ─────────────────────────────────────────────────────────────────────── */

/**
 * Open the global modal.
 *
 * @param {string} title  — Text shown in the modal header
 */
function openModal(title) {
    var overlay = document.getElementById('modal-overlay');
    var titleEl = document.getElementById('modal-title');

    if (!overlay) return;

    if (title && titleEl) {
        titleEl.textContent = title;
    }

    overlay.classList.remove('hidden');

    // Prevent the page behind the modal from scrolling
    document.body.style.overflow = 'hidden';

    // Move focus into the modal for accessibility
    var firstFocusable = overlay.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) {
        firstFocusable.focus();
    }
}

/**
 * Close the global modal and clear its body content.
 */
function closeModal() {
    var overlay = document.getElementById('modal-overlay');
    var body    = document.getElementById('modal-body');

    if (!overlay) return;

    overlay.classList.add('hidden');
    document.body.style.overflow = '';

    // Clear the modal body so stale content doesn't flash next time it opens
    if (body) {
        body.innerHTML = '';
    }

    // Return focus to the element that triggered the modal (if tracked)
    if (window._modalTrigger && document.contains(window._modalTrigger)) {
        window._modalTrigger.focus();
        window._modalTrigger = null;
    }
}

// Expose globally so PHP views can call them from onclick attributes
window.openModal  = openModal;
window.closeModal = closeModal;

// ── Backdrop click closes modal ──────────────────────────────────────────
document.addEventListener('click', function (event) {
    var overlay = document.getElementById('modal-overlay');
    if (event.target === overlay) {
        closeModal();
    }
});

// ── Escape key closes modal ──────────────────────────────────────────────
document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        var overlay = document.getElementById('modal-overlay');
        if (overlay && !overlay.classList.contains('hidden')) {
            closeModal();
        }
    }
});

// ── Track what element triggered the modal (for focus restoration) ───────
document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-modal-trigger]');
    if (trigger) {
        window._modalTrigger = trigger;
    }
});


/* ═══════════════════════════════════════════════════════════════════════════
   4. RECIPIENT CHIP INPUT
   ═══════════════════════════════════════════════════════════════════════════
   The Compose page "To" field uses a chip/tag-style multi-value input.
   Each recipient email (or group name) is displayed as a removable chip.
   The actual values are stored in a hidden input as a JSON array.

   This is initialised on any element with [data-chip-input]:
     <div data-chip-input data-hidden-name="recipients">...</div>

   Called from init() at the bottom of this file.
   ─────────────────────────────────────────────────────────────────────── */

function initChipInput(container) {
    var hiddenInput = document.querySelector(
        'input[name="' + container.dataset.hiddenName + '"]'
    );

    if (!hiddenInput) return;

    var chips = [];

    // ── Text input where the user types ─────────────────────────────────
    var input = document.createElement('input');
    input.type        = 'text';
    input.placeholder = 'Email or group name…';
    input.className   = 'flex-1 min-w-[180px] outline-none bg-transparent text-sm py-1';
    input.setAttribute('aria-label', 'Add recipient');

    container.appendChild(input);

    // ── Add a chip ───────────────────────────────────────────────────────
    function addChip(value) {
        value = value.trim();
        if (!value || chips.includes(value)) return;

        chips.push(value);
        updateHidden();
        renderChip(value);
        input.value = '';
    }

    // ── Remove a chip ────────────────────────────────────────────────────
    function removeChip(value) {
        chips = chips.filter(function (c) { return c !== value; });
        updateHidden();
    }

    // ── Sync chips array to the hidden input ─────────────────────────────
    function updateHidden() {
        hiddenInput.value = JSON.stringify(chips);
    }

    // ── Render a chip element ────────────────────────────────────────────
    function renderChip(value) {
        var chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-full ' +
                         'bg-slate-100 text-slate-700 text-xs font-medium';
        chip.innerHTML = escapeHtml(value) +
            '<button type="button" aria-label="Remove ' + escapeHtml(value) + '" ' +
            '  class="flex items-center text-slate-400 hover:text-slate-700 transition">' +
            '  <i class="bi bi-x text-sm leading-none"></i>' +
            '</button>';

        chip.querySelector('button').addEventListener('click', function () {
            removeChip(value);
            container.removeChild(chip);
        });

        // Insert before the text input
        container.insertBefore(chip, input);
    }

    // ── Keyboard handling ────────────────────────────────────────────────
    input.addEventListener('keydown', function (event) {
        // Enter or comma: add chip
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            addChip(input.value.replace(/,/g, ''));
        }
        // Backspace on empty input: remove last chip
        if (event.key === 'Backspace' && input.value === '' && chips.length > 0) {
            var last = chips[chips.length - 1];
            var lastChipEl = container.querySelector('span:last-of-type');
            removeChip(last);
            if (lastChipEl) container.removeChild(lastChipEl);
        }
    });

    // ── Paste: split on commas/newlines and add multiple chips ───────────
    input.addEventListener('paste', function (event) {
        event.preventDefault();
        var pasted = (event.clipboardData || window.clipboardData).getData('text');
        pasted.split(/[\n,]+/).forEach(function (val) {
            addChip(val);
        });
    });

    // ── Clicking the container focuses the text input ────────────────────
    container.addEventListener('click', function () {
        input.focus();
    });
}


/* ═══════════════════════════════════════════════════════════════════════════
   5. POST-SWAP SCROLL RESTORATION
   ═══════════════════════════════════════════════════════════════════════════
   When HTMX does a full page swap (hx-boost or hx-target="body"), the
   browser doesn't scroll to the top automatically. We do it manually.
   ─────────────────────────────────────────────────────────────────────── */

document.addEventListener('htmx:afterSwap', function (event) {
    // Only scroll if the target was a major content area, not a small partial
    var target = event.detail.target;
    if (target && (target.id === 'main-content' || target.tagName === 'BODY')) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});


/* ═══════════════════════════════════════════════════════════════════════════
   6. HTMX PROGRESS BAR FINALISATION
   ═══════════════════════════════════════════════════════════════════════════
   The CSS sets the loader to 70% width when body.htmx-request is active.
   When the request completes, HTMX removes the class, but the CSS transition
   abruptly snaps back to 0. We instead animate to 100% then fade out.
   ─────────────────────────────────────────────════════════════════════════
─ */

document.addEventListener('htmx:afterRequest', function () {
    var loader = document.getElementById('global-loader');
    if (!loader) return;

    // Snap to 100% and fade out
    loader.style.transition = 'width 0.1s ease, opacity 0.3s ease 0.15s';
    loader.style.width      = '100%';
    loader.style.opacity    = '1';

    setTimeout(function () {
        loader.style.opacity = '0';
        setTimeout(function () {
            // Reset back to initial state for next request
            loader.style.transition = '';
            loader.style.width      = '0%';
        }, 350);
    }, 150);
});


/* ═══════════════════════════════════════════════════════════════════════════
   UTILITY FUNCTIONS
   ═══════════════════════════════════════════════════════════════════════════ */

/**
 * HTML-escape a string for safe injection into the DOM.
 * Used inside JS-generated HTML (toast messages, chip labels).
 *
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
    var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(str).replace(/[&<>"']/g, function (c) { return map[c]; });
}


/* ═══════════════════════════════════════════════════════════════════════════
   INITIALISATION
   ═══════════════════════════════════════════════════════════════════════════
   Run once on DOMContentLoaded and re-run after every HTMX swap
   to pick up dynamically injected chip inputs.
   ─────────────────────────────────────────────────────────────────────── */

function init() {
    // Initialise all chip inputs on the page
    document.querySelectorAll('[data-chip-input]').forEach(function (el) {
        // Avoid double-initialising (check for a flag attribute we set)
        if (!el.dataset.chipInitialised) {
            el.dataset.chipInitialised = 'true';
            initChipInput(el);
        }
    });
}

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('htmx:afterSwap',   init);
```

---

## 3.5 — UI Component Library

These reusable partials are `require`d or `include`d inside views throughout the application. They are not rendered independently — they receive their data as local variables from the surrounding view.

---

### 3.5.1 — Toast Component

The toast component is rendered entirely by JavaScript (see `app.js` section 2 above). However, we need the PHP helper that controllers call to trigger a toast via the `HX-Trigger` header.

- [ ] **3.5.1** Add `htmxTrigger()` to `app/Core/Response.php` if not already implemented:

```php
/**
 * Append an HX-Trigger header to fire a named HTMX event on the client.
 *
 * The event name maps to a document.addEventListener() listener in app.js.
 * Multiple calls chain together in a single JSON header value.
 *
 * Usage — trigger a toast:
 *   return $this->partial('compose/_form')
 *       ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Email sent.']);
 *
 * Usage — trigger multiple events:
 *   return Response::html('')
 *       ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'Saved.'])
 *       ->htmxTrigger('refreshRecipients');
 *
 * @param string $eventName  The JS event name (e.g. 'showToast')
 * @param mixed  $data       Optional data passed to the event detail
 */
public function htmxTrigger(string $eventName, mixed $data = null): static
{
    // Read the current HX-Trigger header value (may already have events)
    $existing = $this->headers['HX-Trigger'] ?? null;

    $events = $existing ? json_decode($existing, true) : [];

    if ($data !== null) {
        $events[$eventName] = $data;
    } else {
        $events[$eventName] = true;
    }

    $this->headers['HX-Trigger'] = json_encode($events);
    return $this;
}

/**
 * Set the HX-Redirect header, causing HTMX to do a client-side redirect.
 *
 * Use this instead of a regular redirect when the current request was
 * made by HTMX — a normal Location redirect header won't work because
 * HTMX intercepts the response.
 *
 * Usage:
 *   return Response::html('')->htmxRedirect('/compose');
 */
public function htmxRedirect(string $url): static
{
    $this->headers['HX-Redirect'] = $url;
    return $this;
}
```

---

### 3.5.2 — Empty State Component

- [ ] **3.5.2** Create `resources/components/ui/_empty-state.php`:

```php
<?php
/**
 * resources/components/ui/_empty-state.php
 *
 * Displayed when a listing page has no data to show.
 *
 * Variables (all optional — provide at least $heading):
 *   string $icon     Bootstrap icon class, e.g. 'bi-inbox' (default: 'bi-inbox')
 *   string $heading  Primary message, e.g. 'No recipients yet'
 *   string $subtext  Supporting description
 *   string $ctaLabel Label for the call-to-action button
 *   string $ctaUrl   URL for the CTA button
 *   string $ctaHtmx  Optional: space-separated hx-* attributes string for HTMX CTA
 */

$icon     = $icon     ?? 'bi-inbox';
$heading  = $heading  ?? 'Nothing here yet';
$subtext  = $subtext  ?? '';
$ctaLabel = $ctaLabel ?? null;
$ctaUrl   = $ctaUrl   ?? null;
$ctaHtmx  = $ctaHtmx  ?? '';
?>

<div class="flex flex-col items-center justify-center py-20 px-6 text-center">
    <!-- Illustration icon -->
    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-5">
        <i class="bi <?= e($icon) ?> text-3xl text-slate-400"></i>
    </div>

    <!-- Heading -->
    <h3 class="text-base font-semibold text-slate-900 mb-2">
        <?= e($heading) ?>
    </h3>

    <!-- Subtext -->
    <?php if ($subtext): ?>
        <p class="text-sm text-slate-500 max-w-xs mb-6 leading-relaxed">
            <?= e($subtext) ?>
        </p>
    <?php endif; ?>

    <!-- Optional CTA button -->
    <?php if ($ctaLabel && $ctaUrl): ?>
        <a
            href="<?= e(url($ctaUrl)) ?>"
            <?= $ctaHtmx ?>
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold
                   text-white transition hover:opacity-90 focus:outline-none focus:ring-2
                   focus:ring-offset-2"
            style="background-color: var(--color-primary);"
        >
            <?= e($ctaLabel) ?>
        </a>
    <?php endif; ?>
</div>
```

---

### 3.5.3 — Badge Component

- [ ] **3.5.3** Create `resources/components/ui/_badge.php`:

```php
<?php
/**
 * resources/components/ui/_badge.php
 *
 * Inline status badge — used in log tables, template cards, etc.
 *
 * Variables:
 *   string $type   One of: success, error, warning, info, neutral (default: neutral)
 *   string $label  The text displayed inside the badge
 */

$type  = $type  ?? 'neutral';
$label = $label ?? '';

$styles = [
    'success' => 'bg-emerald-100 text-emerald-800',
    'error'   => 'bg-red-100    text-red-800',
    'warning' => 'bg-amber-100  text-amber-800',
    'info'    => 'bg-blue-100   text-blue-800',
    'neutral' => 'bg-slate-100  text-slate-700',
];

$cls = $styles[$type] ?? $styles['neutral'];
?>

<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $cls ?>">
    <?= e($label) ?>
</span>
```

---

### 3.5.4 — Loader Spinner

- [ ] **3.5.4** Create `resources/components/ui/_loader.php`:

```php
<?php
/**
 * resources/components/ui/_loader.php
 *
 * Inline spinner used as an HTMX hx-indicator target.
 *
 * Usage in a view:
 *   <button
 *     hx-post="/send"
 *     hx-indicator="#send-spinner"
 *   >
 *     Send
 *     <?php require BASE_PATH . '/resources/components/ui/_loader.php'; ?>
 *   </button>
 *
 * HTMX adds the 'htmx-request' class to the indicator element while the
 * request is in flight. The spinner is hidden by default (opacity-0) and
 * visible only during requests (htmx-request:opacity-100).
 *
 * Variables:
 *   string $id   The element ID (used in hx-indicator="#id"). Default: 'spinner'
 *   string $size Tailwind size classes (default: 'w-4 h-4')
 */

$id   = $id   ?? 'spinner';
$size = $size ?? 'w-4 h-4';
?>

<span
    id="<?= e($id) ?>"
    class="htmx-indicator inline-block <?= e($size) ?>"
    aria-hidden="true"
>
    <svg
        class="animate-spin <?= e($size) ?> text-current opacity-0 htmx-request:opacity-100"
        fill="none"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
    >
        <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
</span>
```

---

### 3.5.5 — Pagination Component

- [ ] **3.5.5** Create `resources/components/tables/_pagination.php`:

```php
<?php
/**
 * resources/components/tables/_pagination.php
 *
 * Renders page navigation for any paginated listing.
 * Uses HTMX to load pages without a full page reload.
 *
 * Variables:
 *   int    $page      Current page number (1-indexed)
 *   int    $lastPage  Total number of pages
 *   string $baseUrl   Base URL for page links, e.g. '/recipients'
 *                     The ?page=N query string is appended automatically.
 *   string $target    HTMX hx-target value (e.g. '#recipient-table').
 *                     If null, falls back to standard href links.
 */

$page     = (int) ($page     ?? 1);
$lastPage = (int) ($lastPage ?? 1);
$baseUrl  = $baseUrl ?? '';
$target   = $target  ?? null;

// Don't render pagination if there's only one page
if ($lastPage <= 1) return;

/**
 * Build the URL for a given page number.
 */
$pageUrl = function (int $n) use ($baseUrl): string {
    return url($baseUrl . '?page=' . $n);
};

/**
 * Build the HTMX attributes string for a page link.
 * If $target is set, page switches happen asynchronously via HTMX.
 */
$htmxAttrs = function (int $n) use ($pageUrl, $target): string {
    if ($target === null) return '';
    return 'hx-get="' . e($pageUrl($n)) . '" hx-target="' . e($target) . '" hx-push-url="true"';
};

// Build a compact window of page numbers around the current page
// e.g. for page 7 of 20: [1, …, 5, 6, 7, 8, 9, …, 20]
$window    = 2; // pages on each side of the current page
$pagesToShow = [];

for ($i = 1; $i <= $lastPage; $i++) {
    if (
        $i === 1 ||
        $i === $lastPage ||
        ($i >= $page - $window && $i <= $page + $window)
    ) {
        $pagesToShow[] = $i;
    }
}
?>

<nav
    class="flex items-center justify-between px-1 py-4"
    aria-label="Pagination"
>
    <!-- Page count summary -->
    <p class="text-sm text-slate-500">
        Page <span class="font-medium text-slate-700"><?= $page ?></span>
        of <span class="font-medium text-slate-700"><?= $lastPage ?></span>
    </p>

    <!-- Page number links -->
    <div class="flex items-center gap-1" role="list">

        <!-- Previous -->
        <?php if ($page > 1): ?>
            <a href="<?= e($pageUrl($page - 1)) ?>"
               <?= $htmxAttrs($page - 1) ?>
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                      text-sm text-slate-500 hover:bg-slate-100 transition"
               aria-label="Previous page">
                <i class="bi bi-chevron-left text-xs"></i>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                         text-sm text-slate-300 cursor-not-allowed" aria-hidden="true">
                <i class="bi bi-chevron-left text-xs"></i>
            </span>
        <?php endif; ?>

        <!-- Page numbers -->
        <?php
        $prev = null;
        foreach ($pagesToShow as $n):
            // Insert ellipsis for gaps in the page range
            if ($prev !== null && $n - $prev > 1):
        ?>
                <span class="inline-flex items-center justify-center w-8 h-8 text-sm text-slate-400"
                      aria-hidden="true">…</span>
        <?php
            endif;
            $prev = $n;
        ?>

            <?php if ($n === $page): ?>
                <span
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm
                           font-semibold text-white"
                    style="background-color: var(--color-primary);"
                    aria-current="page"
                >
                    <?= $n ?>
                </span>
            <?php else: ?>
                <a href="<?= e($pageUrl($n)) ?>"
                   <?= $htmxAttrs($n) ?>
                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm
                          text-slate-600 hover:bg-slate-100 transition">
                    <?= $n ?>
                </a>
            <?php endif; ?>

        <?php endforeach; ?>

        <!-- Next -->
        <?php if ($page < $lastPage): ?>
            <a href="<?= e($pageUrl($page + 1)) ?>"
               <?= $htmxAttrs($page + 1) ?>
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                      text-sm text-slate-500 hover:bg-slate-100 transition"
               aria-label="Next page">
                <i class="bi bi-chevron-right text-xs"></i>
            </a>
        <?php else: ?>
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg
                         text-sm text-slate-300 cursor-not-allowed" aria-hidden="true">
                <i class="bi bi-chevron-right text-xs"></i>
            </span>
        <?php endif; ?>

    </div>
</nav>
```

---

### 3.5.6 — Sortable Table Header

- [ ] **3.5.6** Create `resources/components/tables/_sortable-header.php`:

```php
<?php
/**
 * resources/components/tables/_sortable-header.php
 *
 * A <th> element that triggers an HTMX sort request when clicked.
 *
 * Variables:
 *   string $label      Column display label (e.g. 'Date Added')
 *   string $column     Column identifier passed to the server (e.g. 'created_at')
 *   string $currentCol The currently active sort column
 *   string $currentDir The current sort direction ('asc' or 'desc')
 *   string $sortUrl    Base URL to POST/GET with sort params (e.g. '/recipients')
 *   string $target     HTMX hx-target (e.g. '#recipient-table')
 */

$label      = $label      ?? '';
$column     = $column     ?? '';
$currentCol = $currentCol ?? '';
$currentDir = $currentDir ?? 'asc';
$sortUrl    = $sortUrl    ?? '';
$target     = $target     ?? 'body';

// Determine the new direction when this column is clicked
$isActive = $currentCol === $column;
$newDir   = ($isActive && $currentDir === 'asc') ? 'desc' : 'asc';

// Build the full sort URL
$url = url($sortUrl . '?' . http_build_query([
    'sort' => $column,
    'dir'  => $newDir,
]));
?>

<th
    scope="col"
    class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider
           whitespace-nowrap select-none"
>
    <button
        type="button"
        hx-get="<?= e($url) ?>"
        hx-target="<?= e($target) ?>"
        hx-push-url="true"
        class="inline-flex items-center gap-1 hover:text-slate-800 transition-colors
               <?= $isActive ? 'text-slate-800' : 'text-slate-500' ?>"
    >
        <?= e($label) ?>

        <!-- Sort direction indicator -->
        <?php if ($isActive): ?>
            <i class="bi bi-arrow-<?= $currentDir === 'asc' ? 'up' : 'down' ?> text-xs"></i>
        <?php else: ?>
            <i class="bi bi-arrow-down-up text-xs opacity-30"></i>
        <?php endif; ?>
    </button>
</th>
```

---

## 3.6 — Error Views

Error views must be completely self-contained. They cannot rely on the `app.php` layout, the sidebar, or any of the helper functions (the error might occur before bootstrapping is complete). They have inline CSS and minimal dependencies.

---

- [ ] **3.6.1** Create `resources/layouts/error.php`:

```php
<?php
/**
 * resources/layouts/error.php
 *
 * Minimal layout for error pages (404, 403, 500).
 * No sidebar, no session dependency, no external CSS files that might fail.
 * Bootstrap Icons CDN is the only external dependency.
 *
 * Variables:
 *   $content   string — The rendered error view content
 *   $pageTitle string — Browser <title> content
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') : 'Error' ?></title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 3rem;
            text-decoration: none;
            color: inherit;
        }
        .brand-icon {
            width: 2rem; height: 2rem;
            background: #1d4ed8;
            border-radius: 0.5rem;
            display: flex; align-items: center; justify-content: center;
        }
        .brand-name {
            font-weight: 700;
            font-size: 1rem;
            color: #0f172a;
        }
    </style>
</head>
<body>
    <a href="/" class="brand">
        <div class="brand-icon">
            <i class="bi bi-send-fill" style="color:#fff;font-size:0.875rem;"></i>
        </div>
        <span class="brand-name">Emirates</span>
    </a>
    <?= $content ?>
</body>
</html>
```

---

- [ ] **3.6.2** Create `resources/error/404.php`:

```php
<?php
/**
 * resources/error/404.php
 *
 * Page Not Found error view.
 * Rendered inside resources/layouts/error.php.
 */
?>
<style>
    .error-code    { font-size: 6rem; font-weight: 900; color: #e2e8f0; line-height: 1; }
    .error-heading { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #0f172a; }
    .error-body    { color: #64748b; font-size: 0.9375rem; line-height: 1.6; max-width: 30rem; text-align: center; }
    .error-actions { display: flex; gap: 0.75rem; margin-top: 2rem; flex-wrap: wrap; justify-content: center; }
    .btn-primary   {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #1d4ed8; color: #fff;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none; transition: background 0.15s;
    }
    .btn-primary:hover { background: #1e40af; }
    .btn-ghost {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #f1f5f9; color: #475569;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none; transition: background 0.15s;
    }
    .btn-ghost:hover { background: #e2e8f0; }
</style>

<div style="text-align:center;">
    <p class="error-code">404</p>
    <h1 class="error-heading">Page not found</h1>
    <p class="error-body">
        The page you're looking for doesn't exist or may have been moved.
        Check the URL or head back to the compose page.
    </p>
    <div class="error-actions">
        <a href="/" class="btn-primary">
            <i class="bi bi-house-fill"></i>
            Go home
        </a>
        <a href="javascript:history.back()" class="btn-ghost">
            <i class="bi bi-arrow-left"></i>
            Go back
        </a>
    </div>
</div>
```

---

- [ ] **3.6.3** Create `resources/error/403.php`:

```php
<?php
/**
 * resources/error/403.php
 * Access Denied.
 */
?>
<style>
    .error-code    { font-size: 6rem; font-weight: 900; color: #fee2e2; line-height: 1; }
    .error-heading { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #0f172a; }
    .error-body    { color: #64748b; font-size: 0.9375rem; line-height: 1.6; max-width: 30rem; text-align: center; }
    .error-actions { display: flex; gap: 0.75rem; margin-top: 2rem; flex-wrap: wrap; justify-content: center; }
    .btn-primary   {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #1d4ed8; color: #fff;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none;
    }
</style>

<div style="text-align:center;">
    <p class="error-code">403</p>
    <h1 class="error-heading">Access denied</h1>
    <p class="error-body">
        You don't have permission to view this page.
        If you believe this is a mistake, try signing in again.
    </p>
    <div class="error-actions">
        <a href="/login" class="btn-primary">
            <i class="bi bi-box-arrow-in-right"></i>
            Sign in
        </a>
    </div>
</div>
```

---

- [ ] **3.6.4** Create `resources/error/500.php`:

```php
<?php
/**
 * resources/error/500.php
 * Internal Server Error — shown in production (APP_DEBUG=false).
 *
 * Variables:
 *   string $errorRef  An optional short error reference for support (e.g. a timestamp)
 */
$errorRef = $errorRef ?? date('YmdHis');
?>
<style>
    .error-code    { font-size: 6rem; font-weight: 900; color: #e2e8f0; line-height: 1; }
    .error-heading { font-size: 1.5rem; font-weight: 700; margin: 1rem 0 0.5rem; color: #0f172a; }
    .error-body    { color: #64748b; font-size: 0.9375rem; line-height: 1.6; max-width: 30rem; text-align: center; }
    .error-ref     { font-family: monospace; font-size: 0.75rem; color: #94a3b8; margin-top: 0.5rem; }
    .error-actions { display: flex; gap: 0.75rem; margin-top: 2rem; flex-wrap: wrap; justify-content: center; }
    .btn-primary   {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; background: #1d4ed8; color: #fff;
        border-radius: 0.625rem; font-size: 0.875rem; font-weight: 600;
        text-decoration: none;
    }
</style>

<div style="text-align:center;">
    <p class="error-code">500</p>
    <h1 class="error-heading">Something went wrong</h1>
    <p class="error-body">
        An unexpected error occurred. The error has been logged.
        Try refreshing the page — if the problem persists, check the application logs.
    </p>
    <p class="error-ref">Error ref: <?= htmlspecialchars($errorRef, ENT_QUOTES, 'UTF-8') ?></p>
    <div class="error-actions">
        <a href="/" class="btn-primary">
            <i class="bi bi-arrow-repeat"></i>
            Try again
        </a>
    </div>
</div>
```

---

- [ ] **3.6.5** Create `resources/error/debug.php`:

```php
<?php
/**
 * resources/error/debug.php
 *
 * Developer debug view — shown ONLY when APP_DEBUG=true.
 * NEVER shown in production.
 *
 * Variables (set by ErrorHandler):
 *   Throwable $exception  The caught exception
 *   array     $sourceLines  Array of ['line' => n, 'code' => '...', 'active' => bool]
 *                            (5 lines before and after the error line)
 */

$exception   = $exception   ?? null;
$sourceLines = $sourceLines ?? [];

$class   = $exception ? get_class($exception)      : 'Unknown Error';
$message = $exception ? $exception->getMessage()   : 'No message';
$file    = $exception ? $exception->getFile()      : 'Unknown';
$line    = $exception ? $exception->getLine()      : 0;
$trace   = $exception ? $exception->getTraceAsString() : '';
$code    = $exception ? $exception->getCode()      : 0;

/**
 * Safe HTML escape — uses htmlspecialchars because e() may not be available
 * if the error occurred during bootstrap.
 */
$h = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $h($class) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body   { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', monospace; background: #0f172a; color: #e2e8f0; margin: 0; padding: 0; }
        header { background: #7f1d1d; padding: 1.5rem 2rem; border-bottom: 1px solid #991b1b; }
        .exc-class { font-size: 0.875rem; color: #fca5a5; font-family: monospace; margin-bottom: 0.5rem; }
        .exc-msg   { font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1.3; word-break: break-word; }
        .exc-loc   { font-size: 0.8125rem; color: #fca5a5; margin-top: 0.75rem; font-family: monospace; }
        .exc-code  { display: inline-block; background: #991b1b; border-radius: 4px; padding: 1px 6px; margin-left: 0.5rem; }
        main  { max-width: 1100px; margin: 0 auto; padding: 2rem; }
        section { margin-bottom: 2rem; }
        h2 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 0.75rem; font-weight: 600; }
        /* Source code block */
        .source { background: #1e293b; border-radius: 8px; overflow: hidden; font-family: monospace; font-size: 0.8125rem; line-height: 1.6; }
        .source-line { display: flex; padding: 0 1rem; }
        .source-line.active { background: #7f1d1d; }
        .source-line.active .src-code { color: #fca5a5; }
        .src-num  { width: 3rem; flex-shrink: 0; color: #475569; text-align: right; padding-right: 1rem; user-select: none; }
        .src-code { white-space: pre; overflow-x: auto; flex: 1; }
        /* Stack trace */
        .trace { background: #1e293b; border-radius: 8px; padding: 1.25rem 1.5rem; font-family: monospace; font-size: 0.75rem; color: #94a3b8; white-space: pre-wrap; word-break: break-word; line-height: 1.7; }
        /* Context panels */
        details { background: #1e293b; border-radius: 8px; margin-bottom: 0.75rem; overflow: hidden; }
        summary { padding: 0.875rem 1.25rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; color: #cbd5e1; list-style: none; display: flex; align-items: center; justify-content: space-between; }
        summary::after { content: '+'; font-size: 1rem; color: #475569; }
        details[open] summary::after { content: '−'; }
        .panel-body { padding: 1rem 1.25rem; border-top: 1px solid #334155; }
        pre { margin: 0; font-size: 0.75rem; color: #94a3b8; white-space: pre-wrap; word-break: break-word; line-height: 1.7; }
        .empty { color: #475569; font-size: 0.875rem; font-style: italic; padding: 0.5rem 0; }
    </style>
</head>
<body>

<header>
    <p class="exc-class">
        <?= $h($class) ?>
        <?php if ($code): ?>
            <span class="exc-code">Code <?= $h($code) ?></span>
        <?php endif; ?>
    </p>
    <h1 class="exc-msg"><?= $h($message) ?></h1>
    <p class="exc-loc">
        <?= $h($file) ?> : line <?= $h($line) ?>
    </p>
</header>

<main>

    <!-- ── SOURCE CODE SNIPPET ──────────────────────────────────────────── -->
    <?php if (!empty($sourceLines)): ?>
    <section>
        <h2>Source</h2>
        <div class="source">
            <?php foreach ($sourceLines as $srcLine): ?>
                <div class="source-line <?= $srcLine['active'] ? 'active' : '' ?>">
                    <span class="src-num"><?= (int)$srcLine['line'] ?></span>
                    <span class="src-code"><?= $h($srcLine['code']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ── STACK TRACE ──────────────────────────────────────────────────── -->
    <section>
        <h2>Stack Trace</h2>
        <div class="trace"><?= $h($trace) ?></div>
    </section>

    <!-- ── CONTEXT PANELS ───────────────────────────────────────────────── -->
    <section>
        <h2>Request Context</h2>

        <?php
        $panels = [
            '$_SERVER' => $_SERVER ?? [],
            '$_POST'   => $_POST   ?? [],
            '$_GET'    => $_GET    ?? [],
            '$_SESSION'=> $_SESSION ?? [],
        ];

        foreach ($panels as $label => $data):
            // Redact sensitive keys
            $redactKeys = ['password', 'password_hash', 'APP_KEY', 'DB_PASS', '_csrf'];
            $safe = [];
            foreach ($data as $k => $v) {
                $safe[$k] = in_array($k, $redactKeys, true) ? '[REDACTED]' : $v;
            }
        ?>
            <details>
                <summary><?= $h($label) ?> (<?= count($data) ?> keys)</summary>
                <div class="panel-body">
                    <?php if (empty($safe)): ?>
                        <p class="empty">Empty</p>
                    <?php else: ?>
                        <pre><?= $h(print_r($safe, true)) ?></pre>
                    <?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
    </section>

</main>
</body>
</html>
```

---

- [ ] **3.6.6** Update `app/Core/ErrorHandler.php` to supply `$sourceLines` to the debug view and use the error layout for production views:

```php
<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;
use App\Exceptions\AppException;

/**
 * ErrorHandler
 *
 * Registers PHP's exception, error, and shutdown handlers.
 * In debug mode: renders the full debug view with source snippet.
 * In production: logs the error and renders an appropriate error page.
 * For HTMX requests in production: returns HX-Trigger toast instead of a full page.
 */
class ErrorHandler
{
    private bool   $debug;
    private Logger $logger;

    public function __construct(bool $debug, Logger $logger)
    {
        $this->debug  = $debug;
        $this->logger = $logger;
    }

    /**
     * Register all PHP error and exception handlers.
     * Call this once during bootstrap, before any application code runs.
     */
    public function register(): void
    {
        // Uncaught exceptions
        set_exception_handler([$this, 'handleException']);

        // PHP errors converted to exceptions
        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
            return false;
        });

        // Fatal errors not caught by set_error_handler (parse errors, OOM, etc.)
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                $this->renderError(500, 'Fatal Error: ' . $error['message']);
            }
        });
    }

    /**
     * Main exception handler.
     * Determines the HTTP status code and renders the appropriate response.
     */
    public function handleException(\Throwable $e): void
    {
        $status = $this->statusForException($e);

        // Log everything at 500 level; skip logging for expected 404s and 401s
        if ($status >= 500) {
            $this->logger->error($e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }

        if ($this->debug) {
            $this->renderDebug($e);
        } elseif ($this->isHtmxRequest()) {
            $this->renderHtmxError($status, $e->getMessage());
        } else {
            $this->renderProduction($status, $e);
        }
    }

    // ── Internal helpers ───────────────────────────────────────────────────

    /**
     * Map exception types to HTTP status codes.
     */
    private function statusForException(\Throwable $e): int
    {
        return match (true) {
            $e instanceof NotFoundException  => 404,
            $e instanceof AuthException      => 401,
            $e instanceof ValidationException => 422,
            $e instanceof AppException       => $e->getCode() >= 400 ? $e->getCode() : 500,
            default                           => 500,
        };
    }

    /**
     * Render the full debug view (dev only).
     */
    private function renderDebug(\Throwable $e): void
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');

        // Build source snippet: 5 lines before and 5 lines after the error line
        $sourceLines = $this->extractSourceLines($e->getFile(), $e->getLine(), 5);

        // Render the debug view directly (no layout wrapper — it's self-contained)
        extract(['exception' => $e, 'sourceLines' => $sourceLines]);
        require BASE_PATH . '/resources/error/debug.php';
        exit;
    }

    /**
     * Render an appropriate production error page (using the error layout).
     */
    private function renderProduction(int $status, \Throwable $e): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');

        $viewFile = BASE_PATH . '/resources/error/' . $status . '.php';

        // Fall back to 500 if no specific view exists for this status code
        if (!file_exists($viewFile)) {
            $viewFile = BASE_PATH . '/resources/error/500.php';
        }

        $errorRef = date('YmdHis');

        // Render view into $content
        ob_start();
        extract(['errorRef' => $errorRef]);
        require $viewFile;
        $content = ob_get_clean();

        // Wrap in the error layout
        $layoutFile = BASE_PATH . '/resources/layouts/error.php';
        $pageTitle  = $status . ' — Error';

        extract(['content' => $content, 'pageTitle' => $pageTitle]);
        require $layoutFile;
        exit;
    }

    /**
     * For HTMX requests in production: return a JSON trigger that shows a toast.
     * HTMX will receive this and fire the 'showToast' event on the client.
     */
    private function renderHtmxError(int $status, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/html');
        header('HX-Trigger: ' . json_encode([
            'showToast' => [
                'type'    => 'error',
                'message' => 'An error occurred: ' . $message,
            ],
        ]));
        echo '';
        exit;
    }

    /**
     * Check if the current request was made by HTMX.
     */
    private function isHtmxRequest(): bool
    {
        return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
    }

    /**
     * Extract source code lines around the error location.
     *
     * @param string $file        Absolute path to the PHP file
     * @param int    $errorLine   The line number where the error occurred
     * @param int    $context     Number of lines to include before and after
     * @return array  Array of ['line' => int, 'code' => string, 'active' => bool]
     */
    private function extractSourceLines(string $file, int $errorLine, int $context = 5): array
    {
        if (!file_exists($file) || !is_readable($file)) {
            return [];
        }

        $lines  = file($file, FILE_IGNORE_NEW_LINES);
        $start  = max(0, $errorLine - $context - 1);
        $end    = min(count($lines) - 1, $errorLine + $context - 1);
        $result = [];

        for ($i = $start; $i <= $end; $i++) {
            $result[] = [
                'line'   => $i + 1,
                'code'   => $lines[$i],
                'active' => ($i + 1) === $errorLine,
            ];
        }

        return $result;
    }

    /**
     * Render a plain-text error fallback (used by renderError for fatal shutdown errors).
     */
    private function renderError(int $status, string $message): void
    {
        if (headers_sent()) return;

        http_response_code($status);
        if ($this->debug) {
            echo '<h1>Fatal Error</h1><pre>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</pre>';
        } else {
            echo '<h1>Internal Server Error</h1><p>An unexpected error occurred. Please try again.</p>';
        }
        exit;
    }
}
```

---

## 3.7 — Stub Controllers

Stub controllers return placeholder views so every nav link renders without errors. They will be replaced with full implementations in later phases.

- [ ] **3.7.1** Create `app/Controllers/ComposeController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * ComposeController — Stub
 *
 * Full implementation in Phase 8.
 * This stub allows the /compose route to resolve without a 500 error
 * so the application shell and navigation can be tested in Phase 3.
 */
class ComposeController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('compose/index', [
            'pageTitle' => 'Compose — ' . setting('site_name', 'Emirates'),
        ]);
    }
}
```

---

- [ ] **3.7.2** Create `app/Controllers/RecipientController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * RecipientController — Stub
 *
 * Full implementation in Phase 7.
 */
class RecipientController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('recipients/index', [
            'pageTitle' => 'Recipients — ' . setting('site_name', 'Emirates'),
        ]);
    }
}
```

---

- [ ] **3.7.3** Create `app/Controllers/LogController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * LogController — Stub
 *
 * Full implementation in Phase 10.
 */
class LogController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('logs/index', [
            'pageTitle' => 'Email Logs — ' . setting('site_name', 'Emirates'),
        ]);
    }
}
```

---

- [ ] **3.7.4** Create `app/Controllers/TemplateController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * TemplateController — Stub
 *
 * Full implementation in Phase 5.
 */
class TemplateController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('settings/templates/index', [
            'pageTitle' => 'Templates — ' . setting('site_name', 'Emirates'),
        ]);
    }
}
```

---

- [ ] **3.7.5** Create `app/Controllers/CredentialController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * CredentialController — Stub
 *
 * Full implementation in Phase 6.
 */
class CredentialController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('settings/credentials', [
            'pageTitle' => 'Credentials — ' . setting('site_name', 'Emirates'),
        ]);
    }
}
```

---

- [ ] **3.7.6** Create `app/Controllers/SettingsController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

/**
 * SettingsController — Stub
 *
 * Full implementation in Phase 4.
 */
class SettingsController extends BaseController
{
    public function index(Request $request): Response
    {
        return $this->view('settings/general', [
            'pageTitle' => 'General Settings — ' . setting('site_name', 'Emirates'),
        ]);
    }
}
```

---

## 3.8 — Placeholder Views

Placeholder views output a heading inside the app layout so every route is visitable and the shell renders correctly. They will be replaced by fully designed views in later phases.

- [ ] **3.8.1** Create `resources/compose/index.php`:

```php
<?php
/**
 * resources/compose/index.php — Placeholder
 * Full implementation: Phase 8
 */
?>
<div class="max-w-4xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Compose</h1>
        <p class="text-slate-500 mt-1 text-sm">
            This page is coming in Phase 8. The shell and navigation are working.
        </p>
    </div>

    <!-- Placeholder card -->
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-pencil-square text-3xl text-slate-400"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-2">Compose page placeholder</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">
            The full compose interface — template selector, metadata fields, editor,
            translate, send — will be built in Phase 8.
        </p>
    </div>
</div>
```

---

- [ ] **3.8.2** Create `resources/recipients/index.php`:

```php
<?php
/**
 * resources/recipients/index.php — Placeholder
 * Full implementation: Phase 7
 */
?>
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Recipients</h1>
        <p class="text-slate-500 mt-1 text-sm">Full implementation coming in Phase 7.</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-people text-3xl text-slate-400"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-2">Recipients placeholder</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">
            Contact management, CSV import, search, and groups will be built here in Phase 7.
        </p>
    </div>
</div>
```

---

- [ ] **3.8.3** Create `resources/logs/index.php`:

```php
<?php
/**
 * resources/logs/index.php — Placeholder
 * Full implementation: Phase 10
 */
?>
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Email Logs</h1>
        <p class="text-slate-500 mt-1 text-sm">Full implementation coming in Phase 10.</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-clock-history text-3xl text-slate-400"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-2">Logs placeholder</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">
            Sent email history, error logs, and received email tracking will be built here in Phase 10.
        </p>
    </div>
</div>
```

---

- [ ] **3.8.4** Create `resources/settings/templates/index.php`:

```php
<?php
/**
 * resources/settings/templates/index.php — Placeholder
 * Full implementation: Phase 5
 */
?>
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Email Templates</h1>
        <p class="text-slate-500 mt-1 text-sm">Full implementation coming in Phase 5.</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-grid-1x2 text-3xl text-slate-400"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-2">Templates placeholder</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">
            Template gallery, upload, preview, duplicate, and delete will be built here in Phase 5.
        </p>
    </div>
</div>
```

---

- [ ] **3.8.5** Create `resources/settings/credentials.php`:

```php
<?php
/**
 * resources/settings/credentials.php — Placeholder
 * Full implementation: Phase 6
 */
?>
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">Email Credentials</h1>
        <p class="text-slate-500 mt-1 text-sm">Full implementation coming in Phase 6.</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-key text-3xl text-slate-400"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-2">Credentials placeholder</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">
            Resend and SMTP credential management with encrypted storage will be built here in Phase 6.
        </p>
    </div>
</div>
```

---

- [ ] **3.8.6** Create `resources/settings/general.php`:

```php
<?php
/**
 * resources/settings/general.php — Placeholder
 * Full implementation: Phase 4
 */
?>
<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900">General Settings</h1>
        <p class="text-slate-500 mt-1 text-sm">Full implementation coming in Phase 4.</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-4">
            <i class="bi bi-gear text-3xl text-slate-400"></i>
        </div>
        <h2 class="text-base font-semibold text-slate-900 mb-2">Settings placeholder</h2>
        <p class="text-sm text-slate-500 max-w-xs mx-auto">
            Site name, logo, email branding, colour theme, language, and timezone
            settings will be built here in Phase 4.
        </p>
    </div>
</div>
```

---

## 3.9 — Route Updates

Register all stub controllers in `routes/web.php` so the nav links resolve.

- [ ] **3.9.1** Open `routes/web.php` and update the authenticated route group to include all stub routes:

```php
<?php

use App\Controllers\AuthController;
use App\Controllers\ComposeController;
use App\Controllers\RecipientController;
use App\Controllers\LogController;
use App\Controllers\TemplateController;
use App\Controllers\CredentialController;
use App\Controllers\SettingsController;

// ── Guest-only routes ─────────────────────────────────────────────────────
$router->group(['middleware' => ['guest']], function ($router) {
    $router->get('/login',  [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login']);
});

// ── Authenticated routes (auth + CSRF on all state-changing requests) ─────
$router->group(['middleware' => ['auth', 'csrf']], function ($router) {

    // Auth
    $router->post('/logout', [AuthController::class, 'logout']);

    // Root redirect → compose (the main landing page after login)
    $router->get('/', function () {
        return \App\Core\Response::redirect('/compose');
    });

    // ── Core pages ────────────────────────────────────────────────────────
    $router->get('/compose',    [ComposeController::class,   'index']);
    $router->get('/recipients', [RecipientController::class, 'index']);
    $router->get('/logs',       [LogController::class,       'index']);

    // ── Settings sub-pages ────────────────────────────────────────────────
    $router->get('/settings/general',     [SettingsController::class,    'index']);
    $router->get('/settings/templates',   [TemplateController::class,    'index']);
    $router->get('/settings/credentials', [CredentialController::class,  'index']);

    /*
     * ── Routes registered for future phases ──────────────────────────────
     * These are declared here (commented in) early so the Router knows
     * about them and the middleware is applied automatically.
     * Uncomment each block as the relevant phase is implemented.
     */

    // Phase 4 — General Settings
    // $router->post('/settings/general', [SettingsController::class, 'update']);

    // Phase 5 — Templates CRUD
    // $router->get('/settings/templates/create',          [TemplateController::class, 'create']);
    // $router->post('/settings/templates',                [TemplateController::class, 'store']);
    // $router->get('/settings/templates/{id}/edit',       [TemplateController::class, 'edit']);
    // $router->post('/settings/templates/{id}',           [TemplateController::class, 'update']);
    // $router->delete('/settings/templates/{id}',         [TemplateController::class, 'destroy']);
    // $router->post('/settings/templates/{id}/duplicate', [TemplateController::class, 'duplicate']);
    // $router->get('/settings/templates/{id}/preview',    [TemplateController::class, 'preview']);
    // $router->post('/settings/templates/preview-draft',  [TemplateController::class, 'previewDraft']);

    // Phase 6 — Credentials
    // $router->post('/settings/credentials',      [CredentialController::class, 'store']);
    // $router->post('/settings/credentials/test', [CredentialController::class, 'test']);

    // Phase 7 — Recipients CRUD
    // $router->get('/recipients/create',     [RecipientController::class, 'create']);
    // $router->post('/recipients',           [RecipientController::class, 'store']);
    // $router->get('/recipients/{id}/edit',  [RecipientController::class, 'edit']);
    // $router->post('/recipients/{id}',      [RecipientController::class, 'update']);
    // $router->delete('/recipients/{id}',    [RecipientController::class, 'destroy']);
    // $router->get('/recipients/import',     [RecipientController::class, 'import']);
    // $router->post('/recipients/import',    [RecipientController::class, 'import']);
    // $router->post('/recipients/{id}/suppress', [RecipientController::class, 'suppress']);

    // Phase 8 — Compose & Drafts
    // $router->post('/compose/send',          [ComposeController::class, 'send']);
    // $router->post('/compose/preview',       [ComposeController::class, 'preview']);
    // $router->post('/compose/load-template', [ComposeController::class, 'loadTemplate']);
    // ... (draft routes, translation routes)

    // Phase 10 — Logs
    // $router->get('/logs/{id}',   [LogController::class, 'show']);
    // $router->post('/logs/clear', [LogController::class, 'clear']);
});

// ── Webhook routes (no auth — validated by signature) ─────────────────────
// $router->post('/webhooks/resend', [WebhookController::class, 'resend']);

// ── Storage file serving (auth-protected) ─────────────────────────────────
// Uncomment in Phase 4 when FileUploadService and StorageController are built.
// $router->get('/storage/{type}/{filename}', [StorageController::class, 'serve']);
```

---

## 3.10 — `Html` Helper Update

The `Html::active()` method is used in the sidebar and bottom nav. Confirm it exists in `app/Helpers/Html.php` — if not, add the method:

- [ ] **3.10.1** Open `app/Helpers/Html.php` and confirm (or add) `active()`:

```php
/**
 * Return 'active' if the given path prefix matches the current request URI.
 * Used to apply active CSS classes to navigation links.
 *
 * Usage:
 *   <a class="nav-link <?= Html::active('/compose') ?>">Compose</a>
 *
 * Matching rules:
 *   /compose      → exact match only (to avoid matching /compose-settings)
 *   /settings     → prefix match (matches /settings/general, /settings/templates, etc.)
 *
 * @param string $path  The path to match against
 */
public static function active(string $path): string
{
    $uri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

    $isActive = $path === '/'
        ? $uri === '/'
        : str_starts_with((string)$uri, $path);

    return $isActive ? 'active' : '';
}
```

---

## 3.11 — Milestone: Shell Test

Work through these checks **in order**. All must pass before moving to Phase 4.

### Setup

- [ ] **3.11.1** Ensure the dev server is running:
  ```bash
  php -S localhost:8000 -t public/
  ```

- [ ] **3.11.2** Confirm `assets/js/htmx.min.js` exists and is non-empty:
  ```bash
  wc -c assets/js/htmx.min.js
  # Expected: > 40000 bytes
  ```

---

### Test 1: Authenticated Layout Renders

- [ ] **3.11.3** Log in with correct credentials and visit `http://localhost:8000/compose`.
  - **Expected:** The full layout renders: sidebar on the left (240px wide), main content area on the right showing the Compose placeholder card.
  - **Expected:** The sidebar shows: Emirates logo/name, nav links (Compose, Recipients, Email Logs, Templates, Credentials, General), and a Sign Out button at the bottom.
  - **Expected:** The browser `<title>` reads "Compose — Emirates" (or your configured site name).
  - **Failure:** If you see a plain PHP error, check that `resources/layouts/app.php` requires the navigation partials with the correct `BASE_PATH` prefix.

---

### Test 2: All Nav Links Load Correctly

- [ ] **3.11.4** Click every sidebar link and confirm each page loads without a 500 error:
  - `/compose` → "Compose placeholder" card
  - `/recipients` → "Recipients placeholder" card
  - `/logs` → "Logs placeholder" card
  - `/settings/templates` → "Templates placeholder" card
  - `/settings/credentials` → "Credentials placeholder" card
  - `/settings/general` → "Settings placeholder" card
  - **Expected:** Each page renders the shell (sidebar + content) with the placeholder content.
  - **Failure:** A 500 error means the controller or view file is missing. A 404 means the route is not registered.

---

### Test 3: Active Nav Link Highlighting

- [ ] **3.11.5** On each page above, confirm the correct sidebar link is highlighted:
  - On `/compose` → the "Compose" link should have a tinted background and coloured icon.
  - On `/recipients` → the "Recipients" link should be active.
  - On `/settings/general` → the "General" link should be active.
  - On `/settings/templates` → the "Templates" link should be active.
  - **Expected:** Active links use the `var(--color-primary)` colour defined in the layout.
  - **Failure:** If no link is active, check that `$navClass()` in `_sidebar.php` is calling `str_starts_with` correctly against `$_SERVER['REQUEST_URI']`.

---

### Test 4: Mobile Bottom Navigation

- [ ] **3.11.6** Open Chrome DevTools (or any browser) and switch to a mobile viewport (375px wide).
  - **Expected:** The sidebar disappears (`hidden md:flex` makes it invisible below `md`).
  - **Expected:** The bottom navigation bar appears at the bottom of the screen, showing 4 tabs: Compose, Recipients, Logs, Settings.
  - **Expected:** Each tab is at least 44px tall.
  - **Expected:** The main content area has enough bottom padding that it is not hidden behind the bottom nav.
  - **Failure:** If the sidebar is still visible, check that `_sidebar.php` is inside a `hidden md:flex` wrapper in `app.php`.

---

### Test 5: Toast Notification System

- [ ] **3.11.7** Add a temporary test route to `routes/web.php` to trigger a toast:

  ```php
  // TEMPORARY — remove after testing
  $router->get('/test-toast', function () {
      return \App\Core\Response::html('<p>Toast triggered</p>')
          ->htmxTrigger('showToast', ['type' => 'success', 'message' => 'The toast system works!']);
  });
  ```

  Then add a temporary test link to one of the placeholder views:
  ```html
  <button
      hx-get="/test-toast"
      hx-target="#main-content"
      class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm mt-4"
  >
      Test Toast
  </button>
  ```

  - **Expected:** Clicking the button triggers an HTMX request. A green success toast appears in the top-right corner with the message "The toast system works!".
  - **Expected:** The toast auto-dismisses after 4 seconds.
  - **Expected:** The dismiss (×) button on the toast removes it immediately.
  - **Failure:** If no toast appears, check the browser console for JavaScript errors. Verify `app.js` is loaded and that `document.addEventListener('showToast', ...)` is registered.

- [ ] **3.11.8** Test an error toast by changing `type` to `'error'` in the test route:
  - **Expected:** A red toast appears and does NOT auto-dismiss — it persists until clicked.

- [ ] **3.11.9** Remove the temporary test route and button.

---

### Test 6: Modal System

- [ ] **3.11.10** Add a temporary modal trigger to a placeholder view:
  ```html
  <button
      type="button"
      onclick="openModal('Test Modal')"
      class="px-4 py-2 bg-slate-700 text-white rounded-lg text-sm mt-2"
  >
      Open Modal
  </button>
  <script>
      document.querySelector('[onclick]').addEventListener('click', function () {
          document.getElementById('modal-body').innerHTML =
              '<p class="text-slate-600">This is the modal body content.</p>';
      });
  </script>
  ```
  - **Expected:** Clicking "Open Modal" opens the modal overlay with the title "Test Modal" and the body text.
  - **Expected:** Clicking the × button closes the modal.
  - **Expected:** Clicking the dark backdrop closes the modal.
  - **Expected:** Pressing the Escape key closes the modal.
  - **Expected:** After closing, the modal body is cleared (re-opening shows no stale content).

- [ ] **3.11.11** Remove the temporary modal test code.

---

### Test 7: Error Pages

- [ ] **3.11.12** Visit `http://localhost:8000/this-page-does-not-exist`.
  - **Expected:** A 404 page renders inside the error layout: Emirates logo at the top, "Page not found" heading, "Go home" button.
  - **Expected:** The HTTP status code in the browser is 404 (check Network tab in DevTools).
  - **Failure:** If a PHP error renders instead, check that `ErrorHandler::handleException()` catches `NotFoundException` and calls `renderProduction()`.

- [ ] **3.11.13** Temporarily add a route that throws an exception to test the 500 page:
  ```php
  // TEMPORARY
  $router->get('/test-500', function () {
      throw new \RuntimeException('Test 500 error');
  });
  ```
  With `APP_DEBUG=true` in `.env`:
  - **Expected:** The debug view renders: dark background, exception class and message in red, source code snippet, stack trace, and context panels for `$_SERVER`, `$_POST`, `$_GET`, `$_SESSION`.

  Change `APP_DEBUG=false` and reload:
  - **Expected:** The 500 error page renders: "Something went wrong", error reference number.
  - **Expected:** The error is logged to `storage/logs/app-{today}.log`.

- [ ] **3.11.14** Remove the temporary `/test-500` route and restore `APP_DEBUG=true`.

---

### Test 8: HTMX Progress Bar

- [ ] **3.11.15** Add a temporary slow route to test the progress bar:
  ```php
  // TEMPORARY
  $router->get('/test-slow', function () {
      sleep(2); // Simulate a slow response
      return \App\Core\Response::html('<p>Done</p>');
  });
  ```
  Trigger it with an HTMX request from a placeholder view:
  ```html
  <button hx-get="/test-slow" hx-target="#main-content" class="px-4 py-2 bg-slate-700 text-white rounded-lg text-sm mt-2">
      Test Progress Bar
  </button>
  ```
  - **Expected:** After clicking, the thin progress bar at the very top of the page animates from 0% to ~70% width while the request is in flight, then completes to 100% and fades out when the response arrives.
  - **Failure:** If no bar is visible, check that the `#global-loader` element exists in the layout and that the CSS `body.htmx-request #global-loader` selector is correct.

- [ ] **3.11.16** Remove the temporary slow route and test button.

---

### Test 9: Sign Out

- [ ] **3.11.17** Click the "Sign Out" button in the sidebar.
  - **Expected:** The form POSTs to `/logout`. You are redirected to `/login`. The success flash message "You have been signed out successfully." appears.
  - **Failure:** If a CSRF error appears, check that `csrf_field()` is rendered inside the logout form in `_sidebar.php`.

---

### Test 10: Commit

- [ ] **3.11.18** All tests pass. Commit Phase 3:
  ```bash
  git add -A
  git commit -m "Phase 3: App shell — layout, sidebar, bottom nav, toast, modal, error views, stub controllers"
  ```

---

## Phase 3 Complete ✅

**What you have built:**

| Component | Files |
|---|---|
| App layout | `resources/layouts/app.php` — two-column shell, toast container, modal overlay, progress bar |
| Sidebar | `resources/components/navigation/_sidebar.php` — brand, nav links, user info, logout |
| Mobile nav | `resources/components/navigation/_bottom-nav.php` — 4-tab bottom bar |
| Global JS | `assets/js/app.js` — CSRF injection, toast system, modal open/close, chip input, scroll restoration |
| HTMX | `assets/js/htmx.min.js` — downloaded from CDN |
| Empty state | `resources/components/ui/_empty-state.php` |
| Badge | `resources/components/ui/_badge.php` |
| Loader spinner | `resources/components/ui/_loader.php` |
| Pagination | `resources/components/tables/_pagination.php` |
| Sortable header | `resources/components/tables/_sortable-header.php` |
| Error layout | `resources/layouts/error.php` |
| Error views | `resources/error/404.php`, `403.php`, `500.php`, `debug.php` |
| Error handler | `app/Core/ErrorHandler.php` — updated with source snippet, HTMX error, production/debug split |
| Stub controllers | `ComposeController`, `RecipientController`, `LogController`, `TemplateController`, `CredentialController`, `SettingsController` |
| Placeholder views | One per stub controller — renders inside the app shell |
| Routes | `routes/web.php` — all stub routes registered; future phase routes commented in |
| Html helper | `app/Helpers/Html.php` — `active()` method confirmed |
| BaseController | `partial()` method added for HTMX fragment responses |
| `view()` helper | Layout parameter support confirmed/updated |
| Response | `htmxTrigger()` and `htmxRedirect()` methods confirmed |

**The shell milestone:**
```
Authenticated user visits /compose
  → AuthMiddleware confirms session
  → ComposeController::index() runs
  → view('compose/index', [...], 'app') is called
  → resources/compose/index.php renders into $content
  → resources/layouts/app.php wraps it with sidebar + bottom nav
  → HTML returned → Response::html() → Response::send()
  → Browser renders the full authenticated shell

User clicks "Recipients" in the sidebar
  → Standard <a href> navigation
  → Full page load → same flow as above with RecipientController

User performs an HTMX action (future phases)
  → HX-Trigger: showToast in response header
  → app.js catches showToast event
  → Toast appears in top-right corner
  → Auto-dismisses (success) or waits for user (error)
```

**Ready for Phase 4:** General Settings — the settings form, logo upload, file storage service, and StorageController.

---

*End of Emirates Phase 3 Implementation*
