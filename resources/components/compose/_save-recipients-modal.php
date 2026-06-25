<?php
/**
 * resources/components/compose/_save-recipients-modal.php
 *
 * HTMX-driven modal to save unsaved recipients after sending an email.
 */
?>
<div
    id="save-recipients-modal"
    class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-black/50 px-4"
    role="dialog" aria-modal="true"
>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden transform transition-all">
        <form hx-post="/recipients/batch-save" hx-target="this" hx-swap="outerHTML">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="text-lg font-semibold text-slate-900">Save New Contacts</h2>
                <p class="text-sm text-slate-500 mt-1">
                    You just sent an email to some addresses that aren't in your contacts.
                    Would you like to save them?
                </p>
            </div>
            
            <div class="px-6 py-4 max-h-[60vh] overflow-y-auto space-y-4" id="save-recipients-list">
                <!-- Dynamically populated via JS -->
            </div>

            <div class="flex items-center justify-between px-6 py-4 bg-slate-50 border-t border-slate-100">
                <button
                    type="button"
                    onclick="document.getElementById('save-recipients-modal').classList.add('hidden');"
                    class="text-sm font-medium text-slate-500 hover:text-slate-700 transition"
                >Skip</button>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-sm font-semibold text-white hover:bg-blue-700 transition shadow-sm"
                >
                    <i class="bi bi-person-plus"></i> Save Contacts
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('promptSaveRecipients', function(e) {
    var emails = e.detail.emails || [];
    if (emails.length === 0) return;

    var listContainer = document.getElementById('save-recipients-list');
    listContainer.innerHTML = ''; // clear

    emails.forEach(function(email, index) {
        var div = document.createElement('div');
        div.className = 'grid grid-cols-2 gap-3 pb-3 border-b border-slate-100 last:border-0 last:pb-0';
        
        div.innerHTML = `
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Email</label>
                <input type="email" name="contacts[${index}][email]" value="${escapeHtml(email)}" readonly
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Name (optional)</label>
                <div class="flex gap-2">
                    <input type="text" name="contacts[${index}][first_name]" placeholder="First"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="text" name="contacts[${index}][last_name]" placeholder="Last"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        `;
        listContainer.appendChild(div);
    });

    document.getElementById('save-recipients-modal').classList.remove('hidden');
});

document.addEventListener('closeModal', function(e) {
    if (e.detail && e.detail.id) {
        var modal = document.getElementById(e.detail.id);
        if (modal) modal.classList.add('hidden');
    }
});
</script>
