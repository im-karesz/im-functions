<?php
/**
 * Feature Name: IM Pályázati Infoblokk
 * Description: Dinamikusan konfigurálható frontend pályázati blokk ugrálásmentes betöltéssel és diszkrét bezárógombbal.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


function im_render_palyazati_infoblokk_frontend() {

    $options  = get_option('im_functions_options');

    $position = !empty($options['palyazat_position'])
        ? $options['palyazat_position']
        : 'bottom-right';

    $shadow = isset($options['palyazat_shadow'])
        ? (bool) $options['palyazat_shadow']
        : true;

    $mobile_scale = isset($options['palyazat_mobile_scale'])
        ? intval($options['palyazat_mobile_scale'])
        : 50;

    $mobile_scale = max(1, min(100, $mobile_scale));

    $items = !empty($options['palyazat_items'])
        ? $options['palyazat_items']
        : array();


    if ( empty($items) ) {
        return;
    }


    $wrapper_classes = 'im-palyazat-pos-' . esc_attr($position);

    if (!$shadow) {
        $wrapper_classes .= ' im-palyazat-no-style';
    }
    ?>

    <div
        id="im-palyazat-fixed-wrapper"
        class="<?php echo esc_attr($wrapper_classes); ?>"
        style="display: none !important;"
    >

        <button
            type="button"
            id="im-palyazat-close-btn"
            title="Bezárás"
        >&times;</button>


        <div class="im-palyazat-flex-stack">

            <?php foreach ( $items as $item ) :

                if ( empty($item['image']) ) {
                    continue;
                }


                $item_width = !empty($item['width'])
                    ? absint($item['width'])
                    : 0;

                $item_height = !empty($item['height'])
                    ? absint($item['height'])
                    : 0;


                $desktop_width_css = $item_width > 0
                    ? $item_width . 'px'
                    : 'auto';

                $desktop_height_css = $item_height > 0
                    ? $item_height . 'px'
                    : 'auto';


                $mobile_width = $item_width > 0
                    ? max(1, round($item_width * ($mobile_scale / 100)))
                    : 0;

                $mobile_height = $item_height > 0
                    ? max(1, round($item_height * ($mobile_scale / 100)))
                    : 0;


                $mobile_width_css = $mobile_width > 0
                    ? $mobile_width . 'px'
                    : 'auto';

                $mobile_height_css = $mobile_height > 0
                    ? $mobile_height . 'px'
                    : 'auto';


                $image_style = sprintf(
                    '--im-desktop-width:%s;--im-desktop-height:%s;--im-mobile-width:%s;--im-mobile-height:%s;',
                    $desktop_width_css,
                    $desktop_height_css,
                    $mobile_width_css,
                    $mobile_height_css
                );
                ?>

                <div class="im-palyazat-banner-item">

                    <?php if ( !empty($item['link']) ) : ?>
                        <a href="<?php echo esc_url($item['link']); ?>">
                    <?php endif; ?>


                        <img
                            src="<?php echo esc_url($item['image']); ?>"
                            alt="Pályázati információ"
                            loading="eager"
                            data-no-lazy="1"
                            data-skip-lazy="1"
                            class="skip-lazy no-lazy im-palyazat-img"
                            style="<?php echo esc_attr($image_style); ?>"
                        />


                    <?php if ( !empty($item['link']) ) : ?>
                        </a>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>
    </div>


    <button
        type="button"
        id="im-palyazat-restore-btn"
        class="im-palyazat-restore-pos-<?php echo esc_attr($position); ?> <?php echo !$shadow ? 'im-palyazat-restore-no-style' : ''; ?>"
        style="display: none;"
        title="Pályázati infók visszaállítása"
    >

        <svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 512 512"><g fill-rule="nonzero"><path fill="#4D4D4D" d="M256 0c70.684 0 134.689 28.663 181.013 74.987C483.337 121.31 512 185.316 512 256c0 70.684-28.663 134.689-74.987 181.013C390.689 483.336 326.684 512 256 512c-70.676 0-134.689-28.664-181.013-74.987C28.664 390.689 0 326.676 0 256c0-70.684 28.664-134.69 74.987-181.013C121.311 28.663 185.316 0 256 0z"/><path fill="#fff" d="M256.002 19.596c65.279 0 124.383 26.462 167.161 69.24 42.772 42.777 69.239 101.885 69.239 167.164 0 65.28-26.462 124.384-69.239 167.161-42.778 42.777-101.882 69.239-167.161 69.239-65.28 0-124.388-26.466-167.165-69.239C46.06 380.384 19.598 321.28 19.598 256c0-65.279 26.466-124.387 69.239-167.164 42.777-42.774 101.885-69.24 167.165-69.24z"/><path fill="#039" d="M256.002 39.591c119.517 0 216.404 96.892 216.404 216.409s-96.887 216.405-216.404 216.405C136.48 472.405 39.593 375.517 39.593 256S136.48 39.591 256.002 39.591z"/><path fill="#FFCC02" d="M240.694 435.8L256 424.679l15.305 11.121-5.845-17.995 15.305-11.12h-18.918L256 388.69l-5.848 17.995h-18.918l15.305 11.12-5.845 17.995zM256 76.196l-5.848 17.993h-18.918l15.305 11.122-5.845 17.992L256 112.183l15.305 11.12-5.845-17.994 15.305-11.12h-18.918L256 76.196zm102.889 309.555l-15.305 11.12 5.845 17.995-15.305-11.12-15.308 11.12 5.847-17.995-15.308-11.12h18.921l5.848-17.994 5.845 17.994h18.92zm57.191-57.19l-15.306 11.12 5.846 17.995-15.306-11.121-15.308 11.121 5.848-17.995-15.308-11.12h18.92l5.848-17.995 5.848 17.995h18.918zm-24.766-174.243l5.848 17.995h18.918l-15.306 11.12 5.846 17.994-15.306-11.12-15.308 11.12 5.848-17.994-15.308-11.12h18.92l5.848-17.995zm-81.959-39.196h18.921l5.848-17.995 5.847 17.995h18.918l-15.308 11.123 5.848 17.992-15.305-11.12-15.308 11.12 5.847-17.992-15.308-11.123zm118.198 164.429l-15.305-11.12-15.308 11.12 5.847-17.994-15.308-11.12H406.4l5.848-17.995 5.847 17.995h18.918l-15.305 11.122 5.845 17.992zm-274.443 106.2l15.305 11.12-5.845 17.995 15.306-11.12 15.307 11.12-5.847-17.995 15.308-11.12h-18.921l-5.847-17.994-5.846 17.994h-18.92zm-57.191-57.19l15.306 11.12-5.845 17.995 15.305-11.121 15.308 11.121-5.848-17.995 15.308-11.12h-18.92l-5.848-17.995-5.848 17.995H95.919zm24.766-174.243l-5.848 17.995H95.919l15.306 11.12-5.845 17.994 15.305-11.12 15.308 11.12-5.848-17.994 15.308-11.12h-18.92l-5.848-17.995zm81.959-39.196h-18.921l-5.847-17.995-5.848 17.995H153.11l15.308 11.123-5.848 17.992 15.306-11.12 15.307 11.12-5.847-17.992 15.308-11.123zM84.446 279.551l15.305-11.12 15.308 11.12-5.847-17.994 15.308-11.12h-18.921l-5.848-17.995-5.847 17.995H74.986l15.305 11.122-5.845 17.992z"/></g></svg>

    </button>


    <style type="text/css">

        #im-palyazat-fixed-wrapper {
            position: fixed;
            z-index: 999999;
            background: rgba(255, 255, 255, 0.96);
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            max-width: calc(100vw - 40px);
            box-sizing: border-box;

            /* Sima beúszás animáció áttűnéssel */
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.4s ease, transform 0.4s ease;
        }


        /* Osztály amit a JS ad meg, ha a valós kép betöltődött */
        #im-palyazat-fixed-wrapper.im-palyazat-loaded {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }


        /* KERETMENTES (NO-STYLE) MÓD */
        #im-palyazat-fixed-wrapper.im-palyazat-no-style {
            background: transparent !important;
            padding: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            max-width: 100vw !important;
        }


        /* Pozíciók (20px eltolással a képernyőtől) */
        .im-palyazat-pos-bottom-right {
            bottom: 20px;
            right: 20px;
        }

        .im-palyazat-pos-bottom-left {
            bottom: 20px;
            left: 20px;
        }

        .im-palyazat-pos-top-right {
            top: 20px;
            right: 20px;
        }

        .im-palyazat-pos-top-left {
            top: 20px;
            left: 20px;
        }


        /* Keretmentes pozíciók (Abszolút 0,0 sarok-tapadás) */
        .im-palyazat-no-style.im-palyazat-pos-bottom-right {
            bottom: 0 !important;
            right: 0 !important;
        }

        .im-palyazat-no-style.im-palyazat-pos-bottom-left {
            bottom: 0 !important;
            left: 0 !important;
        }

        .im-palyazat-no-style.im-palyazat-pos-top-right {
            top: 0 !important;
            right: 0 !important;
        }

        .im-palyazat-no-style.im-palyazat-pos-top-left {
            top: 0 !important;
            left: 0 !important;
        }


        /* Sorbarendezés flexbox-szal */
        .im-palyazat-flex-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        #im-palyazat-fixed-wrapper.im-palyazat-no-style .im-palyazat-flex-stack {
            gap: 0px !important;
        }


        .im-palyazat-banner-item {
            width: 100%;
            max-width: 100%;
        }

        .im-palyazat-banner-item a {
            display: block;
            width: 100%;
            max-width: 100%;
        }


        /* Képenként megadott desktop méretezés */
        .im-palyazat-banner-item img {
            display: block;
            max-width: 100%;
            width: var(--im-desktop-width, auto);
            height: var(--im-desktop-height, auto);
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            background: transparent !important;
            margin-left: auto;
        }


        #im-palyazat-fixed-wrapper.im-palyazat-no-style .im-palyazat-banner-item img {
            border-radius: 0 !important;
            box-shadow: none !important;
        }


        /* DISZKRÉT AUDITOR-BIZTOS BEZÁRÓ GOMB (Kilóg a képből, fix tiszta fehér kör háttér) */
        #im-palyazat-close-btn {
            position: absolute;
            top: -12px;
            right: 12px;
            width: 24px;
            height: 24px;
            background: #ffffff !important;
            color: #666666 !important;
            border: 1px solid #dddddd !important;
            border-radius: 50% !important;
            cursor: pointer;
            font-size: 16px;
            line-height: 22px;
            text-align: center;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
            padding: 0;
            z-index: 999999;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }


        #im-palyazat-close-btn:hover {
            color: #000000 !important;
            border-color: #bbbbbb !important;
            box-shadow: 0 2px 12px rgba(0,0,0,0.18) !important;
        }


        /* Korrekció keretmentes módban, hogy a szélén se lógjon le a képernyőről */
        .im-palyazat-no-style #im-palyazat-close-btn {
            top: -12px !important;
            right: 4px !important;
        }


        /* Visszaállító kör alapstílusa */
        #im-palyazat-restore-btn {
            position: fixed;
            z-index: 999998;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            width: 45px;
            height: 45px;
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }


        #im-palyazat-restore-btn:hover {
            transform: scale(1.15);
        }


        #im-palyazat-restore-btn svg {
            width: 100%;
            height: 100%;
            display: block;
        }


        /* Visszaállító gomb normál pozíciói */
        .im-palyazat-restore-pos-bottom-right {
            bottom: 20px;
            right: 20px;
        }

        .im-palyazat-restore-pos-bottom-left {
            bottom: 20px;
            left: 20px;
        }

        .im-palyazat-restore-pos-top-right {
            top: 20px;
            right: 20px;
        }

        .im-palyazat-restore-pos-top-left {
            top: 20px;
            left: 20px;
        }


        /* Visszaállító gomb pozíciói szigorú 0,0 tapadásnál */
        .im-palyazat-restore-no-style.im-palyazat-restore-pos-bottom-right {
            bottom: 0 !important;
            right: 0 !important;
        }

        .im-palyazat-restore-no-style.im-palyazat-restore-pos-bottom-left {
            bottom: 0 !important;
            left: 0 !important;
        }

        .im-palyazat-restore-no-style.im-palyazat-restore-pos-top-right {
            top: 0 !important;
            right: 0 !important;
        }

        .im-palyazat-restore-no-style.im-palyazat-restore-pos-top-left {
            top: 0 !important;
            left: 0 !important;
        }


        /* Mobil nézet */
        @media only screen and (max-width: 768px) {

            #im-palyazat-fixed-wrapper {
                max-width: 85% !important;
            }


            /*
             * Mobilon a desktophoz megadott pixelméretből
             * számított százalékos méretet használjuk.
             */
            .im-palyazat-banner-item img {
                width: var(--im-mobile-width, auto);
                height: var(--im-mobile-height, auto);
                max-width: 100% !important;
            }

        }

    </style>


    <script type="text/javascript">

    document.addEventListener('DOMContentLoaded', function() {

        const pWrapper = document.getElementById('im-palyazat-fixed-wrapper');
        const pCloseBtn = document.getElementById('im-palyazat-close-btn');
        const pRestoreBtn = document.getElementById('im-palyazat-restore-btn');


        if (!pWrapper) {
            return;
        }


        // Ha a felhasználó korábban már bezárta, rejtve indul,
// de az eseménykezelőket továbbra is bekötjük.
const pWasClosed = localStorage.getItem('imPalyazatBlockClosed') === 'true';

if (pWasClosed) {

    pWrapper.style.setProperty('display', 'none', 'important');

    if (pRestoreBtn) {
        pRestoreBtn.style.display = 'block';
    }

}


        const pImages = pWrapper.querySelectorAll('img');


        // Golyóálló lekérdező ciklus (polling) a lazy load kivédésére
        function checkImagesReady() {

            let allReady = true;


            pImages.forEach(function(img) {

                // 1. Ha a kép src-je még csak egy base64 adatsor vagy üres,
                // akkor az még csak a lusta helyőrző kocka
                if (
                    !img.src ||
                    img.src.startsWith('data:') ||
                    img.src.includes('placeholder')
                ) {
                    allReady = false;
                }


                // 2. Ellenőrizzük, hogy a böngésző betöltötte-e
                // és van-e valós fizikai szélessége a képnek
                if (!img.complete || img.naturalWidth <= 1) {
                    allReady = false;
                }

            });


            if (allReady) {

                // Csak akkor lökjük be a kijelzőre a wrapper dobozt,
                // ha a kép valós adatai már 100%-ig renderelődtek
                pWrapper.style.setProperty('display', 'block', 'important');


                setTimeout(function() {
                    pWrapper.classList.add('im-palyazat-loaded');
                }, 30);


                if (pRestoreBtn) {
                    pRestoreBtn.style.display = 'none';
                }

            } else {

                // Ha még tart a lazy load trükközés,
                // 50ms múlva újra csekkoljuk a háttérben, teljesen rejtve
                setTimeout(checkImagesReady, 50);

            }
        }


        // Folyamat indítása csak akkor, ha nem volt korábban bezárva
if (!pWasClosed) {
    checkImagesReady();
}


        // Bezárás esemény
        pCloseBtn.addEventListener('click', function() {

            pWrapper.style.setProperty('display', 'none', 'important');

            pWrapper.classList.remove('im-palyazat-loaded');

            pRestoreBtn.style.display = 'block';

            localStorage.setItem('imPalyazatBlockClosed', 'true');

        });


        // Visszaállítás esemény
        pRestoreBtn.addEventListener('click', function() {

            pWrapper.style.setProperty('display', 'block', 'important');


            setTimeout(function() {
                pWrapper.classList.add('im-palyazat-loaded');
            }, 30);


            pRestoreBtn.style.display = 'none';

            localStorage.setItem('imPalyazatBlockClosed', 'false');

        });

    });

    </script>

    <?php
}


add_action('wp_footer', 'im_render_palyazati_infoblokk_frontend');
