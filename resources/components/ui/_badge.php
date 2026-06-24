<?php
/**
 * resources/components/ui/_badge.php
 *
 * Inline status badge — used in log tables, template cards, etc.
 *
 * Variables:
 *   string $type   One of: success, error, warning, info, neutral (default: neutral)
 *   string $label  The text displayed inside the badge
 */

$type  = $type  ?? 'neutral';
$label = $label ?? '';

$styles = [
    'success' => 'bg-emerald-100 text-emerald-800',
    'error'   => 'bg-red-100    text-red-800',
    'warning' => 'bg-amber-100  text-amber-800',
    'info'    => 'bg-blue-100   text-blue-800',
    'neutral' => 'bg-slate-100  text-slate-700',
];

$cls = $styles[$type] ?? $styles['neutral'];
?>

<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $cls ?>">
    <?= e($label) ?>
</span>