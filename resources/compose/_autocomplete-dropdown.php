<?php
/**
 * resources/compose/_autocomplete-dropdown.php
 *
 * Returned by ComposeController::recipientHints() for the chip input autocomplete.
 * Displayed as an absolute-positioned dropdown below the chip input.
 *
 * @var array $suggestions  Array of ['type' => 'email'|'group', 'value' => string, 'label' => string]
 */
?>

<?php if (empty($suggestions)): ?>
    <div id="recipient-autocomplete-dropdown" class="hidden"></div>
<?php else: ?>
    <div
        id="recipient-autocomplete-dropdown"
        class="absolute left-0 right-0 top-0 z-20 mt-1 bg-white rounded-xl border
               border-slate-200 shadow-lg divide-y divide-slate-100 overflow-hidden"
    >
        <?php foreach ($suggestions as $s): ?>
            <button
                type="button"
                class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-left
                       hover:bg-slate-50 transition"
                onclick="addChipFromSuggestion('<?= e(addslashes($s['value'])) ?>')"
            >
                <i class="bi <?= $s['type'] === 'group' ? 'bi-tag-fill text-blue-400' : 'bi-person-circle text-slate-400' ?> flex-shrink-0 text-base"></i>
                <span class="text-slate-700 truncate"><?= e($s['label']) ?></span>
            </button>
        <?php endforeach; ?>
    </div>

    <script>
    function addChipFromSuggestion(value) {
        // The chip input exposes a global addChip function after initChipInput() runs.
        // Find the correct chip container and add the chip.
        var container = document.getElementById('recipient-chips');
        if (container && typeof container._addChip === 'function') {
            container._addChip(value);
        }
        // Hide the autocomplete dropdown
        var dropdown = document.getElementById('recipient-autocomplete-dropdown');
        if (dropdown) dropdown.classList.add('hidden');
    }
    </script>
<?php endif; ?>