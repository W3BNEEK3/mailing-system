<?php
/**
 * resources/components/compose/_metadata.php
 *
 * The "envelope" fields: To (chip input), Subject, and collapsed CC/BCC/Reply-To.
 *
 * @var \App\Models\EmailDraft|null  $draft  Active draft (or null for fresh compose)
 *
 * The chip input for "To" is powered by initChipInput() in app.js.
 * It stores JSON-encoded values in #recipients-hidden.
 */

$draftRecipients = $draft ? json_encode($draft->recipientsArray()) : '[]';
$draftSubject    = $draft?->subject   ?? '';
$draftReplyTo    = $draft?->replyTo   ?? '';
$draftCc         = $draft ? json_encode($draft->ccArray())  : '[]';
$draftBcc        = $draft ? json_encode($draft->bccArray()) : '[]';
?>

<div class="px-6 py-4 space-y-4">

    <!-- To (chip input) -->
    <div>
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            To
        </label>

        <!-- Hidden input that stores the chip values as JSON -->
        <input
            type="hidden"
            id="recipients-hidden"
            name="recipients"
            value="<?= e($draftRecipients) ?>"
        >

        <!-- Chip container — initialised by app.js initChipInput() -->
        <div
            id="recipient-chips"
            data-chip-input
            data-hidden-name="recipients"
            class="flex flex-wrap items-center gap-1.5 min-h-[44px] w-full rounded-lg
                   border border-slate-200 bg-white px-3 py-2 cursor-text
                   focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500"
        >
            <!--
                Chips are injected here by app.js initChipInput().
                The function reads the data-hidden-name attribute to find
                the corresponding hidden input and pre-populates chips
                from its current JSON value.
            -->
        </div>

        <!-- Autocomplete dropdown (HTMX-populated) -->
        <div id="recipient-autocomplete" class="relative">
            <!-- Populated via HTMX keyup on the chip input text field -->
        </div>
    </div>

    <!-- Subject -->
    <div>
        <label for="subject-input" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            Subject
        </label>
        <input
            type="text"
            id="subject-input"
            name="subject"
            value="<?= e($draftSubject) ?>"
            placeholder="Email subject…"
            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                   text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
            maxlength="500"
        >
    </div>

    <!-- CC / BCC / Reply-To — collapsed by default -->
    <div>
        <button
            type="button"
            id="cc-toggle"
            class="text-xs text-blue-600 hover:text-blue-800 font-medium"
            onclick="document.getElementById('cc-panel').classList.toggle('hidden');
                     this.textContent = this.textContent.includes('Show')
                         ? 'Hide CC / BCC / Reply-To'
                         : 'Show CC / BCC / Reply-To';"
        >
            <?= ($draftCc !== '[]' || $draftBcc !== '[]' || $draftReplyTo !== '') ? 'Hide' : 'Show' ?>
            CC / BCC / Reply-To
        </button>

        <div
            id="cc-panel"
            class="<?= ($draftCc !== '[]' || $draftBcc !== '[]' || $draftReplyTo !== '') ? '' : 'hidden' ?> mt-3 space-y-3"
        >
            <!-- CC -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">CC</label>
                <input type="hidden" id="cc-hidden" name="cc" value="<?= e($draftCc) ?>">
                <div
                    data-chip-input
                    data-hidden-name="cc"
                    class="flex flex-wrap items-center gap-1.5 min-h-[38px] w-full rounded-lg
                           border border-slate-200 bg-white px-3 py-2 cursor-text
                           focus-within:ring-2 focus-within:ring-blue-500"
                ></div>
            </div>

            <!-- BCC -->
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">BCC</label>
                <input type="hidden" id="bcc-hidden" name="bcc" value="<?= e($draftBcc) ?>">
                <div
                    data-chip-input
                    data-hidden-name="bcc"
                    class="flex flex-wrap items-center gap-1.5 min-h-[38px] w-full rounded-lg
                           border border-slate-200 bg-white px-3 py-2 cursor-text
                           focus-within:ring-2 focus-within:ring-blue-500"
                ></div>
            </div>

            <!-- Reply-To -->
            <div>
                <label for="reply-to-input" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">
                    Reply-To
                </label>
                <input
                    type="email"
                    id="reply-to-input"
                    name="reply_to"
                    value="<?= e($draftReplyTo) ?>"
                    placeholder="reply@domain.com"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm
                           text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>
        </div>
    </div>

</div>

<script>
/**
 * Update the recipient count badge in the toolbar when the chip input changes.
 * Listen for changes to the recipients hidden input.
 */
(function () {
    var hiddenInput = document.getElementById('recipients-hidden');
    var badge       = document.getElementById('recipient-count-badge');
    if (!hiddenInput || !badge) return;

    function updateBadge() {
        try {
            var list  = JSON.parse(hiddenInput.value || '[]');
            var count = Array.isArray(list) ? list.length : 0;
            badge.textContent = count + ' recipient' + (count !== 1 ? 's' : '');
        } catch (e) {
            badge.textContent = '0 recipients';
        }
    }

    // Use a MutationObserver to watch for value changes on the hidden input
    new MutationObserver(updateBadge).observe(hiddenInput, { attributes: true, attributeFilter: ['value'] });

    // Also run on load in case there are pre-populated recipients from a draft
    updateBadge();
})();
</script>