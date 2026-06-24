<?php
/**
 * resources/settings/_credential-test-result.php
 *
 * HTMX partial — returned by CredentialController::test().
 * Replaces the content of #test-result-{provider} in the DOM.
 *
 * @var bool   $success   Whether the connection test passed
 * @var string $message   Human-readable result message
 * @var string $provider  'resend' or 'smtp'
 */
?>

<div class="flex items-start gap-2.5 rounded-lg border p-3 text-sm
    <?= $success
        ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
        : 'border-red-200    bg-red-50    text-red-800'
    ?>">
    <i class="bi <?= $success ? 'bi-check-circle-fill text-emerald-500' : 'bi-x-circle-fill text-red-500' ?> text-base flex-shrink-0 mt-0.5"></i>
    <p class="leading-snug"><?= e($message) ?></p>
</div>
