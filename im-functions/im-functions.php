<?php
/**
 * Plugin Name: IM Functions
 * Description: Többfunkciós bővítmény modern ipari stílusban, univerzális űrlap exporttal (CF7, Elementor & Breakdance).
 * Version: 2.3.0
 * Author: Szente Károly
 * Update URI: https://updater.industrialmarketing.dev/im-functions
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

define('IM_FUNCTIONS_OPTIONS', 'im_functions_options');
define('IM_FUNCTIONS_VERSION', '2.3.0');

add_action('admin_menu', 'im_functions_create_menu');
function im_functions_create_menu() {
    add_options_page('IM Functions', 'IM Functions', 'manage_options', 'im-functions', 'im_functions_settings_page');
}

// Gyári WP eszközök (Médiatár, Színválasztó) betöltése kizárólag a bővítmény oldalán
add_action('admin_enqueue_scripts', 'im_functions_admin_assets');
function im_functions_admin_assets($hook) {
    if (isset($_GET['page']) && $_GET['page'] === 'im-functions') {
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
    }
}

function im_functions_settings_page() {
    ?>
    <style>
        :root { --im-primary: #ffc107; --im-black: #000; --im-grey: #f0f0f1; --im-text: #1d2327; --im-border: #e0e0e0; }
        .im-admin-wrapper { margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: var(--im-text); padding-right: 30px; max-width: 1200px; }

        .im-section-title { margin: 40px 0 20px 0; font-size: 16px; font-weight: 800; color: var(--im-black); display: flex; align-items: center; gap: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .im-section-title:after { content: ''; flex: 1; height: 2px; background: var(--im-grey); }

        .im-grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .im-card {
            background: #fff; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); padding: 25px; border: 1px solid var(--im-border); border-left: 6px solid var(--im-black);
            flex: 0 0 calc(50% - 10px); box-sizing: border-box; min-width: 350px; display: flex; flex-direction: column; transition: 0.2s;
        }

        .im-card-header h2 { margin: 0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; border:none; color:var(--im-black); font-weight: 800; border-bottom: 2px solid var(--im-grey); padding-bottom: 12px; margin-bottom: 20px; }

        .im-row { display: flex; align-items: flex-start; gap: 15px; flex-grow: 1; }
        .im-switch { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; }
        .im-switch input { opacity: 0; width: 0; height: 0; }
        .im-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .im-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .im-slider { background-color: var(--im-black); }
        input:checked + .im-slider:before { transform: translateX(18px); background-color: var(--im-primary); }

        .im-select { width: 100%; padding: 8px 12px; border-radius: 6px; border: 1px solid var(--im-border); background: var(--im-grey); font-size: 12px; margin-top: 10px; font-weight: 600; cursor: pointer; }
        .im-select:focus { border-color: var(--im-primary); outline: none; }

        .im-desc { font-size: 12px; color: #666; line-height: 1.5; }
        .im-badge { font-size: 11px; color: green; font-weight: 600; display: block; margin-top: 10px; }

        .im-sub-settings { margin-top: 15px; padding-top: 15px; border-top: 1px dashed var(--im-border); display: none; margin-left: 10px; }
        .im-sub-option { display: flex; align-items: center; gap: 15px; }

        .im-btn { padding: 0 30px !important; height: 45px !important; line-height: 43px !important; font-size: 13px !important; font-weight: 700 !important; border-radius: 6px !important; cursor: pointer; border: none !important; text-transform: uppercase; transition: 0.2s; display: inline-block; text-align: center; text-decoration: none !important; }
        .im-btn-primary { background: var(--im-primary) !important; color: var(--im-black) !important; box-shadow: 0 4px 0 #d39e00; }
        .im-btn-primary:hover { background: var(--im-black) !important; color: var(--im-primary) !important; box-shadow: 0 4px 0 #333; transform: translateY(-1px); }

        #im-success-popup { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 99999; flex-direction: column; justify-content: center; align-items: center; }
        .im-popup-box { background: var(--im-black); color: var(--im-primary); padding: 40px; border-radius: 15px; text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.5); border: 2px solid var(--im-primary); transform: scale(0.8); opacity: 0; transition: 0.3s all cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .im-popup-box.show { transform: scale(1); opacity: 1; }
        .gear-icon { font-size: 50px; margin-bottom: 20px; display: inline-block; }
        .im-popup-box h3 { margin: 0 0 10px 0; text-transform: uppercase; letter-spacing: 2px; font-weight: 900; color: white;}
        .im-popup-box p { margin: 0; font-family: monospace; opacity: 0.8; font-size: 12px; }

        .im-palyazat-drag-handle {
            width: 30px;
            flex-shrink: 0;
            cursor: move;
            cursor: grab;
            font-size: 22px;
            line-height: 1;
            color: #777;
            text-align: center;
            padding-top: 23px;
            user-select: none;
        }

        .im-palyazat-drag-handle:active {
            cursor: grabbing;
        }

        .im-palyazat-row.ui-sortable-helper {
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        }

        .im-palyazat-row-placeholder {
            height: 120px;
            background: #fff8df;
            border: 2px dashed #ffc107;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        @media (max-width: 850px) { .im-card { flex: 0 0 100%; } }
    </style>

    <div id="im-success-popup">
        <div class="im-popup-box">
            <div class="gear-icon">⚙️</div>
            <h3>RENDSZER FRISSÍTVE</h3>
            <p>> beállítások sikeresen mentve</p>
        </div>
    </div>

    <div class="wrap im-admin-wrapper">
        <h1 style="display:flex; align-items:center; margin-bottom: 5px;">IM Functions <span style="font-size:12px; background:#eee; padding:2px 8px; border-radius:4px; margin-left:15px;">v2.2.8</span></h1>
        <p style="margin-top:0; color: #666;">Developed by: <strong>Szente Károly</strong> | Ipari Marketing</p>

        <form method="post" action="options.php">
            <?php
                settings_fields('im-functions-settings-group');
                $options = get_option(IM_FUNCTIONS_OPTIONS);
            ?>

            <div class="im-section-title">Editor & Content UX</div>
            <div class="im-grid">
                <div class="im-card">
                    <div class="im-card-header"><h2>ACF Quick Copy & Paste</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[acf_quick_copy]" value="1" <?php checked(1, !empty($options['acf_quick_copy']) ? $options['acf_quick_copy'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col"><div class="im-desc">Egykattintásos blokkmásolás és beillesztés az ACF Flexible Content mezőkhöz.</div></div>
                    </div>
                </div>

                <div class="im-card">
                    <div class="im-card-header"><h2>ACF Custom Block Title</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[acf_custom_block_title]" value="1" <?php checked(1, !empty($options['acf_custom_block_title']) ? $options['acf_custom_block_title'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col"><div class="im-desc">Kiszedi az ACF mezők címét és a blokk fejrészébe teszi.</div></div>
                    </div>
                </div>

                <div class="im-card">
                    <div class="im-card-header"><h2>Google Docs Copy-Paste Fix</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[google_docs_fix]" value="1" <?php checked(1, !empty($options['google_docs_fix']) ? $options['google_docs_fix'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col"><div class="im-desc">Tisztítja a Google Dokumentumok felesleges formázásait.</div></div>
                    </div>
                </div>

                <div class="im-card">
                    <div class="im-card-header"><h2>File Name Sanitizer</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" id="im_file_cleanup_toggle" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[file_name_cleanup]" value="1" <?php checked(1, !empty($options['file_name_cleanup']) ? $options['file_name_cleanup'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col">
                            <div class="im-desc">Automatikus ékezetmentesítés és szerverbarát fájlnevek feltöltéskor.</div>

                            <div id="im_sanitizer_sub" class="im-sub-settings">
                                <div class="im-sub-option">
                                    <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[append_site_name]" value="1" <?php checked(1, !empty($options['append_site_name']) ? $options['append_site_name'] : 0); ?> /><span class="im-slider"></span></label>
                                    <div class="im-desc" style="font-weight:600;">Cégnév hozzáfűzése a fájlnévhez</div>
                                </div>

                                <div style="font-size:10px; color:#999; margin-left:55px; margin-top:5px; font-family: monospace;">
                                    Rendszer által generált suffix: <span style="color:var(--im-black); font-weight:800;">-<?php
                                        $brand = strtolower(remove_accents(get_bloginfo('name')));
                                        $brand = preg_replace('/[^a-z0-9]/', '', $brand);
                                        echo str_replace(['kft', 'bt', 'khe'], '', $brand);
                                    ?></span>
                                </div>

                                <div style="margin-top:15px; margin-left:55px;">
                                    <div class="im-desc" style="font-weight:600; margin-bottom:5px;">Kizárt kulcsszavak (vesszővel elválasztva):</div>
                                    <input type="text"
                                           name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[excluded_keywords]"
                                           value="<?php echo esc_attr(!empty($options['excluded_keywords']) ? $options['excluded_keywords'] : ''); ?>"
                                           class="im-select"
                                           style="background:#fff; cursor:text;"
                                           placeholder="pl. cps, mt, projekt" />
                                    <div style="font-size:10px; color:#999; margin-top:5px;">Ha ezek bármelyike szerepel a fájlnévben, a rendszer nem fűzi hozzá a cégnevet.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="im-card">
                    <div class="im-card-header"><h2>ACF Image Rename Updater</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[acf_image_rename_updater]" value="1" <?php checked(1, !empty($options['acf_image_rename_updater']) ? $options['acf_image_rename_updater'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col"><div class="im-desc">Automatikusan frissíti az ACF blokkokban tárolt képneveket.</div></div>
                    </div>
                </div>
            </div>

            <div class="im-section-title">Marketing & Tracking</div>
            <div class="im-grid">
                <div class="im-card">
                    <div class="im-card-header"><h2>Contact Info Mask</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[contact_info_mask]" value="1" <?php checked(1, !empty($options['contact_info_mask']) ? $options['contact_info_mask'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col"><div class="im-desc">Elrejti az email címeket és telefonszámokat méréshez.</div></div>
                    </div>
                </div>

                <div class="im-card">
                    <div class="im-card-header"><h2>UTM & User Journey Tracking</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[utm_tracking]" value="1" <?php checked(1, !empty($options['utm_tracking']) ? $options['utm_tracking'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col"><div class="im-desc">Rögzíti az UTM/GCLID paramétereket és a látogató bejárt útvonalát (User Journey). [user_journey] shortcode támogatással.</div></div>
                    </div>
                </div>

                <div class="im-card" style="flex: 0 0 100%;">
                    <div class="im-card-header"><h2>Pályázati Infoblokk Kezelő</h2></div>

                    <div class="im-row">
                        <label class="im-switch">
                            <input type="checkbox" id="im_palyazat_toggle" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_enabled]" value="1" <?php checked(1, !empty($options['palyazat_enabled']) ? $options['palyazat_enabled'] : 0); ?> />
                            <span class="im-slider"></span>
                        </label>

                        <div class="im-content-col" style="width:100%;">
                            <div class="im-desc">Lebegő, bezárható többképes pályázati infósáv megjelenítése a frontend oldalon.</div>

                            <div id="im_palyazat_sub" class="im-sub-settings" style="margin-top: 20px;">

                                <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">

                                    <div style="flex: 1; min-width: 200px;">
                                        <div class="im-desc" style="font-weight:600; margin-bottom:5px;">Blokk pozíciója a képernyőn:</div>

                                        <select name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_position]" class="im-select" style="margin-top:0;">
                                            <option value="bottom-right" <?php selected(!empty($options['palyazat_position']) ? $options['palyazat_position'] : 'bottom-right', 'bottom-right'); ?>>Jobb alsó sarok</option>
                                            <option value="bottom-left" <?php selected(!empty($options['palyazat_position']) ? $options['palyazat_position'] : 'bottom-right', 'bottom-left'); ?>>Bal alsó sarok</option>
                                            <option value="top-right" <?php selected(!empty($options['palyazat_position']) ? $options['palyazat_position'] : 'bottom-right', 'top-right'); ?>>Jobb felső sarok</option>
                                            <option value="top-left" <?php selected(!empty($options['palyazat_position']) ? $options['palyazat_position'] : 'bottom-right', 'top-left'); ?>>Bal felső sarok</option>
                                        </select>
                                    </div>

                                    <div style="flex: 1; min-width: 200px;">
                                        <div class="im-desc" style="font-weight:600; margin-bottom:5px;">Mobil méret a desktop méret %-ában:</div>

                                        <input
                                            type="number"
                                            name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_mobile_scale]"
                                            value="<?php echo esc_attr(isset($options['palyazat_mobile_scale']) ? $options['palyazat_mobile_scale'] : '50'); ?>"
                                            class="im-select"
                                            style="background:#fff; cursor:text; margin-top:0;"
                                            min="1"
                                            max="100"
                                            step="1"
                                            placeholder="50"
                                        />

                                        <div style="font-size:10px; color:#999; margin-top:5px;">
                                            Példa: 50 = mobilon a megadott desktop méretek fele.
                                        </div>
                                    </div>

                                    <div style="flex: 1; min-width: 250px; display: flex; align-items: center; margin-top: 18px;">

                                        <input
                                            type="hidden"
                                            name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_shadow]"
                                            value="0"
                                        />

                                        <label class="im-switch">
                                            <input
                                                type="checkbox"
                                                name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_shadow]"
                                                value="1"
                                                <?php checked(1, isset($options['palyazat_shadow']) ? $options['palyazat_shadow'] : 1); ?>
                                            />
                                            <span class="im-slider"></span>
                                        </label>

                                        <div class="im-desc" style="font-weight:600; margin-left: 15px;">
                                            Dizájn keret és árnyék (Drop Shadow) aktív
                                        </div>
                                    </div>
                                </div>


                                <div class="im-desc" style="font-weight:600; margin-bottom:10px;">
                                    Pályázati infó sávok (Képek és Linkek):
                                </div>

                                <div id="im-palyazat-repeater" style="max-width: 900px;">

                                    <div class="im-palyazat-rows-container">

                                        <?php
                                        $p_items = !empty($options['palyazat_items']) ? $options['palyazat_items'] : array();

                                        if (!empty($p_items)) :

                                            foreach ($p_items as $idx => $p_item) :
                                                ?>

                                                <div class="im-palyazat-row" style="display:flex; gap:15px; align-items:flex-start; margin-bottom:15px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee; position:relative;">

                                                    <div class="im-palyazat-drag-handle" title="Fogd meg és húzd a sorrend módosításához">☰</div>

                                                    <div style="flex:1; min-width:0;">

                                                        <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                            Banner kép:
                                                        </div>

                                                        <div style="display:flex; gap:10px;">
                                                            <input
                                                                type="text"
                                                                name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][<?php echo $idx; ?>][image]"
                                                                value="<?php echo esc_attr(!empty($p_item['image']) ? $p_item['image'] : ''); ?>"
                                                                class="im-select im-palyazat-img-input"
                                                                style="background:#fff; cursor:text; margin-top:0;"
                                                                readonly
                                                                placeholder="Kép URL"
                                                            />

                                                            <button
                                                                type="button"
                                                                class="im-btn im-btn-primary im-palyazat-media-trigger"
                                                                style="height:33px !important; line-height:31px !important; font-size:11px !important; padding:0 15px !important;"
                                                            >
                                                                Tallózás
                                                            </button>
                                                        </div>

                                                        <div class="im-palyazat-preview-box" style="margin-top:8px;">
                                                            <?php if (!empty($p_item['image'])) : ?>
                                                                <img
                                                                    src="<?php echo esc_url($p_item['image']); ?>"
                                                                    style="max-height:50px; border:1px solid #ddd; border-radius:4px;"
                                                                />
                                                            <?php endif; ?>
                                                        </div>

                                                    </div>


                                                    <div style="flex:1; min-width:0;">

                                                        <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                            Kattintási URL link:
                                                        </div>

                                                        <input
                                                            type="text"
                                                            name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][<?php echo $idx; ?>][link]"
                                                            value="<?php echo esc_attr(!empty($p_item['link']) ? $p_item['link'] : ''); ?>"
                                                            class="im-select im-palyazat-link-input"
                                                            style="background:#fff; cursor:text; margin-top:0;"
                                                            placeholder="pl. /rolunk/palyazat/"
                                                        />


                                                        <div style="display:flex; gap:10px; margin-top:12px;">

                                                            <div style="flex:1;">
                                                                <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                                    Desktop szélesség:
                                                                </div>

                                                                <input
                                                                    type="number"
                                                                    name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][<?php echo $idx; ?>][width]"
                                                                    value="<?php echo esc_attr(!empty($p_item['width']) ? $p_item['width'] : ''); ?>"
                                                                    class="im-select im-palyazat-width-input"
                                                                    style="background:#fff; cursor:text; margin-top:0;"
                                                                    min="1"
                                                                    max="3000"
                                                                    placeholder="auto"
                                                                />
                                                            </div>

                                                            <div style="flex:1;">
                                                                <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                                    Desktop magasság:
                                                                </div>

                                                                <input
                                                                    type="number"
                                                                    name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][<?php echo $idx; ?>][height]"
                                                                    value="<?php echo esc_attr(!empty($p_item['height']) ? $p_item['height'] : ''); ?>"
                                                                    class="im-select im-palyazat-height-input"
                                                                    style="background:#fff; cursor:text; margin-top:0;"
                                                                    min="1"
                                                                    max="3000"
                                                                    placeholder="auto"
                                                                />
                                                            </div>

                                                        </div>

                                                        <div style="font-size:10px; color:#999; margin-top:5px;">
                                                            Pixelben. Ha üres, automatikus méret.
                                                        </div>

                                                    </div>


                                                    <button
                                                        type="button"
                                                        class="im-palyazat-row-remove"
                                                        style="background:none; border:none; color:#d63638; font-size:22px; cursor:pointer; font-weight:bold; margin-top:22px;"
                                                        title="Sor törlése"
                                                    >
                                                        &times;
                                                    </button>

                                                </div>

                                                <?php
                                            endforeach;

                                        else :
                                            ?>

                                            <div class="im-palyazat-row" style="display:flex; gap:15px; align-items:flex-start; margin-bottom:15px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee; position:relative;">

                                                <div class="im-palyazat-drag-handle" title="Fogd meg és húzd a sorrend módosításához">☰</div>

                                                <div style="flex:1; min-width:0;">

                                                    <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                        Banner kép:
                                                    </div>

                                                    <div style="display:flex; gap:10px;">
                                                        <input
                                                            type="text"
                                                            name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][0][image]"
                                                            value=""
                                                            class="im-select im-palyazat-img-input"
                                                            style="background:#fff; cursor:text; margin-top:0;"
                                                            readonly
                                                            placeholder="Kép URL"
                                                        />

                                                        <button
                                                            type="button"
                                                            class="im-btn im-btn-primary im-palyazat-media-trigger"
                                                            style="height:33px !important; line-height:31px !important; font-size:11px !important; padding:0 15px !important;"
                                                        >
                                                            Tallózás
                                                        </button>
                                                    </div>

                                                    <div class="im-palyazat-preview-box" style="margin-top:8px;"></div>

                                                </div>


                                                <div style="flex:1; min-width:0;">

                                                    <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                        Kattintási URL link:
                                                    </div>

                                                    <input
                                                        type="text"
                                                        name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][0][link]"
                                                        value=""
                                                        class="im-select im-palyazat-link-input"
                                                        style="background:#fff; cursor:text; margin-top:0;"
                                                        placeholder="pl. /rolunk/palyazat/"
                                                    />


                                                    <div style="display:flex; gap:10px; margin-top:12px;">

                                                        <div style="flex:1;">
                                                            <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                                Desktop szélesség:
                                                            </div>

                                                            <input
                                                                type="number"
                                                                name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][0][width]"
                                                                value=""
                                                                class="im-select im-palyazat-width-input"
                                                                style="background:#fff; cursor:text; margin-top:0;"
                                                                min="1"
                                                                max="3000"
                                                                placeholder="auto"
                                                            />
                                                        </div>

                                                        <div style="flex:1;">
                                                            <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                                                Desktop magasság:
                                                            </div>

                                                            <input
                                                                type="number"
                                                                name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][0][height]"
                                                                value=""
                                                                class="im-select im-palyazat-height-input"
                                                                style="background:#fff; cursor:text; margin-top:0;"
                                                                min="1"
                                                                max="3000"
                                                                placeholder="auto"
                                                            />
                                                        </div>

                                                    </div>

                                                    <div style="font-size:10px; color:#999; margin-top:5px;">
                                                        Pixelben. Ha üres, automatikus méret.
                                                    </div>

                                                </div>


                                                <button
                                                    type="button"
                                                    class="im-palyazat-row-remove"
                                                    style="background:none; border:none; color:#d63638; font-size:22px; cursor:pointer; font-weight:bold; margin-top:22px;"
                                                    title="Sor törlése"
                                                >
                                                    &times;
                                                </button>

                                            </div>

                                        <?php endif; ?>

                                    </div>


                                    <button
                                        type="button"
                                        id="im-add-palyazat-row"
                                        class="im-btn im-btn-primary"
                                        style="height:33px !important; line-height:31px !important; font-size:11px !important; padding:0 15px !important; margin-top:5px;"
                                    >
                                        + Új sor hozzáadása
                                    </button>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="im-section-title">Data Export</div>
            <div class="im-grid">
                <div class="im-card">
                    <div class="im-card-header"><h2>Universal CSV Export</h2></div>
                    <div class="im-row">
                        <label class="im-switch"><input type="checkbox" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[cfdb7_csv_export]" value="1" <?php checked(1, !empty($options['cfdb7_csv_export']) ? $options['cfdb7_csv_export'] : 0); ?> /><span class="im-slider"></span></label>
                        <div class="im-content-col">
                            <div class="im-desc">Űrlap adatok automatikus napi exportálása CSV fájlba (Max 1000 rekord).</div>

                            <?php
                            global $wpdb;
                            $sources = array(
                                'cfdb7'      => array('label' => 'Standard CF7 DB (CFDB7)', 'active' => file_exists(WP_PLUGIN_DIR . '/contact-form-cfdb7')),
                                'vfb'        => array('label' => 'Contact Form Entries (Elementor)', 'active' => file_exists(WP_PLUGIN_DIR . '/contact-form-entries')),
                                'adv_cf7'    => array('label' => 'Advanced CF7 DB', 'active' => false),
                                'breakdance' => array('label' => 'Breakdance Forms', 'active' => defined('BREAKDANCE_VERSION') || file_exists(WP_PLUGIN_DIR . '/breakdance'))
                            );

                            $adv_tables = array($wpdb->prefix.'cf7_vdata', $wpdb->prefix.'cf7dbplugin_submits', $wpdb->prefix.'vsz_cf7_submissions');
                            foreach($adv_tables as $t) {
                                if($wpdb->get_var("SHOW TABLES LIKE '$t'") == $t) { $sources['adv_cf7']['active'] = true; break; }
                            }
                            ?>

                            <select name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[export_db_source]" class="im-select">
                                <?php foreach ($sources as $val => $data) : ?>
                                    <option value="<?php echo $val; ?>"
                                        <?php selected($val, !empty($options['export_db_source']) ? $options['export_db_source'] : 'cfdb7'); ?>
                                        <?php echo !$data['active'] ? 'disabled style="color:#ccc;"' : ''; ?>>
                                        Forrás: <?php echo $data['label']; ?> <?php echo !$data['active'] ? '(Nem elérhető)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if (!empty($options['cfdb7_csv_export'])): ?>
                                <div style="margin-top:20px; display:flex; flex-direction:column; gap:10px;">
                                    <a href="<?php echo esc_url(admin_url('options-general.php?page=im-functions&manual-export=1')); ?>" class="im-btn im-btn-primary" style="height:30px !important; line-height:28px !important; font-size:10px !important; width:fit-content;">Kézi CSV frissítés</a>

                                    <div class="im-csv-links" style="background: #f9f9f9; padding: 10px; border-radius: 6px; border: 1px dashed #ccc;">
                                        <span style="font-size:11px; font-weight:bold; text-transform:uppercase; color:#666; display:block; margin-bottom:8px;">Űrlap exportok állapota:</span>
                                        <?php
                                        $upload_dir = wp_upload_dir();
                                        $csv_path = trailingslashit($upload_dir['basedir']) . 'csv-public/';
                                        $csv_url = trailingslashit($upload_dir['baseurl']) . 'csv-public/';

                                        if (is_dir($csv_path)) {
                                            $files = glob($csv_path . "*.csv");
                                            if (!empty($files)) {
                                                foreach ($files as $file) {
                                                    $fname = basename($file);
                                                    $filesize = filesize($file);

                                                    if ($filesize > 5242880) {
                                                        $count_text = 'Nagy fájl';
                                                    } else {
                                                        $lines = 0;
                                                        if (($fp = fopen($file, 'r')) !== FALSE) {
                                                            while (fgetcsv($fp) !== FALSE) {
                                                                $lines++;
                                                            }
                                                            fclose($fp);
                                                        }
                                                        $count = max(0, $lines - 1);
                                                        $count_text = $count . ' rekord';
                                                    }

                                                    $is_empty = ($count_text === '0 rekord');
                                                    echo '<div style="margin-bottom:4px; opacity:'.($is_empty ? '0.6' : '1').'">';
                                                    echo '<a href="'.$csv_url.$fname.'" target="_blank" style="font-size:11px; color:'.($is_empty ? '#999' : '#0073aa').'; text-decoration:none; font-weight:'.($is_empty ? '400' : '600').';">';
                                                    echo '📂 '.$fname.'</a> ';
                                                    echo '<span style="font-size:10px; color:#666;">('.$count_text.')</span>';
                                                    echo '</div>';
                                                }
                                            } else { echo '<span style="font-size:11px; color:#999;">Még nem generálódott fájl.</span>'; }
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>


            <div class="im-section-title">White-label & Brand UX</div>
            <div class="im-grid">
                <div class="im-card" style="flex: 0 0 100%;">
                    <div class="im-card-header"><h2>Login Page Customizer</h2></div>
                    <div class="im-row">
                        <label class="im-switch">
                            <input type="checkbox" id="im_login_toggle" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[login_customizer]" value="1" <?php checked(1, !empty($options['login_customizer']) ? $options['login_customizer'] : 0); ?> />
                            <span class="im-slider"></span>
                        </label>
                        <div class="im-content-col" style="width:100%;">
                            <div class="im-desc">Személyre szabott bejelentkezési oldal az ügyfeleknek felesleges bővítmények nélkül.</div>

                            <div id="im_login_sub" class="im-sub-settings" style="margin-top: 20px;">
                                <div style="margin-bottom: 15px; max-width: 600px;">
                                    <div class="im-desc" style="font-weight:600; margin-bottom:5px;">Bejelentkező oldal háttérképe:</div>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <input type="text" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[login_bg]" value="<?php echo esc_attr(!empty($options['login_bg']) ? $options['login_bg'] : ''); ?>" class="im-select" style="background:#fff; cursor:text; margin-top:0;" placeholder="Kép URL-je a médiatárból" />
                                        <button type="button" class="im-btn im-btn-primary im-upload-trigger" style="height:33px !important; line-height:31px !important; font-size:11px !important; padding:0 15px !important;">Tallózás</button>
                                    </div>
                                </div>

                                <div style="margin-bottom: 15px; max-width: 600px;">
                                    <div class="im-desc" style="font-weight:600; margin-bottom:5px;">Ügyfél egyedi logója:</div>
                                    <div style="display:flex; gap:10px; align-items:center;">
                                        <input type="text" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[login_logo]" value="<?php echo esc_attr(!empty($options['login_logo']) ? $options['login_logo'] : ''); ?>" class="im-select" style="background:#fff; cursor:text; margin-top:0;" placeholder="Logó URL-je a médiatárból" />
                                        <button type="button" class="im-btn im-btn-primary im-upload-trigger" style="height:33px !important; line-height:31px !important; font-size:11px !important; padding:0 15px !important;">Tallózás</button>
                                    </div>
                                </div>

                                <div style="margin-bottom: 5px;">
                                    <div class="im-desc" style="font-weight:600; margin-bottom:8px;">Elsődleges márka (Gomb) szín:</div>
                                    <input type="text" name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[login_color]" value="<?php echo esc_attr(!empty($options['login_color']) ? $options['login_color'] : '#ffc107'); ?>" class="im-color-picker-field" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 60px; padding-bottom: 50px; text-align: center;">
                <?php submit_button('Beállítások Mentése', 'im-btn im-btn-primary', 'submit', false); ?>
            </div>
        </form>
    </div>


    <script>
    jQuery(document).ready(function($) {

        $('.im-color-picker-field').wpColorPicker();


        $('.im-upload-trigger').on('click', function(e) {
            e.preventDefault();

            var button = $(this);
            var input_field = button.prev('input');

            var custom_uploader = wp.media({
                title: 'Kép kiválasztása',
                button: { text: 'Kép használata' },
                multiple: false
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                input_field.val(attachment.url);
            }).open();
        });


        function showIMSuccess() {
            var $popup = $('#im-success-popup');
            var $box = $('.im-popup-box');

            $popup.css('display', 'flex').hide().fadeIn(200);

            setTimeout(function() {
                $box.addClass('show');
            }, 50);

            setTimeout(function() {
                $box.removeClass('show');
                $popup.fadeOut(300);
            }, 2500);
        }

        if ($('.updated, #setting-error-settings_updated').length > 0) {
            $('.updated, #setting-error-settings_updated').hide();
            showIMSuccess();
        }

        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('settings-updated') || urlParams.get('updated') === 'true') {
            showIMSuccess();
        }


        function toggleSanitizerSub() {
            if ($('#im_file_cleanup_toggle').is(':checked')) {
                $('#im_sanitizer_sub').slideDown(200);
            } else {
                $('#im_sanitizer_sub').slideUp(200);
            }
        }

        toggleSanitizerSub();

        $('#im_file_cleanup_toggle').on('change', function() {
            toggleSanitizerSub();
        });


        function toggleLoginSub() {
            if ($('#im_login_toggle').is(':checked')) {
                $('#im_login_sub').slideDown(200);
            } else {
                $('#im_login_sub').slideUp(200);
            }
        }

        toggleLoginSub();

        $('#im_login_toggle').on('change', function() {
            toggleLoginSub();
        });


        // Pályázati panel csúszka vezérlése
        function togglePalyazatSub() {
            if ($('#im_palyazat_toggle').is(':checked')) {
                $('#im_palyazat_sub').slideDown(200);
            } else {
                $('#im_palyazat_sub').slideUp(200);
            }
        }

        togglePalyazatSub();

        $('#im_palyazat_toggle').on('change', function() {
            togglePalyazatSub();
        });


        // Pályázati sorok indexelése.
        // Így hozzáadás, törlés és drag & drop után is biztosan egyedi indexet kap minden sor.
        function reindexPalyazatRows() {

            $('.im-palyazat-row').each(function(index) {

                $(this).find('.im-palyazat-img-input').attr(
                    'name',
                    '<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][' + index + '][image]'
                );

                $(this).find('.im-palyazat-link-input').attr(
                    'name',
                    '<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][' + index + '][link]'
                );

                $(this).find('.im-palyazat-width-input').attr(
                    'name',
                    '<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][' + index + '][width]'
                );

                $(this).find('.im-palyazat-height-input').attr(
                    'name',
                    '<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][' + index + '][height]'
                );

            });

        }


        // Betöltéskor is normalizáljuk az indexeket
        reindexPalyazatRows();


        // Drag & drop sorrendezés
        $('.im-palyazat-rows-container').sortable({
            items: '.im-palyazat-row',
            handle: '.im-palyazat-drag-handle',
            axis: 'y',
            tolerance: 'pointer',
            placeholder: 'im-palyazat-row-placeholder',

            update: function() {
                reindexPalyazatRows();
            }
        });


        // Új pályázati sor
        $('#im-add-palyazat-row').on('click', function(e) {
            e.preventDefault();

            let palyazatIndex = $('.im-palyazat-row').length;

            let rowTemplate = `
            <div class="im-palyazat-row" style="display:flex; gap:15px; align-items:flex-start; margin-bottom:15px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee; position:relative;">

                <div class="im-palyazat-drag-handle" title="Fogd meg és húzd a sorrend módosításához">☰</div>

                <div style="flex:1; min-width:0;">

                    <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                        Banner kép:
                    </div>

                    <div style="display:flex; gap:10px;">

                        <input
                            type="text"
                            name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][${palyazatIndex}][image]"
                            value=""
                            class="im-select im-palyazat-img-input"
                            style="background:#fff; cursor:text; margin-top:0;"
                            readonly
                            placeholder="Kép URL"
                        />

                        <button
                            type="button"
                            class="im-btn im-btn-primary im-palyazat-media-trigger"
                            style="height:33px !important; line-height:31px !important; font-size:11px !important; padding:0 15px !important;"
                        >
                            Tallózás
                        </button>

                    </div>

                    <div class="im-palyazat-preview-box" style="margin-top:8px;"></div>

                </div>


                <div style="flex:1; min-width:0;">

                    <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                        Kattintási URL link:
                    </div>

                    <input
                        type="text"
                        name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][${palyazatIndex}][link]"
                        value=""
                        class="im-select im-palyazat-link-input"
                        style="background:#fff; cursor:text; margin-top:0;"
                        placeholder="pl. /rolunk/palyazat/"
                    />


                    <div style="display:flex; gap:10px; margin-top:12px;">

                        <div style="flex:1;">

                            <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                Desktop szélesség:
                            </div>

                            <input
                                type="number"
                                name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][${palyazatIndex}][width]"
                                value=""
                                class="im-select im-palyazat-width-input"
                                style="background:#fff; cursor:text; margin-top:0;"
                                min="1"
                                max="3000"
                                placeholder="auto"
                            />

                        </div>


                        <div style="flex:1;">

                            <div class="im-desc" style="font-weight:600; margin-bottom:3px;">
                                Desktop magasság:
                            </div>

                            <input
                                type="number"
                                name="<?php echo IM_FUNCTIONS_OPTIONS; ?>[palyazat_items][${palyazatIndex}][height]"
                                value=""
                                class="im-select im-palyazat-height-input"
                                style="background:#fff; cursor:text; margin-top:0;"
                                min="1"
                                max="3000"
                                placeholder="auto"
                            />

                        </div>

                    </div>

                    <div style="font-size:10px; color:#999; margin-top:5px;">
                        Pixelben. Ha üres, automatikus méret.
                    </div>

                </div>


                <button
                    type="button"
                    class="im-palyazat-row-remove"
                    style="background:none; border:none; color:#d63638; font-size:22px; cursor:pointer; font-weight:bold; margin-top:22px;"
                    title="Sor törlése"
                >
                    &times;
                </button>

            </div>`;

            $('.im-palyazat-rows-container').append(rowTemplate);

            reindexPalyazatRows();
        });


        // Sor törlése
        $(document).on('click', '.im-palyazat-row-remove', function(e) {
            e.preventDefault();

            if ($('.im-palyazat-row').length > 1) {

                $(this).closest('.im-palyazat-row').remove();

                reindexPalyazatRows();

            } else {

                let row = $(this).closest('.im-palyazat-row');

                row.find('input').val('');
                row.find('.im-palyazat-preview-box').html('');

            }
        });


        // Pályázati banner kiválasztása
        $(document).on('click', '.im-palyazat-media-trigger', function(e) {
            e.preventDefault();

            let button = $(this);
            let input_field = button.prev('input');
            let preview_box = button.closest('.im-palyazat-row').find('.im-palyazat-preview-box');

            let custom_uploader = wp.media({
                title: 'Pályázati banner kiválasztása',
                button: { text: 'Kép használata' },
                multiple: false
            }).on('select', function() {

                let attachment = custom_uploader.state().get('selection').first().toJSON();

                input_field.val(attachment.url);

                preview_box.html(
                    '<img src="' +
                    attachment.url +
                    '" style="max-height:50px; border:1px solid #ddd; border-radius:4px;" />'
                );

            }).open();
        });

    });
    </script>
    <?php
}


add_action('admin_init', 'im_functions_register_settings');

function im_functions_register_settings() {

    $default_options = array(
        'acf_custom_block_title' => 0,
        'google_docs_fix' => 0,
        'contact_info_mask' => 0,
        'utm_tracking' => 0,
        'acf_image_rename_updater' => 0,
        'cfdb7_csv_export' => 0,
        'acf_quick_copy' => 1,
        'export_db_source' => 'cfdb7',
        'file_name_cleanup' => 0,
        'append_site_name' => 0,
        'excluded_keywords' => '',
        'login_customizer' => 0,
        'login_bg' => '',
        'login_logo' => '',
        'login_color' => '#ffc107',

        'palyazat_enabled' => 0,
        'palyazat_position' => 'bottom-right',
        'palyazat_shadow' => 1,
        'palyazat_mobile_scale' => '50',
        'palyazat_items' => array()
    );

    $options = get_option(IM_FUNCTIONS_OPTIONS, $default_options);

    if ($options === false) {
        update_option(IM_FUNCTIONS_OPTIONS, $default_options);
    }

    register_setting('im-functions-settings-group', IM_FUNCTIONS_OPTIONS);
}


$options = get_option(IM_FUNCTIONS_OPTIONS);

if (!empty($options['acf_quick_copy'])) require_once plugin_dir_path(__FILE__) . 'acf-quick-copy.php';
if (!empty($options['acf_custom_block_title'])) require_once plugin_dir_path(__FILE__) . 'acf-custom-block-title.php';
if (!empty($options['google_docs_fix'])) require_once plugin_dir_path(__FILE__) . 'google-docs-fix.php';
if (!empty($options['file_name_cleanup'])) require_once plugin_dir_path(__FILE__) . 'file-name-sanitizer.php';
if (!empty($options['contact_info_mask'])) require_once plugin_dir_path(__FILE__) . 'contact-info-mask.php';
if (!empty($options['utm_tracking'])) require_once plugin_dir_path(__FILE__) . 'utm-cookie-handler.php';
if (!empty($options['acf_image_rename_updater'])) require_once plugin_dir_path(__FILE__) . 'acf-image-rename-updater.php';
if (!empty($options['cfdb7_csv_export'])) require_once plugin_dir_path(__FILE__) . 'cfdb7-export-to-csv.php';
if (!empty($options['login_customizer'])) require_once plugin_dir_path( __FILE__ ) . 'login-customizer.php';
if (!empty($options['palyazat_enabled'])) require_once plugin_dir_path(__FILE__) . 'palyazati-infoblokk.php';


require_once plugin_dir_path(__FILE__) . 'im-functions-updater.php';

if (class_exists('IM_Functions_Updater')) {
    new IM_Functions_Updater(__FILE__, IM_FUNCTIONS_VERSION);
}


add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'im_functions_add_settings_link');

function im_functions_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=im-functions">Beállítások</a>';
    array_unshift($links, $settings_link);
    return $links;
}
