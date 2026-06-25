<?php
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
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">

    <title><?= e($pageTitle ?? $siteName) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://unpkg.com/htmx.org@1.9.12"></script>

    <!-- TinyMCE -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        :root {
            --color-primary:   <?= e($primaryColor) ?>;
            --color-secondary: <?= e($secondaryColor) ?>;
        }

        #global-loader {
            position: fixed; top: 0; left: 0; width: 0%; height: 3px;
            background: var(--color-primary); z-index: 9999;
            transition: width 0.3s ease, opacity 0.3s ease; opacity: 0;
        }

        body.htmx-request #global-loader { width: 70%; opacity: 1; }

        .htmx-indicator { display: none !important; }
        .htmx-request .htmx-indicator,
        .htmx-request.htmx-indicator { display: flex !important; }

        .nav-link.active {
            background-color: color-mix(in srgb, var(--color-primary) 12%, transparent);
            color: var(--color-primary);
        }
        .nav-link.active .nav-icon { color: var(--color-primary); }

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

        #modal-overlay { transition: opacity 0.2s ease; }
        #modal-overlay.hidden { pointer-events: none; }

        @media (max-width: 767px) {
            #main-content { padding-bottom: 5rem; }
        }

        /* TinyMCE Customization */
        .tox-tinymce {
            border-radius: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
        }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-900 flex">

    <div id="global-loader" role="progressbar" aria-hidden="true"></div>

    <div class="hidden md:flex md:w-60 md:flex-col md:fixed md:inset-y-0 md:z-30">
        <?php require BASE_PATH . '/resources/components/navigation/_sidebar.php'; ?>
    </div>

    <div id="main-content" class="flex-1 md:ml-60 min-h-screen overflow-y-auto">
        <main class="p-4 md:p-6 lg:p-8 max-w-screen-xl mx-auto">
            <?= $content ?>
        </main>
    </div>

    <div class="md:hidden">
        <?php require BASE_PATH . '/resources/components/navigation/_bottom-nav.php'; ?>
    </div>

    <div id="toast-container" aria-live="polite" aria-atomic="false"
         class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]
                max-sm:right-1/2 max-sm:translate-x-1/2"></div>

    <div id="modal-overlay" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
         role="dialog" aria-modal="true" aria-labelledby="modal-title">
        <div id="modal-panel" class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                <h2 id="modal-title" class="text-base font-semibold text-slate-900"></h2>
                <button type="button" onclick="closeModal()" aria-label="Close modal"
                        class="flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <i class="bi bi-x-lg text-sm"></i>
                </button>
            </div>
            <div id="modal-body" class="overflow-y-auto p-6"></div>
        </div>
    </div>

    <script>
        document.addEventListener('htmx:configRequest', function (event) {
            var token = document.querySelector('meta[name="csrf-token"]');
            if (token) {
                event.detail.headers['X-CSRF-Token'] = token.getAttribute('content');
            }
        });

        document.addEventListener('showToast', function (event) {
            var detail = event.detail || {};
            showToast(detail.type || 'info', detail.message || '');
        });

        function showToast(type, message) {
            var container = document.getElementById('toast-container');
            if (!container) return;

            var styles = {
                success: { bg: 'bg-emerald-50 border-emerald-200', text: 'text-emerald-800', icon: 'bi-check-circle-fill text-emerald-500' },
                error: { bg: 'bg-red-50 border-red-200', text: 'text-red-800', icon: 'bi-exclamation-circle-fill text-red-500' },
                warning: { bg: 'bg-amber-50 border-amber-200', text: 'text-amber-800', icon: 'bi-exclamation-triangle-fill text-amber-500' },
                info: { bg: 'bg-blue-50 border-blue-200', text: 'text-blue-800', icon: 'bi-info-circle-fill text-blue-500' }
            };

            var s = styles[type] || styles.info;
            var toast = document.createElement('div');
            toast.setAttribute('role', 'alert');
            toast.className = 'flex items-start gap-3 w-full rounded-xl border px-4 py-3 shadow-sm toast-enter ' + s.bg + ' ' + s.text;

            toast.innerHTML = '<i class="bi ' + s.icon + ' text-base flex-shrink-0 mt-0.5"></i>' +
                '<p class="flex-1 text-sm font-medium leading-snug">' + escapeHtml(message) + '</p>' +
                '<button type="button" aria-label="Dismiss" class="flex-shrink-0 text-current opacity-50 hover:opacity-80 transition ml-1" onclick="dismissToast(this.parentElement)"><i class="bi bi-x-lg text-xs"></i></button>';

            container.appendChild(toast);
            var autoDismissMs = type === 'error' ? null : (type === 'warning' ? 5000 : 4000);
            if (autoDismissMs !== null) {
                setTimeout(function () { dismissToast(toast); }, autoDismissMs);
            }
        }

        function dismissToast(toastEl) {
            if (!toastEl || !toastEl.parentElement) return;
            toastEl.classList.remove('toast-enter');
            toastEl.classList.add('toast-leave');
            setTimeout(function () {
                if (toastEl.parentElement) toastEl.parentElement.removeChild(toastEl);
            }, 220);
        }
        window.dismissToast = dismissToast;

        function openModal(title) {
            var overlay = document.getElementById('modal-overlay');
            var titleEl = document.getElementById('modal-title');
            if (!overlay) return;
            if (title && titleEl) titleEl.textContent = title;
            overlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            var overlay = document.getElementById('modal-overlay');
            var body = document.getElementById('modal-body');
            if (!overlay) return;
            overlay.classList.add('hidden');
            document.body.style.overflow = '';
            if (body) body.innerHTML = '';
        }
        window.openModal = openModal;
        window.closeModal = closeModal;

        document.addEventListener('click', function (event) {
            var overlay = document.getElementById('modal-overlay');
            if (event.target === overlay) closeModal();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeModal();
        });

        function initChipInput(container) {
            var hiddenInput = document.querySelector('input[name="' + container.dataset.hiddenName + '"]');
            if (!hiddenInput) return;
            var chips = [];

            var input = document.createElement('input');
            input.type = 'text';
            input.placeholder = 'Email or group name…';
            input.className = 'flex-1 min-w-[180px] outline-none bg-transparent text-sm py-1';
            container.appendChild(input);

            function addChip(value) {
                value = value.trim();
                if (!value || chips.includes(value)) return;
                chips.push(value);
                hiddenInput.value = JSON.stringify(chips);
                var chip = document.createElement('span');
                chip.className = 'inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium mt-1 mb-1';
                chip.innerHTML = escapeHtml(value) + '<button type="button" class="flex items-center text-slate-400 hover:text-slate-700 transition"><i class="bi bi-x text-sm leading-none"></i></button>';
                chip.querySelector('button').addEventListener('click', function () {
                    chips = chips.filter(function (c) { return c !== value; });
                    hiddenInput.value = JSON.stringify(chips);
                    container.removeChild(chip);
                });
                container.insertBefore(chip, input);
                input.value = '';
            }

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ',') {
                    event.preventDefault(); addChip(input.value.replace(/,/g, ''));
                }
                if (event.key === 'Backspace' && input.value === '' && chips.length > 0) {
                    var last = chips[chips.length - 1];
                    var lastChipEl = container.querySelector('span:last-of-type');
                    chips = chips.filter(function (c) { return c !== last; });
                    hiddenInput.value = JSON.stringify(chips);
                    if (lastChipEl) container.removeChild(lastChipEl);
                }
            });

            input.setAttribute('hx-get', '/compose/recipient-hints');
            input.setAttribute('hx-trigger', 'keyup changed delay:300ms');
            input.setAttribute('hx-target', '#recipient-autocomplete');
            input.setAttribute('hx-swap', 'innerHTML');
            input.setAttribute('name', 'q');
            if (window.htmx) htmx.process(input);
        }

        document.addEventListener('htmx:afterSwap', function (event) {
            var target = event.detail.target;
            if (target && (target.id === 'main-content' || target.tagName === 'BODY')) window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.addEventListener('htmx:afterRequest', function () {
            var loader = document.getElementById('global-loader');
            if (!loader) return;
            loader.style.transition = 'width 0.1s ease, opacity 0.3s ease 0.15s';
            loader.style.width = '100%';
            loader.style.opacity = '1';
            setTimeout(function () {
                loader.style.opacity = '0';
                setTimeout(function () { loader.style.transition = ''; loader.style.width = '0%'; }, 350);
            }, 150);
        });

        function escapeHtml(str) {
            var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(str).replace(/[&<>"']/g, function (c) { return map[c]; });
        }

        function init() {
            document.querySelectorAll('[data-chip-input]').forEach(function (el) {
                if (!el.dataset.chipInitialised) {
                    el.dataset.chipInitialised = 'true';
                    initChipInput(el);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', init);
        document.addEventListener('htmx:afterSwap', init);
    </script>
</body>
</html>