<?php
/**
 * resources/components/compose/_drafts-drawer.php
 *
 * Slide-in drawer that shows the list of saved drafts.
 * Opened by openDraftsDrawer(), closed by closeDraftsDrawer().
 *
 * The drawer content is loaded via HTMX (GET /drafts) when it opens.
 * After any draft save or delete, refreshDraftList event triggers a reload.
 */
?>

<!-- Backdrop -->
<div
    id="drafts-backdrop"
    class="hidden fixed inset-0 z-30 bg-black/30 md:hidden"
    onclick="closeDraftsDrawer()"
></div>

<!-- Drawer -->
<div
    id="drafts-drawer"
    class="fixed top-0 right-0 z-40 h-full w-80 max-w-[90vw] bg-white shadow-2xl
           transform translate-x-full transition-transform duration-300 ease-in-out
           flex flex-col"
    aria-label="Saved drafts"
>
    <!-- Drawer header -->
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <h2 class="text-base font-semibold text-slate-900">Saved Drafts</h2>
        <button
            type="button"
            onclick="closeDraftsDrawer()"
            class="rounded-lg p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition"
            aria-label="Close drafts"
        >
            <i class="bi bi-x-lg text-sm"></i>
        </button>
    </div>

    <!-- Draft list container — HTMX loads content here -->
    <div
        id="draft-list-container"
        class="flex-1 overflow-y-auto"
        hx-get="/drafts"
        hx-trigger="refresh"
        hx-target="#drafts-drawer-content"
        hx-swap="innerHTML"
    >
        <div id="drafts-drawer-content" class="p-4">
            <!-- Content loaded by HTMX -->
            <div class="text-center py-10">
                <svg class="animate-spin h-5 w-5 text-slate-400 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>