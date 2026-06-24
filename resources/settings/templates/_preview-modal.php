<?php
/**
 * resources/settings/templates/_preview-modal.php
 *
 * The template preview modal. Included once in the templates index page.
 *
 * The modal is opened by calling openTemplatePreview(id) from JS (defined below).
 * It fetches GET /settings/templates/{id}/preview, receives the rendered HTML,
 * and sets it as the iframe's srcdoc attribute so the template renders in a
 * fully sandboxed context — it cannot access the parent page's DOM or cookies.
 *
 * Desktop/Mobile toggle: buttons resize the iframe's max-width to simulate
 * 600px (email desktop) vs 375px (iPhone SE / mobile) viewports.
 */
?>

<div
    id="template-preview-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center"
    role="dialog"
    aria-modal="true"
    aria-labelledby="preview-modal-title"
>
    <?php // Backdrop ?>
    <div
        class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
        onclick="closeTemplatePreview()"
    ></div>

    <?php // Modal panel ?>
    <div class="relative z-10 flex flex-col w-full max-w-4xl mx-4 max-h-[90vh]
                bg-white rounded-xl shadow-2xl overflow-hidden">

        <?php // Modal header ?>
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h2 id="preview-modal-title" class="text-base font-semibold text-slate-800">
                Template Preview
            </h2>

            <div class="flex items-center gap-2">
                <?php // Viewport toggle ?>
                <div class="flex rounded-lg border border-slate-200 overflow-hidden">
                    <button
                        type="button"
                        id="preview-desktop-btn"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium
                               bg-slate-800 text-white transition"
                        onclick="setPreviewViewport('desktop')"
                        title="Desktop preview (600px)"
                    >
                        <i class="bi bi-display"></i> Desktop
                    </button>
                    <button
                        type="button"
                        id="preview-mobile-btn"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium
                               bg-white text-slate-600 hover:bg-slate-50 transition"
                        onclick="setPreviewViewport('mobile')"
                        title="Mobile preview (375px)"
                    >
                        <i class="bi bi-phone"></i> Mobile
                    </button>
                </div>

                <?php // Close ?>
                <button
                    type="button"
                    class="rounded-md p-1.5 text-slate-500 hover:bg-slate-100 transition"
                    onclick="closeTemplatePreview()"
                    aria-label="Close preview"
                >
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>
        </div>

        <?php // Preview area ?>
        <div class="flex-1 overflow-auto bg-slate-100 p-6 flex justify-center">
            <?php // Loading spinner (visible while fetching) ?>
            <div id="preview-loader" class="flex items-center justify-center w-full py-20">
                <svg class="animate-spin h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>

            <?php // Sandboxed iframe ?>
            <iframe
                id="preview-iframe"
                class="hidden w-full border-0 rounded-lg shadow-md bg-white transition-all duration-200"
                style="max-width:600px;min-height:500px;height:600px;"
                sandbox="allow-same-origin"
                title="Email template preview"
            ></iframe>
        </div>
    </div>
</div>

<script>
/**
 * Open the preview modal for a specific template ID.
 * Fetches the rendered HTML from the server and sets it as the iframe srcdoc.
 */
async function openTemplatePreview(templateId) {
    const modal   = document.getElementById('template-preview-modal');
    const iframe  = document.getElementById('preview-iframe');
    const loader  = document.getElementById('preview-loader');

    // Show modal + spinner, hide iframe
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loader.classList.remove('hidden');
    iframe.classList.add('hidden');
    iframe.srcdoc = '';

    try {
        const response = await fetch(`/settings/templates/${templateId}/preview`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
            throw new Error(`Server returned ${response.status}`);
        }

        const html = await response.text();
        iframe.srcdoc = html;

        // Wait for iframe to load before showing it
        iframe.onload = () => {
            loader.classList.add('hidden');
            iframe.classList.remove('hidden');
        };
    } catch (err) {
        loader.innerHTML = `
            <p class="text-sm text-red-600">
                <i class="bi bi-exclamation-triangle-fill mr-1"></i>
                Failed to load preview: ${err.message}
            </p>`;
    }
}

/**
 * Close and reset the preview modal.
 */
function closeTemplatePreview() {
    const modal  = document.getElementById('template-preview-modal');
    const iframe = document.getElementById('preview-iframe');

    modal.classList.add('hidden');
    modal.classList.remove('flex');
    iframe.srcdoc = '';
    iframe.classList.add('hidden');
    document.getElementById('preview-loader').classList.remove('hidden');
}

/**
 * Toggle between desktop (600px) and mobile (375px) preview widths.
 */
function setPreviewViewport(mode) {
    const iframe      = document.getElementById('preview-iframe');
    const desktopBtn  = document.getElementById('preview-desktop-btn');
    const mobileBtn   = document.getElementById('preview-mobile-btn');

    if (mode === 'mobile') {
        iframe.style.maxWidth = '375px';
        mobileBtn.classList.add('bg-slate-800', 'text-white');
        mobileBtn.classList.remove('bg-white', 'text-slate-600');
        desktopBtn.classList.remove('bg-slate-800', 'text-white');
        desktopBtn.classList.add('bg-white', 'text-slate-600');
    } else {
        iframe.style.maxWidth = '600px';
        desktopBtn.classList.add('bg-slate-800', 'text-white');
        desktopBtn.classList.remove('bg-white', 'text-slate-600');
        mobileBtn.classList.remove('bg-slate-800', 'text-white');
        mobileBtn.classList.add('bg-white', 'text-slate-600');
    }
}

// Close on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeTemplatePreview();
});
</script>
