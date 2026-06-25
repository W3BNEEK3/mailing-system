<?php
$draftRecipients = $draft ? json_encode($draft->recipientsArray()) : '[]';
$draftSubject    = $draft?->subject   ?? '';
$draftReplyTo    = $draft?->replyTo   ?? '';
$draftCc         = $draft ? json_encode($draft->ccArray())  : '[]';
$draftBcc        = $draft ? json_encode($draft->bccArray()) : '[]';
?>

<div class="px-6 py-4 space-y-4">

    <!-- To (chip input) -->
    <div class="relative">
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            To
        </label>

        <input type="hidden" id="recipients-hidden" name="recipients" value="<?= e($draftRecipients) ?>">

        <div id="recipient-chips" data-chip-input data-hidden-name="recipients"
             class="flex flex-wrap items-center gap-1.5 min-h-[42px] px-3 py-1.5 rounded-xl border border-slate-200 bg-white shadow-sm focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition-all cursor-text">
        </div>

        <div id="recipient-autocomplete" class="absolute left-0 right-0 top-full mt-1 z-50"></div>
    </div>

    <!-- FIXED: Restored classic outlined input and label for Subject -->
    <div>
        <label for="subject-input" class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            Subject
        </label>
        <input type="text" id="subject-input" name="subject" value="<?= e($draftSubject) ?>"
               placeholder="Enter Email Subject"
               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- Extra fields toggle -->
    <div>
        <button type="button" onclick="document.getElementById('advanced-meta').classList.toggle('hidden')" class="text-xs font-medium text-blue-600 hover:text-blue-800 transition">
            + Add CC / BCC / Reply-To
        </button>

        <div id="advanced-meta" class="hidden mt-4 space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-100">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Reply-To (optional)</label>
                <input type="email" name="reply_to" value="<?= e($draftReplyTo) ?>" placeholder="reply@domain.com"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var badge = document.getElementById('recipient-count-badge');
    if (!badge) return;

    function updateBadge(count) {
        badge.textContent = count + ' recipient' + (count !== 1 ? 's' : '');
    }

    // MutationObserver on attribute changes does NOT fire when JS sets
    // hiddenInput.value directly (property assignment vs. setAttribute).
    // Instead, listen for the custom 'recipientsUpdated' event fired by
    // the chip input system in app.js whenever the list changes.
    document.addEventListener('recipientsUpdated', function (e) {
        updateBadge(e.detail ? e.detail.count : 0);
    });

    // Initial state (in case the event already fired before this script ran)
    try {
        var hiddenInput = document.getElementById('recipients-hidden');
        var list = JSON.parse((hiddenInput && hiddenInput.value) || '[]');
        updateBadge(Array.isArray(list) ? list.length : 0);
    } catch (e) {
        updateBadge(0);
    }
})();
</script>