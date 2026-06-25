<?php
/**
 * resources/compose/_draft-list.php
 *
 * The draft list partial returned by DraftController::index().
 * Rendered inside the #drafts-drawer-content div.
 *
 * @var \App\Models\EmailDraft[] $drafts
 */
?>

<?php if (empty($drafts)): ?>

    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <i class="bi bi-journal-x text-4xl text-slate-300 mb-3"></i>
        <p class="text-sm font-medium text-slate-600">No saved drafts</p>
        <p class="text-xs text-slate-400 mt-1">
            Drafts are auto-saved every 60 seconds, or click "Save Draft" manually.
        </p>
    </div>

<?php else: ?>

    <div class="divide-y divide-slate-100">
        <?php foreach ($drafts as $draft): ?>
            <div
                id="draft-item-<?= $draft->id ?>"
                class="flex items-start gap-3 px-4 py-3.5 hover:bg-slate-50 transition group"
            >
                <!-- Load draft into compose -->
                <a
                    href="#"
                    hx-get="/drafts/<?= $draft->id ?>/load"
                    hx-target="#compose-area"
                    hx-swap="innerHTML"
                    hx-on::after-request="closeDraftsDrawer();
                        document.getElementById('draft-id-input').value = <?= $draft->id ?>;"
                    class="flex-1 min-w-0"
                >
                    <p class="text-sm font-medium text-slate-800 truncate">
                        <?= e($draft->displaySubject()) ?>
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5 flex items-center gap-1.5">
                        <i class="bi bi-people text-slate-300"></i>
                        <?= count($draft->recipientsArray()) ?> recipient(s)
                        <span class="text-slate-200">·</span>
                        <?= e($draft->savedAgo()) ?>
                    </p>
                </a>

                <!-- Delete button -->
                <button
                    type="button"
                    class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition
                           rounded-md p-1 text-red-400 hover:bg-red-50 hover:text-red-600"
                    hx-post="/drafts/<?= $draft->id ?>/delete"
                    hx-target="#draft-item-<?= $draft->id ?>"
                    hx-swap="outerHTML"
                    hx-confirm="Delete this draft?"
                    aria-label="Delete draft"
                >
                    <i class="bi bi-trash3 text-sm"></i>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>