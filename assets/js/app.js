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
            bg: 'bg-emerald-50 border-emerald-200',
            text: 'text-emerald-800',
            icon: 'bi-check-circle-fill text-emerald-500',
        },
        error: {
            bg: 'bg-red-50 border-red-200',
            text: 'text-red-800',
            icon: 'bi-exclamation-circle-fill text-red-500',
        },
        warning: {
            bg: 'bg-amber-50 border-amber-200',
            text: 'text-amber-800',
            icon: 'bi-exclamation-triangle-fill text-amber-500',
        },
        info: {
            bg: 'bg-blue-50 border-blue-200',
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
        s.bg,
        s.text,
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
    var autoDismissMs = type === 'error' ? null: (type === 'warning' ? 5000: 4000);

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
    },
        220);
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
    var body = document.getElementById('modal-body');

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
window.openModal = openModal;
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


/* 4. RECIPIENT CHIP INPUT — replaced by plain textarea in _metadata.php. No JS needed here. */

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
        window.scrollTo({
            top: 0, behavior: 'smooth'
        });
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
    loader.style.width = '100%';
    loader.style.opacity = '1';

    setTimeout(function () {
        loader.style.opacity = '0';
        setTimeout(function () {
            // Reset back to initial state for next request
            loader.style.transition = '';
            loader.style.width = '0%';
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
    var map = {
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    };
    return String(str).replace(/[&<>"']/g,
        function (c) {
            return map[c];
        });
}