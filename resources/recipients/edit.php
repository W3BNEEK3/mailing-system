<?php
/*
 * resources/recipients/edit.php
 *
 * Edit an existing contact.
 *
 * @var \App\Models\Recipient $recipient    The contact being edited
 * @var string                $currentTags  Comma-separated current group names
 * @var \App\Models\RecipientGroup[] $groups  All existing groups
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
            <h1 class="text-2xl font-bold text-slate-900">Edit Contact</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                Editing: <span class="font-medium text-slate-700"><?= e($recipient->fullName()) ?></span>
            </p>
        </div>
    </div>

    <!-- Suppressed warning -->
    <?php if ($recipient->isSuppressed()): ?>
        <div class="mb-5 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50
                    px-5 py-3.5 text-sm text-red-800">
            <i class="bi bi-slash-circle-fill text-red-400 text-base"></i>
            <span>
                This contact is <strong>suppressed</strong> and will not receive emails.
                <button
                    type="button"
                    class="ml-2 underline font-medium hover:no-underline"
                    hx-post="/recipients/<?= (int) $recipient->id ?>/unsuppress"
                    hx-headers='{"X-CSRF-Token": "<?= e(csrf_token()) ?>"}'
                    hx-target="body"
                    hx-swap="none"
                    hx-on::after-request="window.location.reload()"
                >
                    Restore this contact
                </button>
            </span>
        </div>
    <?php endif; ?>

    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <form
            method="POST"
            action="/recipients/<?= (int) $recipient->id ?>"
            class="px-6 py-6 space-y-5"
        >
            <?= csrf_field() ?>
            <input type="hidden" name="_method" value="PUT">

            <!-- Name row -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <?php include component('forms/_input', [
                    'name'        => 'first_name',
                    'label'       => 'First Name',
                    'type'        => 'text',
                    'value'       => old('first_name', $recipient->first_name ?? ''),
                    'placeholder' => 'Alice',
                    'required'    => true,
                    'error'       => $fieldErrors['first_name'] ?? null,
                ]); ?>

                <?php include component('forms/_input', [
                    'name'        => 'last_name',
                    'label'       => 'Last Name',
                    'type'        => 'text',
                    'value'       => old('last_name', $recipient->last_name ?? ''),
                    'placeholder' => 'Smith',
                    'error'       => $fieldErrors['last_name'] ?? null,
                ]); ?>
            </div>

            <!-- Email -->
            <?php include component('forms/_input', [
                'name'        => 'email',
                'label'       => 'Email Address',
                'type'        => 'email',
                'value'       => old('email', $recipient->email ?? ''),
                'placeholder' => 'alice@example.com',
                'required'    => true,
                'error'       => $fieldErrors['email'] ?? null,
            ]); ?>

            <!-- Company -->
            <?php include component('forms/_input', [
                'name'        => 'company',
                'label'       => 'Company (optional)',
                'type'        => 'text',
                'value'       => old('company', $recipient->company ?? ''),
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
                    value="<?= e(old('tags', $currentTags)) ?>"
                    placeholder="Clients, Newsletter, VIPs"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                           text-slate-800 shadow-sm placeholder-slate-400
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                >
                <p class="text-xs text-slate-500">Separate multiple groups with commas. Saving with an empty tags field removes all group memberships.</p>
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
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm
                           text-slate-800 shadow-sm resize-y
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                ><?= e(old('notes', $recipient->notes ?? '')) ?></textarea>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <a href="/recipients" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5
                           text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
                >
                    <i class="bi bi-floppy"></i>
                    Save Changes
                </button>
            </div>

        </form>
    </div>

</div>
