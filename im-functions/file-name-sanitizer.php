<?php
/**
 * Intelligens fájlnév tisztító, branding és kulcsszó szűrés
 */

// Csak akkor aktiváljuk a tisztítót, ha valódi médiafeltöltés történik
add_filter('wp_handle_upload_prefilter', 'im_enable_sanitizer_on_upload');

function im_enable_sanitizer_on_upload($file) {
    add_filter('sanitize_file_name', 'im_smart_file_sanitizer', 10);
    return $file;
}

function im_smart_file_sanitizer($filename) {
    $options = get_option('im_functions_options');
    $append_brand = !empty($options['append_site_name']);
    $excluded_input = !empty($options['excluded_keywords']) ? $options['excluded_keywords'] : '';

    $info = pathinfo($filename);
    $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';
    $name = $info['filename'];

    // 1. Alap tisztítás
    $name = remove_accents($name);
    $name = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $name);
    $name = strtolower($name);

    if ($append_brand) {
        // Cégnév generálása
        $brand = strtolower(remove_accents(get_bloginfo('name')));
        $brand = preg_replace('/[^a-z0-9]/', '', $brand);
        $brand = str_replace(['kft', 'bt', 'khe'], '', $brand);
        $brand = trim($brand);

        if (!empty($brand)) {
            $should_append = true;
            $clean_name_for_check = str_replace(['-', '_'], '', $name);

            // A: Ellenőrizzük a fő cégnevet
            if (strpos($clean_name_for_check, $brand) !== false) {
                $should_append = false;
            }

            // B: Ellenőrizzük a kizárt kulcsszavakat
            if ($should_append && !empty($excluded_input)) {
                $keywords = explode(',', $excluded_input);
                foreach ($keywords as $kw) {
                    $kw = trim(strtolower(remove_accents($kw)));
                    if (!empty($kw)) {
                        // Itt is megnézzük kötőjellel és anélkül is
                        $clean_kw = str_replace(['-', '_'], '', $kw);
                        if (strpos($clean_name_for_check, $clean_kw) !== false) {
                            $should_append = false;
                            break;
                        }
                    }
                }
            }

            // Ha egyik szűrő sem talált egyezést, mehet a branding
            if ($should_append) {
                $name .= '-' . $brand;
            }
        }
    }

    $name = preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');

    // Miután lefutott a tisztítás, el kell távolítani a szűrőt, hogy más nevekbe ne nyúljon bele
    remove_filter('sanitize_file_name', 'im_smart_file_sanitizer', 10);

    return $name . $ext;
}
