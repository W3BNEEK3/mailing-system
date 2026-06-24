<?php
/**
 * resources/components/ui/_loader.php
 *
 * Inline spinner used as an HTMX hx-indicator target.
 *
 * Usage in a view:
 *   <button
 *     hx-post="/send"
 *     hx-indicator="#send-spinner"
 *   >
 *     Send
 *     <?php require BASE_PATH . '/resources/components/ui/_loader.php'; ?>
 *   </button>
 *
 * HTMX adds the 'htmx-request' class to the indicator element while the
 * request is in flight. The spinner is hidden by default (opacity-0) and
 * visible only during requests (htmx-request:opacity-100).
 *
 * Variables:
 *   string $id   The element ID (used in hx-indicator="#id"). Default: 'spinner'
 *   string $size Tailwind size classes (default: 'w-4 h-4')
 */

$id   = $id   ?? 'spinner';
$size = $size ?? 'w-4 h-4';
?>

<span
    id="<?= e($id) ?>"
    class="htmx-indicator inline-block <?= e($size) ?>"
    aria-hidden="true"
>
    <svg
        class="animate-spin <?= e($size) ?> text-current opacity-0 htmx-request:opacity-100"
        fill="none"
        viewBox="0 0 24 24"
        xmlns="http://www.w3.org/2000/svg"
    >
        <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
</span>