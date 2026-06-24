<?php
/**
 * resources/components/ui/_empty-state.php
 *
 * Displayed when a listing page has no data to show.
 *
 * Variables (all optional — provide at least $heading):
 *   string $icon     Bootstrap icon class, e.g. 'bi-inbox' (default: 'bi-inbox')
 *   string $heading  Primary message, e.g. 'No recipients yet'
 *   string $subtext  Supporting description
 *   string $ctaLabel Label for the call-to-action button
 *   string $ctaUrl   URL for the CTA button
 *   string $ctaHtmx  Optional: space-separated hx-* attributes string for HTMX CTA
 */

$icon     = $icon     ?? 'bi-inbox';
$heading  = $heading  ?? 'Nothing here yet';
$subtext  = $subtext  ?? '';
$ctaLabel = $ctaLabel ?? null;
$ctaUrl   = $ctaUrl   ?? null;
$ctaHtmx  = $ctaHtmx  ?? '';
?>

<div class="flex flex-col items-center justify-center py-20 px-6 text-center">
    <!-- Illustration icon -->
    <div class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center mb-5">
        <i class="bi <?= e($icon) ?> text-3xl text-slate-400"></i>
    </div>

    <!-- Heading -->
    <h3 class="text-base font-semibold text-slate-900 mb-2">
        <?= e($heading) ?>
    </h3>

    <!-- Subtext -->
    <?php if ($subtext): ?>
        <p class="text-sm text-slate-500 max-w-xs mb-6 leading-relaxed">
            <?= e($subtext) ?>
        </p>
    <?php endif; ?>

    <!-- Optional CTA button -->
    <?php if ($ctaLabel && $ctaUrl): ?>
        <a
            href="<?= e(url($ctaUrl)) ?>"
            <?= $ctaHtmx ?>
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold
                   text-white transition hover:opacity-90 focus:outline-none focus:ring-2
                   focus:ring-offset-2"
            style="background-color: var(--color-primary);"
        >
            <?= e($ctaLabel) ?>
        </a>
    <?php endif; ?>
</div>