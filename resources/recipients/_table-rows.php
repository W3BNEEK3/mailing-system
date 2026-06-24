<?php
/*
 * resources/recipients/_table-rows.php
 *
 * Renders the <tbody> contents for the recipients table.
 *
 * This partial is the HTMX target for live search — when the user types
 * in the search box, HTMX replaces the contents of <tbody id="recipient-table">
 * with this partial, showing only matching results.
 *
 * It is also included directly inside recipients/index.php for the initial
 * page render (no duplication — one partial, two uses).
 *
 * @var \App\Models\Recipient[] $recipients  The recipients to display
 * @var array                   $paginated   Pagination metadata
 * @var string                  $search      Current search string
 */
?>

<?php if (empty($recipients)): ?>
    <tr>
        <td colspan="5" class="py-16 text-center">
            <div class="flex flex-col items-center gap-2 text-slate-400">
                <i class="bi bi-people text-4xl"></i>
                <?php if ($search !== ''): ?>
                    <p class="text-sm font-medium">No contacts match "<?= e($search) ?>".</p>
                    <p class="text-xs">Try a different search term.</p>
                <?php else: ?>
                    <p class="text-sm font-medium">No contacts yet.</p>
                    <p class="text-xs">Add your first contact or import a CSV file.</p>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php else: ?>
    <?php foreach ($recipients as $recipient): ?>
        <?php include BASE_PATH . '/resources/recipients/_table-row.php'; ?>
    <?php endforeach; ?>
<?php endif; ?>
