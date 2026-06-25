<?php
/**
 * resources/components/compose/_preview-modal.php
 *
 * Email preview modal — renders the body in a sandboxed iframe.
 * Opened by openPreviewModal() after the HTMX preview request completes.
 * The Preview button's hx-target="#preview-modal-body" loads the rendered HTML
 * into the div, then openPreviewModal() moves it to the iframe's srcdoc.
 */
?>

<!-- Hidden relay div — HTMX loads preview HTML here -->
<div id="preview-modal-body" class="hidden" aria-hidden="true"></div>

<div
    id="compose-preview-overlay"
    class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 px-4"
    role="dialog"
    aria-modal="true"
    aria-label="Email preview"
>
    <div class="relative flex flex-col w-full max-w-3xl mx-4 max-h-[90vh]
                bg-white rounded-2xl shadow-2xl overflow-hidden">

        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h2 class="text-base font-semibold text-slate-800">Email Preview</h2>
            <div class="flex items-center gap-2">
                <!-- Viewport toggle -->
                <div class="flex rounded-lg border border-slate-200 overflow-hidden">
                    <button type="button"
                            class="px-3 py-1.5 text-xs font-medium bg-slate-800 text-white"
                            onclick="setPreviewWidth('600px')" id="preview-desktop-btn">
                        <i class="bi bi-display"></i> Desktop
                    </button>
                    <button type="button"
                            class="px-3 py-1.5 text-xs font-medium bg-white text-slate-600 hover:bg-slate-50"
                            onclick="setPreviewWidth('375px')" id="preview-mobile-btn">
                        <i class="bi bi-phone"></i> Mobile
                    </button>
                </div>
                <button type="button"
                        onclick="closePreviewModal()"
                        class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
                        aria-label="Close preview">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>
        </div>

        <!-- Preview area -->
        <div class="flex-1 overflow-auto bg-slate-100 p-6 flex justify-center">
            <iframe
                id="compose-preview-iframe"
                class="w-full border-0 rounded-lg bg-white shadow-sm transition-all duration-200"
                style="max-width:600px;min-height:500px;"
                sandbox="allow-same-origin"
                title="Email preview"
            ></iframe>
        </div>
    </div>
</div>

<script>
function setPreviewWidth(width) {
    document.getElementById('compose-preview-iframe').style.maxWidth = width;
    var isDesktop = width === '600px';
    document.getElementById('preview-desktop-btn').className =
        'px-3 py-1.5 text-xs font-medium ' + (isDesktop ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-50');
    document.getElementById('preview-mobile-btn').className =
        'px-3 py-1.5 text-xs font-medium ' + (!isDesktop ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-50');
}
</script>