<?php

/**
 * Google Docs Paste Cleaner - Teljes funkcionalitás + Elementor fix
 */
if ( ! function_exists( 'im_google_docs_paste_cleaner_final' ) ) {

    add_action('admin_footer', 'im_google_docs_paste_cleaner_final');
    add_action('elementor/editor/footer', 'im_google_docs_paste_cleaner_final');

    function im_google_docs_paste_cleaner_final() {
        // Opció ellenőrzése
        $options = get_option('im_functions_options');
        if ( is_array($options) && isset($options['google_docs_fix']) && !$options['google_docs_fix'] ) {
            return;
        }
        ?>
        <script>
        (function($) {
            // --- AZ ÖSSZES EREDETI TISZTÍTÓ FUNKCIÓ ---
            function cleanContent(content) {
                if (!content || typeof content !== 'string') return content;

                // 1. Alap formázó tagek egységesítése
                content = content.replace(/<b(\s+[^>]*)?>/gi, '<strong>');
                content = content.replace(/<\/b>/gi, '</strong>');
                content = content.replace(/<i(\s+[^>]*)?>/gi, '<em>');
                content = content.replace(/<\/i>/gi, '</em>');

                // 2. Google Docs data-sheets span-ek eltávolítása
                content = content.replace(/<span[^>]*data-sheets-[^>]*>(.*?)<\/span>/gi, '$1');

                // 3. Formázó span-ek törlése (stílusok nélkül)
                content = content.replace(/<span[^>]*>(.*?)<\/span>/gi, '$1');

                // 4. Nem törhető szóközök eltávolítása (eredeti kérés szerint törlés)
                content = content.replace(/&nbsp;|\u00A0/g, '');

                // 5. Headingekből minden formázást kiszed
                for (let i = 1; i <= 6; i++) {
                    // pl. <strong><h2>xyz</h2></strong> → <h2>xyz</h2>
                    content = content.replace(
                        new RegExp('<(strong|em|b|i|u)[^>]*>\\s*(<h' + i + '[^>]*>.*?<\\/h' + i + '>)\\s*<\\/\\1>', 'gi'),
                        '$2'
                    );
                    // pl. <h2><strong>xyz</strong></h2> → <h2>xyz</h2>
                    content = content.replace(
                        new RegExp('<h' + i + '[^>]*>(.*?)<\\/(strong|em|b|i|u)>\\s*<\\/h' + i + '>', 'gi'),
                        function(_, inner) {
                            inner = inner.replace(/<[^>]+>/g, '');
                            return '<h' + i + '>' + inner + '</h' + i + '>';
                        }
                    );
                    // Általános heading tisztítás
                    content = content.replace(
                        new RegExp('(<h' + i + '\\b[^>]*>)([\\s\\S]*?)(<\\/h' + i + '>)', 'gi'),
                        function(_, openTag, inner, closeTag) {
                            let cleanedInner = inner.replace(/<\/?(strong|em|b|i|u|span)[^>]*>/gi, '').trim();
                            return openTag + cleanedInner + closeTag;
                        }
                    );
                }

                // 6. <li> tagek egységesítése (attribútumok törlése) - VISSZATÉVE
                content = content.replace(/<li[^>]*?>/gi, '<li>');

                // 7. Szimbólumokat sup-be - VISSZATÉVE
                content = content.replace(/(?<!<sup[^>]*?>)([®©™℠])(?!<\/sup>)/g, '<sup>$1</sup>');

                // 8. Töröld az üres bekezdéseket
                content = content.replace(/<p[^>]*>((\s|<br\s*\/?>)*)<\/p>/gi, '');

                // 9. Több <br> egymás után → 1 darabra
                content = content.replace(/(<br\s*\/?>\s*){2,}/gi, '<br>');

                // 10. Sorok elejéről és végéről whitespace
                content = content.replace(/^[ \t]+|[ \t]+$/gm, '');

                // 11. Magányosan álló <br> tagek eltávolítása
                content = content.replace(/^\s*<br\s*\/?>\s*$/gmi, '');

                // 12. Blokkok közé szúrt <br> eltávolítása
                content = content.replace(/<\/(p|h[1-6]|div)>\s*<br\s*\/?>\s*<(p|h[1-6]|div)>/gi, '</$1><$2>');

                return content;
            }

            // Kapcsolódás a TinyMCE-hez (WP és Elementor Text Editor)
            function hookToTinyMCE(editor) {
                if (!editor) return;
                editor.on('PastePreProcess', function(e) {
                    e.content = cleanContent(e.content);
                });
            }

            // Meglévő editorok és újak figyelése
            function reInitEditors() {
                if (typeof tinymce !== 'undefined') {
                    tinymce.editors.forEach(function(editor) {
                        editor.off('PastePreProcess'); // Duplikáció elkerülése
                        hookToTinyMCE(editor);
                    });
                }
            }

            // WP alap esemény
            $(document).on('tinymce-editor-init', function(event, editor) {
                hookToTinyMCE(editor);
            });

            // Elementor specifikus: minden panelváltáskor nézzük meg az editorokat
            $(window).on('elementor/frontend/init', function() {
                if (window.elementor && elementor.channels) {
                    elementor.channels.editor.on('section:activated', function() {
                        setTimeout(reInitEditors, 200);
                    });
                }
            });

            // Biztonsági futtatás betöltés után
            setTimeout(reInitEditors, 1000);

        })(jQuery);
        </script>
        <?php
    }
}
