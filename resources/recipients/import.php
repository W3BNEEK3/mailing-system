<?php
/*
 * resources/recipients/import.php
 *
 * CSV import page for bulk-uploading recipient contacts.
 *
 * The page has:
 *   - A description of the expected CSV format
 *   - A sample CSV download link (served as a static asset or inline data URI)
 *   - A file upload form (HTMX-enhanced for in-page results)
 *   - An #import-results area where the result partial is injected after import
 *
 * The import form uses HTMX to POST the file and replace #import-results
 * with the _import-results.php partial showing counts and any error rows.
 * If JS is disabled, the form falls back to a standard POST + redirect.
 */

$fieldErrors  = errors();
$flashToast   = session()->getFlash('_toast');
$importResult = session()->getFlash('_import_result');
?>

<div class="mx-auto max-w-3xl px-4 py-8">

    <!-- Page header -->
    <div class="mb-6 flex items-center gap-4">
        <a href="/recipients" class="text-slate-400 hover:text-slate-600 transition">
            <i class="bi bi-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Import Recipients</h1>
            <p class="mt-0.5 text-sm text-slate-500">Upload a CSV file to import multiple contacts at once.</p>
        </div>
    </div>

    <!-- Flash toast bridge (for non-HTMX fallback) -->
    <?php if ($flashToast): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.dispatchEvent(new CustomEvent('showToast', { detail: <?= json_encode($flashToast) ?> }));
            });
        </script>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">

        <!-- Left: Upload form (wider column) -->
        <div class="lg:col-span-3 space-y-6">

            <!-- CSV format instructions -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-3">
                    <i class="bi bi-info-circle mr-1 text-blue-500"></i>
                    Expected CSV Format
                </h2>
                <p class="text-sm text-slate-600 mb-3">
                    Your CSV should have the following columns. A header row is optional but
                    recommended. Only the <strong>email</strong> column is required.
                </p>
                <div class="overflow-x-auto rounded-lg border border-slate-200">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 text-slate-500 font-semibold uppercase tracking-wide">
                            <tr>
                                <th class="px-3 py-2">Column</th>
                                <th class="px-3 py-2">Required</th>
                                <th class="px-3 py-2">Example</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-600 divide-y divide-slate-100">
                            <tr><td class="px-3 py-2 font-mono">first_name</td><td class="px-3 py-2">No</td><td class="px-3 py-2">Alice</td></tr>
                            <tr><td class="px-3 py-2 font-mono">last_name</td><td class="px-3 py-2">No</td><td class="px-3 py-2">Smith</td></tr>
                            <tr><td class="px-3 py-2 font-mono">email</td><td class="px-3 py-2"><strong class="text-red-600">Yes</strong></td><td class="px-3 py-2">alice@example.com</td></tr>
                            <tr><td class="px-3 py-2 font-mono">company</td><td class="px-3 py-2">No</td><td class="px-3 py-2">Acme Corp</td></tr>
                            <tr><td class="px-3 py-2 font-mono">tags</td><td class="px-3 py-2">No</td><td class="px-3 py-2">Clients, Newsletter</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sample CSV download -->
                <div class="mt-3">
                    <a
                        href="data:text/csv;charset=utf-8,first_name%2Clast_name%2Cemail%2Ccompany%2Ctags%0AAlice%2CSmith%2Calice%40example.com%2CAcme%20Corp%2CClients%0ABob%2CJones%2Cbob%40example.com%2C%2CNewsletter"
                        download="sample-recipients.csv"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:underline"
                    >
                        <i class="bi bi-download"></i>
                        Download sample CSV
                    </a>
                </div>
            </div>

            <!-- Upload form -->
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm p-5">
                <h2 class="text-sm font-semibold text-slate-700 mb-4">Upload Your CSV</h2>

                <form
                    id="import-form"
                    method="POST"
                    action="/recipients/import"
                    enctype="multipart/form-data"
                    class="space-y-4"
                    hx-post="/recipients/import"
                    hx-encoding="multipart/form-data"
                    hx-include="#import-form"
                    hx-target="#import-results"
                    hx-swap="innerHTML"
                    hx-indicator="#import-indicator"
                >
                    <?= csrf_field() ?>

                    <?php include component('forms/_file-upload', [
                        'name'    => 'csv_file',
                        'label'   => 'CSV File',
                        'accept'  => 'text/csv,application/csv,.csv',
                        'hint'    => '.csv files only. Maximum 5,000 rows per import.',
                        'isImage' => false,
                        'error'   => $fieldErrors['csv_file'] ?? null,
                    ]); ?>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5
                                   text-sm font-medium text-white shadow-sm hover:bg-blue-700 transition"
                        >
                            <span id="import-indicator" class="htmx-indicator">
                                <svg class="animate-spin h-4 w-4 text-white mr-1" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                            </span>
                            <i class="bi bi-cloud-upload"></i>
                            Import Contacts
                        </button>

                        <a href="/recipients" class="text-sm text-slate-500 hover:text-slate-700">
                            Cancel
                        </a>
                    </div>

                </form>
            </div>

        </div>

        <!-- Right: Results area -->
        <div class="lg:col-span-2">
            <div
                id="import-results"
                class="rounded-xl border border-slate-200 bg-slate-50 p-5 min-h-[180px]
                       flex items-center justify-center text-center"
            >
                <!-- Non-HTMX result (from flash after redirect) -->
                <?php if ($importResult): ?>
                    <?php include BASE_PATH . '/resources/recipients/_import-results.php'; ?>
                <?php else: ?>
                    <p class="text-sm text-slate-400">
                        <i class="bi bi-clipboard-data text-3xl block mb-2"></i>
                        Import results will appear here after you upload a file.
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>
