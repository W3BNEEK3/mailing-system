<?php
$draft = $draft ?? null;
$flashToast = session()->getFlash('_toast');
?>

<?php if ($flashToast): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.dispatchEvent(new CustomEvent('showToast', {
            detail: <?= json_encode($flashToast) ?>
        }));
    });
</script>
<?php endif; ?>

<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Compose</h1>
            <p class="text-sm text-slate-500 mt-0.5">Write, preview, and send your email.</p>
        </div>
        <button type="button" hx-get="/drafts" hx-target="#drafts-drawer-content" hx-swap="innerHTML" onclick="openDraftsDrawer()"
            class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
            <i class="bi bi-journal-text"></i> Drafts
        </button>
    </div>

    <div id="compose-form">
        <input type="hidden" id="draft-id-input" name="draft_id" value="<?= $draft ? $draft->id : '' ?>">
        <input type="hidden" id="email-logo-path-input" name="email_logo_path" value="<?= e($draft?->emailLogoPath ?? '') ?>">
        <input type="hidden" id="primary-color-input" name="primary_color" value="<?= e($draft?->primaryColor ?? '') ?>">
        <input type="hidden" id="secondary-color-input" name="secondary_color" value="<?= e($draft?->secondaryColor ?? '') ?>">

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <?= component('compose/_toolbar', ['templates' => $templates, 'globalContext' => $globalContext, 'draft' => $draft]) ?>
            
            <div class="divide-y divide-slate-100">
                <?= component('compose/_metadata', ['draft' => $draft]) ?>
                <div id="compose-area">
                    <?= component('compose/_editor', ['bodyHtml' => $draft?->bodyHtml ?? '', 'templateId' => $draft?->templateId ?? null]) ?>
                </div>
                <div id="translation-controls"></div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-100 gap-4">
                <div class="order-2 sm:order-1 text-center sm:text-left">
                    <span id="autosave-status" class="text-xs text-slate-400">
                        <?= $draft ? $draft->savedAgo() : 'Not saved yet' ?>
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 order-1 sm:order-2">
                    <button type="button" hx-post="/drafts" hx-include="#compose-form" hx-target="#autosave-status" hx-swap="outerHTML"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                        <i class="bi bi-floppy"></i> Save Draft
                    </button>

                    <button type="button" hx-post="/compose/preview" hx-include="#compose-form" hx-target="#preview-modal-body" hx-swap="innerHTML"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-slate-200 text-sm font-medium text-slate-600 hover:bg-slate-50 transition">
                        <i class="bi bi-eye"></i> Preview
                    </button>

                    <button type="button" onclick="openSendModal()"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold text-white transition hover:opacity-90"
                        style="background-color: var(--color-primary);">
                        <i class="bi bi-send-fill"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div hx-post="/drafts/autosave" hx-include="#compose-form" hx-target="#autosave-status" hx-swap="outerHTML" hx-trigger="every 60s" aria-hidden="true"></div>
</div>

<?= component('compose/_send-modal') ?>
<?= component('compose/_preview-modal') ?>
<?= component('compose/_drafts-drawer') ?>
<?= component('compose/_colour-picker') ?>

<script>
    document.addEventListener('draftSaved', function (event) {
        var draftId = event.detail.draftId;
        if (draftId) document.getElementById('draft-id-input').value = draftId;
    });

    document.addEventListener('composeSent', function () {
        document.getElementById('draft-id-input').value = '';
    });

    document.body.addEventListener('htmx:afterRequest', function (event) {
        var elt = event.detail.elt;
        if (elt && elt.getAttribute('hx-post') === '/compose/preview') {
            if (event.detail.successful) {
                var overlay = document.getElementById('compose-preview-overlay');
                var iframe = document.getElementById('compose-preview-iframe');
                if (overlay && iframe) {
                    iframe.srcdoc = event.detail.xhr.responseText;
                    overlay.classList.remove('hidden');
                    overlay.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                }
            }
        }
    });

    function closePreviewModal() {
        var overlay = document.getElementById('compose-preview-overlay');
        if (overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
            document.body.style.overflow = '';
        }
    }

    function openSendModal() {
        document.getElementById('send-modal-overlay').classList.remove('hidden');
        document.getElementById('send-modal-overlay').classList.add('flex');
        document.body.style.overflow = 'hidden';
        populateSendSummary();
    }

    function closeSendModal() {
        document.getElementById('send-modal-overlay').classList.add('hidden');
        document.getElementById('send-modal-overlay').classList.remove('flex');
        document.body.style.overflow = '';
    }

    function populateSendSummary() {
        var subject = document.getElementById('subject-input')?.value || '(No subject)';
        var recipients = JSON.parse(document.getElementById('recipients-hidden')?.value || '[]');
        var summary = document.getElementById('send-summary');
        if (summary) {
            summary.innerHTML = '<div class="text-sm text-slate-600 space-y-1">'
            + '<p><span class="font-medium text-slate-800">To: </span>'
            + (recipients.length > 0 ? '<span class="text-slate-600">' + recipients.length + ' recipient' + (recipients.length > 1 ? 's': '') + '</span>': '<span class="text-red-500">No recipients added</span>')
            + '</p><p><span class="font-medium text-slate-800">Subject: </span>'
            + escapeHtml(subject) + '</p></div>';
        }
    }

    function openDraftsDrawer() {
        var drawer = document.getElementById('drafts-drawer');
        if (drawer) {
            drawer.classList.remove('translate-x-full');
            drawer.classList.add('translate-x-0');
        }
    }

    function closeDraftsDrawer() {
        var drawer = document.getElementById('drafts-drawer');
        if (drawer) {
            drawer.classList.remove('translate-x-0');
            drawer.classList.add('translate-x-full');
        }
    }

    function toggleColourPicker() {
        var picker = document.getElementById('colour-picker-popover');
        if (picker) picker.classList.toggle('hidden');
    }

    function updateColour(inputId, swatchId, value) {
        var input = document.getElementById(inputId);
        var swatch = document.getElementById(swatchId);
        if (input) input.value = value;
        if (swatch) swatch.style.backgroundColor = value;
    }

    function escapeHtml(str) {
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(str).replace(/[&<>"']/g, function(c) { return map[c]; });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSendModal();
            closePreviewModal();
            closeDraftsDrawer();
        }
    });

    document.addEventListener('click', function (e) {
        var picker = document.getElementById('colour-picker-popover');
        var trigger = document.getElementById('colour-picker-trigger');
        if (picker && !picker.contains(e.target) && !trigger?.contains(e.target)) {
            picker.classList.add('hidden');
        }
    });

    document.addEventListener('refreshDraftList', function () {
        htmx.trigger('#draft-list-container', 'refresh');
    });
</script>