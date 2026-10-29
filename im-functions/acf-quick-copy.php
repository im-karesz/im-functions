<?php
/**
 * IM Functions - ACF Quick Copy & Paste
 * Verzió: 2.1.1 (Deep Clean & URL Sync)
 */

if( !defined( 'ABSPATH' ) ) exit;

add_action('admin_footer', function() {
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'post') return;
    ?>
    <style>
        /* Alap formázás az IM szerkezet alapján */
        .acf-fc-layout-controls .custom-copy-paste-group { display: none; align-items: center; }
        .layout:hover > .acf-fc-layout-actions-wrap .custom-copy-paste-group,
        .layout.active-layout > .acf-fc-layout-actions-wrap .custom-copy-paste-group { display: inline-flex; }
        .custom-copy-paste-group .acf-icon {
            display: block; box-sizing: content-box; height: 28px; width: 28px; margin: 0 2px;
            border-radius: 100%; color: #101828 !important; background-color: transparent;
            border: 0px; cursor: pointer; visibility: visible !important;
        }
        .custom-copy-paste-group a { color: transparent !important; position: relative; }
        .layout.active-layout > .acf-fc-layout-actions-wrap .custom-copy-paste-group .acf-icon { color: #fff !important; }
        .c-icon-glyph:before { font-family: dashicons !important; font-size: 16px; line-height: 28px; display: block; width: 100%; text-align: center; }
        .c-icon-copy:before { content: "\f105"; }
        .c-icon-paste:before { content: "\f481"; }
        .custom-v-sep { border-left: 1px solid rgba(0,0,0,0.1); height: 20px; margin: 0 8px 0 4px; }
        .layout.active-layout .custom-v-sep { border-left-color: rgba(255,255,255,0.3); }

        /* Tooltip stílus */
        .im-acf-notice-tooltip {
            position: absolute; bottom: 35px; left: 50%; transform: translateX(-50%);
            background: #101828; color: #fff !important; padding: 5px 12px; border-radius: 4px;
            font-size: 12px; font-weight: 500; white-space: nowrap; z-index: 9999;
            pointer-events: none; box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            animation: imAcfTooltipIn 0.2s ease-out;
        }
        .im-acf-notice-tooltip:after {
            content: ""; position: absolute; top: 100%; left: 50%; margin-left: -5px;
            border-width: 5px; border-style: solid; border-color: #101828 transparent transparent transparent;
        }
        .im-acf-notice-error { background: #d63638; }
        .im-acf-notice-error:after { border-color: #d63638 transparent transparent transparent; }
        @keyframes imAcfTooltipIn { from { opacity: 0; bottom: 25px; } to { opacity: 1; bottom: 35px; } }
    </style>

    <script type="text/javascript">
    (function($){
        if(typeof acf === 'undefined') return;

        const Utils = {
            // Segédfunkció a tooltip megjelenítéséhez
            showNotice: function($btn, message, isError = false) {
                $('.im-acf-notice-tooltip').remove();
                var errorClass = isError ? ' im-acf-notice-error' : '';
                var $tooltip = $('<div class="im-acf-notice-tooltip' + errorClass + '">' + message + '</div>');
                $btn.append($tooltip);
                setTimeout(function() { $tooltip.fadeOut(300, function() { $(this).remove(); }); }, 2500);
            },

            fixAndClean: function($layout) {
                // 1. TinyMCE mentés
                if (typeof tinymce !== 'undefined') { tinymce.triggerSave(); }

                // 2. Értékek rögzítése és tisztítás az ingyenes bővítmény logikája szerint
                $layout.find('input, textarea, select').each(function() {
                    var $el = $(this);
                    if ($el.is('select')) {
                        $el.find('option').each(function() { $(this).attr('selected', this.selected); });
                    } else if ($el.is(':checkbox, :radio')) {
                        $el.attr('checked', this.checked);
                    } else {
                        $el.attr('value', $el.val());
                        if($el.is('textarea')) $el.html($el.val());
                    }
                });

                // 3. ACF specifikus UI elemek eltávolítása (Date, Color, Select2)
                $layout.find('.acf-editor-wrap').each(function() {
                    $(this).find('.wp-editor-container div').remove();
                    $(this).find('.wp-editor-container textarea').css('display', '');
                });
                $layout.find('.acf-date-picker, .acf-time-picker').each(function() {
                    $(this).find('input.input').removeClass('hasDatepicker').removeAttr('id');
                });
                $layout.find('.wp-picker-container').each(function() {
                    var $input = $(this).find('input.wp-color-picker').clone();
                    $(this).after($input).remove();
                });
                $layout.find('.select2-container').remove();
                $layout.find('select').removeClass('select2-hidden-accessible').removeAttr('data-select2-id');
            }
        };

        function injectButtons($el) {
            $el.find('.acf-fc-layout-controls').each(function(){
                var $controls = $(this);
                if( !$controls.find('.custom-copy-paste-group').length ) {
                    var $group = $('<div class="custom-copy-paste-group"></div>');
                    $group.append('<a class="acf-js-tooltip" title="Másolás" href="#" data-cmd="copy"><span class="acf-icon c-icon-glyph c-icon-copy"></span></a>');
                    $group.append('<a class="acf-js-tooltip" title="Beillesztés ez fölé" href="#" data-cmd="paste"><span class="acf-icon c-icon-glyph c-icon-paste"></span></a>');
                    $group.append('<span class="custom-v-sep"></span>');
                    $controls.prepend($group);
                }
            });
        }

        acf.addAction('ready_field/type=flexible_content', function(f){ injectButtons(f.$el); });
        acf.addAction('append', function($el){ if($el.is('.layout')) injectButtons($el.closest('.acf-field')); });

        // MÁSOLÁS
        $(document).on('click', '[data-cmd="copy"]', function(e){
            e.preventDefault();
            var $layout = $(this).closest('.layout');
            var $clone = $layout.clone();
            Utils.fixAndClean($clone);

            localStorage.setItem('acf_im_functions_v21', JSON.stringify({
                domain: window.location.origin, // Domain mentése az URL szinkronhoz
                layout: $layout.data('layout'),
                html: $clone[0].outerHTML,
                old_id: $layout.data('id')
            }));

            Utils.showNotice($(this), ($layout.data('label') || 'Blokk') + ' kimásolva');
        });

        // BEILLESZTÉS
        $(document).on('click', '[data-cmd="paste"]', function(e){
            e.preventDefault();
            var $target = $(this).closest('.layout');
            var field = acf.getClosestField($target);
            var data = localStorage.getItem('acf_im_functions_v21');

            if(!data) {
                Utils.showNotice($(this), 'Nincs mit beilleszteni!', true); // Piros hiba tooltip
                return;
            }

            var clip = JSON.parse(data);

            // Domain szinkronizálása (pl. staging -> live csere)
            var finalHtml = clip.html.replaceAll(clip.domain, window.location.origin);

            var $new = field.add({ layout: clip.layout, before: $target });
            if($new) {
                $new.html($(finalHtml).html());
                acf.rename({ target: $new, search: clip.old_id, replace: $new.data('id') });
                acf.doAction('append', $new);
            }
        });

    })(jQuery);
    </script>
    <?php
});
