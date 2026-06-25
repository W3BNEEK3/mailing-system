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
                <!--<button
                    type="button"
                    class="text-xs font-mono text-blue-600 hover:text-blue-800 hover:underline"
                    onclick="insertToken('<?= $token ?>')"
                    title="<?= e($label) ?>"
                >
                    <?= e($token) ?>
                </button>-->
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
(function() {
    var ta = document.getElementById('body-html-input');
    if (!ta) return;

    // Destroy existing instance if HTMX swap over existing editor
    if (window.tinymce && tinymce.get('body-html-input')) {
        tinymce.get('body-html-input').remove();
    }

    tinymce.init({
        selector: '#body-html-input',
        height: 500,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | ' +
            'bold italic forecolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | tokens | code | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 15px; }',
        setup: function(editor) {
            // Add custom Tokens menu button
            editor.ui.registry.addMenuButton('tokens', {
                text: 'Tokens',
                fetch: function (callback) {
                    var items = [
                        { type: 'menuitem', text: 'Logo URL ({{LOGO_URL}})', onAction: function () { editor.insertContent('{{LOGO_URL}}'); } },
                        { type: 'menuitem', text: 'Primary Color ({{PRIMARY_COLOR}})', onAction: function () { editor.insertContent('{{PRIMARY_COLOR}}'); } },
                        { type: 'menuitem', text: 'Secondary Color ({{SECONDARY_COLOR}})', onAction: function () { editor.insertContent('{{SECONDARY_COLOR}}'); } },
                        { type: 'menuitem', text: 'Sender Name ({{SENDER_NAME}})', onAction: function () { editor.insertContent('{{SENDER_NAME}}'); } },
                        { type: 'menuitem', text: 'Sender Email ({{SENDER_EMAIL}})', onAction: function () { editor.insertContent('{{SENDER_EMAIL}}'); } },
                    ];
                    callback(items);
                }
            });

            // Sync to textarea for HTMX
            editor.on('change keyup paste', function() {
                editor.save();
                ta.dispatchEvent(new Event('change', { bubbles: true }));
            });
        }
    });

    window.insertToken = function(token) {
        if (tinymce.get('body-html-input')) {
            tinymce.get('body-html-input').insertContent(token);
        }
    };
})();
</script>