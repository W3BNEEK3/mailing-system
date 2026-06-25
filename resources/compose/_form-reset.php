<?php
/**
 * resources/compose/_form-reset.php
 *
 * Returned by ComposeController::send() on a successful send.
 * Replaces #compose-area with a blank editor (no template pre-selected,
 * body empty, template_id cleared), effectively resetting the form
 * for the next compose session.
 */
?>

<?= component('compose/_editor', [
    'bodyHtml'   => '',
    'templateId' => null,
]) ?>

<script>
// Clear all compose form fields after a successful send
(function () {
    // Clear recipients
    var recipientsHidden = document.getElementById('recipients-hidden');
    if (recipientsHidden) recipientsHidden.value = '[]';

    // Clear chip containers
    ['recipient-chips'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) {
            // Remove all chip spans (keep only the text input child)
            Array.from(el.querySelectorAll('span')).forEach(function (s) { s.remove(); });
        }
    });

    // Clear subject
    var subjectInput = document.getElementById('subject-input');
    if (subjectInput) subjectInput.value = '';

    // Clear template selector
    var templateSelector = document.getElementById('template-selector');
    if (templateSelector) templateSelector.value = '';

    // Clear hidden fields
    ['template-id-input', 'email-logo-path-input', 'primary-color-input', 'secondary-color-input'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });

    // Update recipient count badge
    var badge = document.getElementById('recipient-count-badge');
    if (badge) badge.textContent = '0 recipients';

    // Reset autosave status
    var status = document.getElementById('autosave-status');
    if (status) status.textContent = 'Not saved yet';
})();
</script>