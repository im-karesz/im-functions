<?php
/**
 * Plugin Name: IM Functions
 * Description: Öt funkcióval rendelkező bővítmény, amely lehetővé teszi az ACF admin címek testreszabását, a Google Docs-ból másolt tartalmak tisztítását, az elérhetőségi adatok elrejtését, az UTM követési sütik beállítását és az ACF képnevek frissítését Phoenix Media Rename használatakor.
 * Version: 2.3
 * Author: Szente Károly
 * Author URI: https://iparimarketing.hu
 * License: GPL2
 */

// Bővítmény beállításai
define('IM_FUNCTIONS_OPTIONS', 'im_functions_options');

// Bővítmény admin menü hozzáadása a Beállítások alá
add_action('admin_menu', 'im_functions_create_menu');

function im_functions_create_menu() {
    add_options_page(
        'IM Functions', 
        'IM Functions', 
        'manage_options', 
        'im-functions', 
        'im_functions_settings_page'
    );
}

// Admin oldal létrehozása
function im_functions_settings_page() {
    ?>
    <div class="wrap">
        <h1>IM Functions Beállítások</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('im-functions-settings-group');
                do_settings_sections('im-functions-settings-group');
                $options = get_option(IM_FUNCTIONS_OPTIONS);
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">ACF Custom Block Title</th>
                    <td><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[acf_custom_block_title]" value="1" <?php checked(1, !empty($options['acf_custom_block_title']) ? $options['acf_custom_block_title'] : 0); ?> />
                    <p style="font-size: 12px; color: #666;">Kiszedi az ACF mezők címét és a blokk fejrészébe teszi, így könnyebb eligazodni az adminon.</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Google Docs Copy-Paste Fix</th>
                    <td><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[google_docs_fix]" value="1" <?php checked(1, !empty($options['google_docs_fix']) ? $options['google_docs_fix'] : 0); ?> />
                    <p style="font-size: 12px; color: #666;">Wysiwyg mezőkbe beillesztéskor kiszedi a Google Dokumentumok felesleges formázásait, a B tageket STRONG-ra cseréli, az I tageket EM-re és a copyright szimbólumokat felső indexbe rakja.</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Contact Info Mask</th>
                    <td><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[contact_info_mask]" value="1" <?php checked(1, !empty($options['contact_info_mask']) ? $options['contact_info_mask'] : 0); ?> />
                    <p style="font-size: 12px; color: #666;">Elrejti az oldalon az összes email címet, telefonszámot, faxszámot. Az első három karakter után átmenetesen elfedi és csak kattintásra jeleníti meg, így mérhető az interakció.</p></td>
                </tr>
                <tr valign="top">
                    <th scope="row">UTM Tracking Cookie</th>
                    <td>
                        <input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[utm_tracking]" value="1" <?php checked(1, !empty($options['utm_tracking']) ? $options['utm_tracking'] : 0); ?> />
                        <p style="font-size: 12px; color: #666;">Az UTM paramétereket sütikben tárolja és shortcode-dal kiíratható a tartalmuk bárhol.</p>
                        <?php if (!empty($options['utm_tracking'])) : ?>
                            <p style="color: green;">Add hozzá az [utm_params] shortcode-ot a kiküldendő levél törzséhez!</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">ACF Image Rename Updater</th>
                    <td>
                        <input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[acf_image_rename_updater]" value="1" <?php checked(1, !empty($options['acf_image_rename_updater']) ? $options['acf_image_rename_updater'] : 0); ?> />
                        <p style="font-size: 12px; color: #666;">Ez a funkció automatikusan frissíti az ACF blokkokban tárolt képneveket, ha a Phoenix Media Rename plugin módosítja a fájlnevet.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Beállítások regisztrálása és alapértelmezett értékek beállítása
add_action('admin_init', 'im_functions_register_settings');

function im_functions_register_settings() {
    $default_options = array(
        'acf_custom_block_title' => 0,
        'google_docs_fix' => 0,
        'contact_info_mask' => 0,
        'utm_tracking' => 0,
        'acf_image_rename_updater' => 0,
    );

    $options = get_option(IM_FUNCTIONS_OPTIONS, $default_options);

    if ($options === false) {
        update_option(IM_FUNCTIONS_OPTIONS, $default_options);
    }

    register_setting('im-functions-settings-group', IM_FUNCTIONS_OPTIONS);
}

// Betöltjük a funkciós fájlokat feltételekkel
$options = get_option(IM_FUNCTIONS_OPTIONS);

if (isset($options['acf_custom_block_title']) && $options['acf_custom_block_title']) {
    require_once plugin_dir_path(__FILE__) . 'acf-custom-block-title.php';
}

if (isset($options['google_docs_fix']) && $options['google_docs_fix']) {
    require_once plugin_dir_path(__FILE__) . 'google-docs-fix.php';
}

if (isset($options['contact_info_mask']) && $options['contact_info_mask']) {
    require_once plugin_dir_path(__FILE__) . 'contact-info-mask.php';
}

if (isset($options['utm_tracking']) && $options['utm_tracking']) {
    require_once plugin_dir_path(__FILE__) . 'utm-cookie-handler.php';
}

if (isset($options['acf_image_rename_updater']) && $options['acf_image_rename_updater']) {
    require_once plugin_dir_path(__FILE__) . 'acf-image-rename-updater.php';
}

// Automatikus frissítés saját GitHub repóból
if (is_admin()) {
    require_once plugin_dir_path(__FILE__) . 'im-functions-updater.php';
}

// Itt példányosítjuk az updater-t
new IM_Functions_Updater(__FILE__, 'im-karesz', 'im-functions');
