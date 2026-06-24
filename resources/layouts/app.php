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

        /* HTMX indicator styling: hide by default, show on request */
        .htmx-indicator {
            display: none !important;
        }
        .htmx-request .htmx-indicator,
        .htmx-request.htmx-indicator {
            display: flex !important;
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