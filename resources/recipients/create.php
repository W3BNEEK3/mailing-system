<?php
/*
 * resources/recipients/create.php
 *
 * Add a new contact form.
 *
 * @var \App\Models\RecipientGroup[] $groups  All existing groups (for the tags hint)
 */

$fieldErrors = errors();
?>

<div class="mx-auto max-w-2xl px-4 py-8">

    <!-- Page header -->
    <div class="mb-6 flex items-center gap-4">
        <a href="/recipients" class="text-slate-400 hover:text-slate-600 transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Add Contact</h1>
            <p class="mt-0.5 text-sm text-slate-500">All fields except Email are optional.</p>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <form
            method="POST"
            action="/recipients"
            class="px-6 py-6 space-y-5"
        >
            <?= csrf_field() ?>

            <!-- Name row -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <?php include component('forms/_input', [
                    'name'        => 'first_name',
                    'label'       => 'First Name',
                    'type'        => 'text',
                    'value'       => old('first_name', ''),
                    'placeholder' => 'Alice',
                    'required'    => true,
                    'error'       => $fieldErrors['first_name'] ?? null,
                ]); ?>

                <?php include component('forms/_input', [
                    'name'        => 'last_name',
                    'label'       => 'Last Name',
                    'type'        => 'text',
                    'value'       => old('last_name', ''),
                    'placeholder' => 'Smith',
                    'error'       => $fieldErrors['last_name'] ?? null,
                ]); ?>
            </div>

            <!-- Email -->
            <?php include component('forms/_input', [
                'name'        => 'email',
                'label'       => 'Email Address',
                'type'        => 'email',
                'value'       => old('email', ''),
                'placeholder' => 'alice@example.com',
                'required'    => true,
                'error'       => $fieldErrors['email'] ?? null,
            ]); ?>

            <!-- Company -->
            <?php include component('forms/_input', [
                'name'        => 'company',
                'label'       => 'Company (optional)',
                'type'        => 'text',
                'value'       => old('company', ''),
                'placeholder' => 'Acme Corp',
                'error'       => $fieldErrors['company'] ?? null,
            ]); ?>

            <!-- Tags -->
            <div class="flex flex-col gap-1">
                <label for="tags" class="text-sm font-medium text-slate-700">
                    Groups / Tags <span class="text-xs text-slate-400 font-normal">(optional)</span>
                </label>
                <input
                    type="text"
                    id="tags"
                    name="tags"
                    value="<?= e(old('tags', '')) ?>"
                    placeholder="Clients, Newsletter, VIPs"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                           text-slate-800 shadow-sm placeholder-slate-400
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                >
                <p class="text-xs text-slate-500">
                    Separate multiple groups with commas.
                    <?php if (!empty($groups)): ?>
                        Existing groups:
                        <?php foreach ($groups as $g): ?>
                            <button
                                type="button"
                                class="underline text-blue-600 hover:text-blue-700"
                                onclick="addTag('<?= e(addslashes($g->name)) ?>')"
                            ><?= e($g->name) ?></button><?= ($g !== end($groups)) ? ',' : '' ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </p>
            </div>

            <!-- Notes -->
            <div class="flex flex-col gap-1">
                <label for="notes" class="text-sm font-medium text-slate-700">
                    Notes <span class="text-xs text-slate-400 font-normal">(optional)</span>
                </label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="3"
                    placeholder="Any notes about this contact…"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                           text-slate-800 shadow-sm placeholder-slate-400 resize-y
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                ><?= e(old('notes', '')) ?></textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <a href="/recipients" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5
                           text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
                >
                    <i class="bi bi-person-plus-fill"></i>
                    Save Contact
                </button>
            </div>

        </form>
    </div>

</div>

<script>
/*
 * addTag: click a group badge in the hint text to append it to the tags input.
 * Avoids adding the same tag twice.
 */
function addTag(tagName) {
    const input = document.getElementById('tags');
    const current = input.value.split(',').map(t => t.trim()).filter(Boolean);
    if (!current.map(t => t.toLowerCase()).includes(tagName.toLowerCase())) {
        current.push(tagName);
        input.value = current.join(', ');
    }
    input.focus();
}
</script>
