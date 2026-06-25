<?php
/**
 * resources/components/compose/_send-modal.php
 *
 * Confirmation dialog before sending.
 * Opened by openSendModal() from compose/index.php.
 *
 * On "Confirm & Send", HTMX POSTs to /compose/send with hx-include="#compose-form".
 * The response replaces #compose-area (resets the form on success) and
 * triggers a showToast event.
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
                onclick="closeSendModal()"
                class="text-sm font-medium text-slate-500 hover:text-slate-700 transition"
            >
                Cancel
            </button>

            <!-- The actual send button — HTMX posts to /compose/send -->
            <button
                type="button"
                id="confirm-send-btn"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold
                       text-white transition hover:opacity-90 focus:outline-none focus:ring-2
                       focus:ring-offset-2"
                style="background-color: var(--color-primary);"
                hx-post="/compose/send"
                hx-include="#compose-form"
                hx-target="#compose-area"
                hx-swap="innerHTML"
                hx-on::before-request="this.disabled=true; this.innerHTML='<i class=\'bi bi-arrow-repeat animate-spin\'></i> Sending…'"
                hx-on::after-request="this.disabled=false; this.innerHTML='<i class=\'bi bi-send-fill\'></i> Confirm & Send'; closeSendModal();"
            >
                <i class="bi bi-send-fill"></i>
                Confirm & Send
            </button>
        </div>
    </div>
</div>