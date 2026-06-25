<?php
/*
 * _metadata.php — Compose form fields (To, Subject, Reply-To)
 *
 * Recipients: plain textarea, one email per line or comma-separated.
 * On submit, a tiny inline script converts the textarea text into the
 * JSON array that ComposeController::send() expects via `name="recipients"`.
 *
 * No chip library. No HTMX autocomplete. No hidden inputs. Just works.
 */

// Convert stored JSON array back to plain text for the textarea (draft reload)
$draftRecipientsRaw = $draft ? $draft->recipientsArray() : [];
$draftRecipientsText = implode(', ', $draftRecipientsRaw);

$draftSubject = $draft?->subject  ?? '';
$draftReplyTo = $draft?->reply_to  ?? '';
?>

<div class="px-6 py-4 space-y-4">

    <!-- To -->
    <div>
        <label for="recipients-textarea"
               class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            To
        </label>
        <p class="text-xs text-slate-400 mb-1.5">
            One email per line, or separate with commas.
        </p>

        <!--
            This textarea is NOT submitted directly.
            The hidden input #recipients-hidden (name="recipients") holds the
            JSON array that the controller reads. The script below converts
            the textarea value → JSON whenever the form is about to be submitted.
        -->
        <textarea
            id="recipients-textarea"
            rows="2"
            placeholder="alice@example.com, bob@example.com"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm
                   text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500
                   resize-none"
        ><?= e($draftRecipientsText) ?></textarea>

        <!-- The actual submitted field — kept in sync by the script below -->
        <input type="hidden" id="recipients-hidden" name="recipients" value="<?= e(json_encode($draftRecipientsRaw)) ?>">
    </div>

    <!-- Subject -->
    <div>
        <label for="subject-input"
               class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">
            Subject
        </label>
        <input type="text" id="subject-input" name="subject"
               value="<?= e($draftSubject) ?>"
               placeholder="Enter email subject"
               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2
                      text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <!-- CC / BCC / Reply-To toggle -->
    <div>
        <button type="button"
                onclick="document.getElementById('advanced-meta').classList.toggle('hidden')"
                class="text-xs font-medium text-blue-600 hover:text-blue-800 transition">
            + Add CC / BCC / Reply-To
        </button>

        <div id="advanced-meta" class="hidden mt-4 space-y-3 p-4 bg-slate-50 rounded-xl border border-slate-100">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Reply-To (optional)</label>
                <input type="email" name="reply_to" value="<?= e($draftReplyTo) ?>"
                       placeholder="reply@domain.com"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2
                              text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var textarea = document.getElementById('recipients-textarea');
    var hidden   = document.getElementById('recipients-hidden');
    var badge    = document.getElementById('recipient-count-badge');

    if (!textarea || !hidden) return;

    /* Parse the textarea text into a clean array of email strings. */
    function parseEmails() {
        return textarea.value
            .split(/[\n,;]+/)
            .map(function (s) { return s.trim(); })
            .filter(function (s) { return s.length > 0; });
    }

    /* Push the array into the hidden input and update the toolbar badge. */
    function sync() {
        var emails = parseEmails();
        hidden.value = JSON.stringify(emails);

        if (badge) {
            badge.textContent = emails.length + ' recipient' + (emails.length !== 1 ? 's' : '');
        }

        /* Keep populateSendSummary() in sync when the send modal opens */
        document.dispatchEvent(new CustomEvent('recipientsUpdated', {
            detail: { chips: emails, count: emails.length }
        }));
    }

    /* Sync on every keystroke so the badge and send-modal are always current. */
    textarea.addEventListener('input', sync);

    /* Sync once immediately on load (covers draft reloads). */
    sync();
})();
</script>