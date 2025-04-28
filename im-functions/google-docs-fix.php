<?php
// Google Docs Copy-Paste Cleaner – ACF WYSIWYG mezőknél, JS alapon működik beillesztéskor

add_action('acf/input/admin_footer', 'im_google_docs_paste_cleaner');
function im_google_docs_paste_cleaner() {
    $options = get_option('im_functions_options');
    if (!isset($options['google_docs_fix']) || !$options['google_docs_fix']) {
        return;
    }
    ?>
    <script>
        (function($){
            function cleanContent(content) {
                // 1. <b> → <strong>
                content = content.replace(/<b(\s+[^>]*)?>/gi, '<strong>');
                content = content.replace(/<\/b>/gi, '</strong>');

                // 2. <i> → <em>
                content = content.replace(/<i(\s+[^>]*)?>/gi, '<em>');
                content = content.replace(/<\/i>/gi, '</em>');

                // 3. Remove <span style="font-weight: 400;">...</span>
                content = content.replace(/<span style="font-weight:\s?400;">(.*?)<\/span>/gi, '$1');

                // 4. Remove style and aria-* from <li> tags
                content = content.replace(/<li[^>]*?>/gi, function() {
                    return '<li>';
                });

                // 5. Wrap ®, ©, ™, ℠ in <sup> if not already inside one
                content = content.replace(/(?<!<sup[^>]*?>)([®©™℠])(?!<\/sup>)/g, '<sup>$1</sup>');

                return content;
            }

            // Apply on all TinyMCE editors (ACF WYSIWYG)
            $(document).on('tinymce-editor-init', function(event, editor) {
                editor.on('PastePreProcess', function(e) {
                    e.content = cleanContent(e.content);
                });
            });
        })(jQuery);
    </script>
    <?php
}
