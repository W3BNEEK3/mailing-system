<?php
/**
 * resources/components/compose/_send-modal.php
 *
 * Confirmation dialog before sending.
 *
 * Fix: replaced hx-on::after-request (which fires on BOTH success and failure,
 * closing the modal before the error toast can render) with separate
 * hx-on::htmx:after-on-load for success and manual error handling.
 *
 * Simpler approach: use htmx response events properly, or just do a plain
 * fetch() POST instead of HTMX on this one button so we have full control.
 */
?>

<div
    id="send-modal-overlay"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-black/50 px-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="send-modal-title"
>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
            <h2 id="send-modal-title" class="text-base font-semibold text-slate-900">
                Confirm Send
            </h2>
            <button
                type="button"
                onclick="closeSendModal()"
                class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                aria-label="Cancel"
            >
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>

        <!-- Summary -->
        <div class="px-6 py-5">
            <p class="text-sm text-slate-600 mb-4">
                Review the details below before sending.
            </p>
            <div id="send-summary" class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
                <!-- Populated by populateSendSummary() in index.php -->
            </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-100">
            <button
                type="button"
                id="cancel-send-btn"
                onclick="closeSendModal()"
                class="text-sm font-medium text-slate-500 hover:text-slate-700 transition"
            >
                Cancel
            </button>

            <button
                type="button"
                id="confirm-send-btn"
                onclick="doSend()"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold
                       text-white transition hover:opacity-90 focus:outline-none focus:ring-2
                       focus:ring-offset-2"
                style="background-color: var(--color-primary);"
            >
                <i class="bi bi-send-fill"></i>
                Confirm & Send
            </button>
        </div>
    </div>
</div>

<script>
function doSend() {
    var btn = document.getElementById('confirm-send-btn');
    var cancelBtn = document.getElementById('cancel-send-btn');

    // --- Collect form data from #compose-form ---
    var form = document.getElementById('compose-form');
    if (!form) {
        showToast('error', 'Could not find compose form.');
        return;
    }

    // Sync recipients textarea → hidden input right before sending
    var textarea = document.getElementById('recipients-textarea');
    var hidden   = document.getElementById('recipients-hidden');
    if (textarea && hidden) {
        var emails = textarea.value
            .split(/[\n,;]+/)
            .map(function(s) { return s.trim(); })
            .filter(function(s) { return s.length > 0; });
        hidden.value = JSON.stringify(emails);
    }

    // Flush TinyMCE to its underlying textarea before collecting form data
    if (window.tinymce) {
        tinymce.triggerSave();
    }

    // Build FormData from all named inputs/textareas inside #compose-form
    var formData = new FormData();
    form.querySelectorAll('input[name], textarea[name], select[name]').forEach(function(el) {
        if (el.name) formData.append(el.name, el.value);
    });

    // Add CSRF token
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) formData.append('_csrf', csrfMeta.getAttribute('content'));

    // --- Disable button and show spinner ---
    btn.disabled = true;
    cancelBtn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i> Sending…';

    fetch('/compose/send', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin',
        headers: {
            'HX-Request': 'true',   // so the controller knows it's HTMX-style
            'X-CSRF-Token': csrfMeta ? csrfMeta.getAttribute('content') : ''
        }
    })
    .then(function(response) {
        // Read HX-Trigger header for toasts BEFORE closing modal
        var hxTrigger = response.headers.get('HX-Trigger');

        return response.text().then(function(html) {
            return { html: html, hxTrigger: hxTrigger, ok: response.ok, status: response.status };
        });
    })
    .then(function(result) {
        // Fire any HX-Trigger events (showToast, composeSent, promptSaveRecipients, etc.)
        if (result.hxTrigger) {
            try {
                var triggers = JSON.parse(result.hxTrigger);
                Object.keys(triggers).forEach(function(eventName) {
                    document.dispatchEvent(new CustomEvent(eventName, {
                        detail: triggers[eventName] === true ? {} : triggers[eventName]
                    }));
                });
            } catch(e) {
                // Single event name (non-JSON string)
                document.dispatchEvent(new CustomEvent(result.hxTrigger, { detail: {} }));
            }
        }

        // If response body has HTML (success: form reset partial), swap #compose-area
        if (result.html && result.html.trim() !== '') {
            var composeArea = document.getElementById('compose-area');
            if (composeArea) {
                composeArea.innerHTML = result.html;
                // Run any <script> tags in the injected HTML
                composeArea.querySelectorAll('script').forEach(function(oldScript) {
                    var newScript = document.createElement('script');
                    newScript.textContent = oldScript.textContent;
                    document.body.appendChild(newScript);
                    document.body.removeChild(newScript);
                });
            }

            // Clear the recipients textarea and subject on success
            if (textarea) textarea.value = '';
            if (hidden)   hidden.value = '[]';
            var subjectInput = document.getElementById('subject-input');
            if (subjectInput) subjectInput.value = '';

            // Close the modal only on success
            closeSendModal();
        }
        // On error (empty body + error toast): keep the modal open so user sees the error
    })
    .catch(function(err) {
        // Network-level failure
        showToast('error', 'Network error — please check your connection and try again.');
        console.error('Send fetch error:', err);
    })
    .finally(function() {
        btn.disabled = false;
        cancelBtn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill"></i> Confirm & Send';
    });
}
</script>