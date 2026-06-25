<?php
/**
 * resources/components/compose/_editor.php
 *
 * The email body editor. Returned as an HTMX partial by ComposeController::loadTemplate().
 * Also included directly in index.php on first load.
 *
 * For MVP, this is a `<textarea>` for the HTML body. A contenteditable WYSIWYG
 * editor is a post-MVP enhancement.
 *
 * The textarea name `body_html` is included in every compose-form HTMX request
 * (hx-include="#compose-form"). Its value is the final HTML that will be:
 *   1. Run through TemplateRenderService::render() at send-time
 *   2. Delivered to the recipient as the email body
 *
 * @var string   $bodyHtml    Current body HTML (empty string on fresh compose)
 * @var int|null $templateId  Currently selected template ID (or null)
 */
?>

<div id="editor-wrapper" class="px-6 py-5">

    <!-- Body textarea -->
    <div class="flex flex-col gap-2">
        <div class="flex items-center justify-between">
            <label for="body-html-input" class="text-xs font-semibold text-slate-500 uppercase tracking-wide">
                Email Body (HTML)
            </label>
            <span class="text-xs text-slate-400">
                Use
                <code class="font-mono bg-slate-100 px-1 py-0.5 rounded text-slate-600">&#123;&#123;TOKEN&#125;&#125;</code>
                for dynamic content
            </span>
        </div>

        <textarea
            id="body-html-input"
            name="body_html"
            rows="22"
            placeholder="Write HTML here, or select a template above to start from a pre-designed layout…"
            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs
                   font-mono text-slate-800 shadow-inner resize-none
                   focus:outline-none focus:ring-2 focus:ring-blue-500
                   transition-colors"
            spellcheck="false"
            autocomplete="off"
        ><?= e($bodyHtml) ?></textarea>

        <!-- Token reference -->
        <div class="flex flex-wrap gap-x-3 gap-y-1 mt-1">
            <?php
            $tokens = [
                '{{LOGO_URL}}'        => 'Logo image URL',
                '{{PRIMARY_COLOR}}'   => 'Primary colour',
                '{{SECONDARY_COLOR}}' => 'Secondary colour',
                '{{SENDER_NAME}}'     => 'Sender name',
                '{{SENDER_EMAIL}}'    => 'Sender email',
            ];
            foreach ($tokens as $token => $label):
            ?>
                <button
                    type="button"
                    class="text-xs font-mono text-blue-600 hover:text-blue-800 hover:underline"
                    onclick="insertToken('<?= $token ?>')"
                    title="<?= e($label) ?>"
                >
                    <?= e($token) ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
/**
 * Insert a token string at the cursor position in the body textarea.
 */
function insertToken(token) {
    var ta    = document.getElementById('body-html-input');
    var start = ta.selectionStart;
    var end   = ta.selectionEnd;
    ta.value  = ta.value.slice(0, start) + token + ta.value.slice(end);
    ta.selectionStart = ta.selectionEnd = start + token.length;
    ta.focus();
}
</script>