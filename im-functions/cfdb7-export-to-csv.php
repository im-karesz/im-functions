<?php
/**
 * Univerzális CSV Export - VXCF (name/value) & CFDB7 kiterjesztés (Összes űrlap támogatással)
 */

defined('ABSPATH') || exit;

add_action('im_functions_universal_export_cron', 'im_functions_run_universal_export');

add_action('init', function () {
    $options = get_option(IM_FUNCTIONS_OPTIONS);

    // --- 2. Ütemezés kezelése ---
    if (!empty($options['cfdb7_csv_export'])) {
        if (!wp_next_scheduled('im_functions_universal_export_cron')) {
            wp_schedule_event(time(), 'daily', 'im_functions_universal_export_cron');
        }
    } else {
        $timestamp = wp_next_scheduled('im_functions_universal_export_cron');
        if ($timestamp) { wp_unschedule_event($timestamp, 'im_functions_universal_export_cron'); }
    }

    // --- 3. Manuális export indítása (Debug) ---
    if (isset($_GET['manual-export']) && current_user_can('manage_options')) {
        ob_start();
        $source = isset($options['export_db_source']) ? $options['export_db_source'] : 'cfdb7';
        echo "<strong>DEBUG LOG:</strong><br>Forrás: " . esc_html($source) . "<br>";
        im_functions_run_universal_export();
        $debug_log = ob_get_clean();
        set_transient('im_export_debug_log', $debug_log, 30);
        wp_redirect(admin_url('options-general.php?page=im-functions&exported=1'));
        exit;
    }
});

/**
 * Az exportálást végző fő függvény
 */
function im_functions_run_universal_export() {
    global $wpdb;
    $options = get_option(IM_FUNCTIONS_OPTIONS);
    $source = isset($options['export_db_source']) ? $options['export_db_source'] : 'cfdb7';

    @set_time_limit(0);
    wp_suspend_cache_addition(true);

    $upload_dir = wp_upload_dir();
    $csv_path = trailingslashit($upload_dir['basedir']) . 'csv-public/';
    if (!file_exists($csv_path)) wp_mkdir_p($csv_path);

    if ($source === 'breakdance') {
        // --- BREAKDANCE NATÍV MEGOLDÁS (breakdance_form_res CPT) ---
        $sub_posts = $wpdb->get_results("
            SELECT ID, post_date, post_parent
            FROM {$wpdb->prefix}posts
            WHERE post_type = 'breakdance_form_res'
            ORDER BY post_date DESC
            LIMIT 5000
        ", ARRAY_A);

        if ($sub_posts) {
            $forms_data = [];

            foreach ($sub_posts as $sub) {
                $sub_id = $sub['ID'];
                $form_id = $sub['post_parent'];
                $entry = ['date' => $sub['post_date']];

                $form_name = 'Breakdance_Form';
                if ($form_id > 0) {
                    $parent_title = get_the_title($form_id);
                    if ($parent_title) $form_name = $parent_title;
                }

                $metas = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->prefix}postmeta WHERE post_id = %d", $sub_id), ARRAY_A);
                if ($metas) {
                    foreach ($metas as $meta) {
                        $k = $meta['meta_key'];
                        $v = $meta['meta_value'];

                        if (in_array($k, ['_edit_lock', '_edit_last'])) continue;
                        if (stripos($k, 'form_name') !== false || stripos($k, 'form_title') !== false) {
                            $form_name = $v;
                            continue;
                        }

                        $unserialized = @unserialize($v);
                        if (is_array($unserialized)) {
                            foreach ($unserialized as $uk => $uv) { $entry[$uk] = is_array($uv) ? implode(', ', $uv) : $uv; }
                        } else {
                            $json = json_decode($v, true);
                            if (is_array($json)) {
                                foreach ($json as $jk => $jv) { $entry[$jk] = is_array($jv) ? implode(', ', $jv) : $jv; }
                            } else {
                                $entry[$k] = $v;
                            }
                        }
                    }
                }

                if (!isset($forms_data[$form_name])) $forms_data[$form_name] = [];
                // Szigorú 1000 rekordos korlát űrlaponként
                if (count($forms_data[$form_name]) < 1000) {
                    $forms_data[$form_name][] = $entry;
                }
            }

            foreach ($forms_data as $fname => $entries) {
                im_functions_final_csv_writer($fname, $entries, $csv_path);
            }
        }
    }
    elseif ($source === 'adv_cf7') {
        $t_submits = $wpdb->prefix . 'cf7dbplugin_submits';
        $t_vdata = $wpdb->prefix . 'cf7_vdata';
        $t_ventry = $wpdb->prefix . 'cf7_vdata_entry';

        // 1. Variáció: cf7dbplugin_submits
        if ($wpdb->get_var("SHOW TABLES LIKE '$t_submits'") == $t_submits) {
            $form_names = $wpdb->get_col("SELECT DISTINCT form_name FROM $t_submits");
            foreach ($form_names as $f_name) {
                if (empty($f_name)) continue;

                $last_timestamps = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT submit_time FROM $t_submits WHERE form_name = %s ORDER BY submit_time DESC LIMIT 1000", $f_name));

                if ($last_timestamps) {
                    $placeholders = implode(',', array_fill(0, count($last_timestamps), '%s'));
                    $query = $wpdb->prepare("SELECT submit_time, field_name, field_value FROM $t_submits WHERE form_name = %s AND submit_time IN ($placeholders) ORDER BY submit_time DESC", array_merge([$f_name], $last_timestamps));
                    $rows = $wpdb->get_results($query, ARRAY_A);

                    $entries = [];
                    foreach ($rows as $row) {
                        $ts = (string)$row['submit_time'];
                        if (!isset($entries[$ts])) {
                            $clean_ts = explode('.', $ts)[0];
                            $entries[$ts] = ['date' => date('Y-m-d H:i:s', (int)$clean_ts)];
                        }
                        $entries[$ts][$row['field_name']] = $row['field_value'];
                    }
                    im_functions_final_csv_writer($f_name, array_values($entries), $csv_path);
                }
            }
        }

        // 2. Variáció: cf7_vdata & cf7_vdata_entry
        if ($wpdb->get_var("SHOW TABLES LIKE '$t_vdata'") == $t_vdata && $wpdb->get_var("SHOW TABLES LIKE '$t_ventry'") == $t_ventry) {
            $form_ids = $wpdb->get_col("SELECT DISTINCT cf7_id FROM $t_ventry");
            foreach ($form_ids as $f_id) {
                $last_data_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT ve.data_id
                    FROM $t_ventry ve
                    JOIN $t_vdata v ON ve.data_id = v.id
                    WHERE ve.cf7_id = %d
                    ORDER BY v.created DESC
                    LIMIT 1000
                ", $f_id));

                if ($last_data_ids) {
                    $placeholders = implode(',', array_fill(0, count($last_data_ids), '%d'));
                    $query = $wpdb->prepare("
                        SELECT ve.data_id, v.created as date_val, ve.name as field_key, ve.value as field_val
                        FROM $t_ventry ve JOIN $t_vdata v ON ve.data_id = v.id
                        WHERE ve.cf7_id = %d AND ve.data_id IN ($placeholders) ORDER BY v.created DESC
                    ", array_merge([$f_id], $last_data_ids));
                    $rows = $wpdb->get_results($query, ARRAY_A);

                    $entries = [];
                    foreach ($rows as $row) {
                        $did = $row['data_id'];
                        if (!isset($entries[$did])) { $entries[$did] = ['date' => $row['date_val']]; }
                        $entries[$did][$row['field_key']] = $row['field_val'];
                    }
                    $form_title = get_the_title($f_id) ?: 'form-' . $f_id;
                    im_functions_final_csv_writer($form_title, array_values($entries), $csv_path);
                }
            }
        }
    }
    elseif ($source === 'vfb') {
        $table_l = $wpdb->prefix . 'vxcf_leads';
        $table_d = $wpdb->prefix . 'vxcf_leads_detail';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_l'") == $table_l) {
            $form_ids = $wpdb->get_col("SELECT DISTINCT form_id FROM $table_l");
            foreach ($form_ids as $f_id) {
                $last_lead_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $table_l WHERE form_id = %s ORDER BY created DESC LIMIT 1000", $f_id));
                if($last_lead_ids) {
                    $placeholders = implode(',', array_fill(0, count($last_lead_ids), '%d'));
                    $query = $wpdb->prepare("SELECT l.id as lid, l.created as date, d.name, d.value FROM $table_l l JOIN $table_d d ON l.id = d.lead_id WHERE l.id IN ($placeholders) ORDER BY l.created DESC", $last_lead_ids);
                    $rows = $wpdb->get_results($query, ARRAY_A);

                    $entries = [];
                    foreach ($rows as $r) {
                        $id = $r['lid'];
                        if (!isset($entries[$id])) $entries[$id] = ['date' => $r['date']];
                        $entries[$id][$r['name']] = $r['value'];
                    }
                    im_functions_final_csv_writer('VXCF-'.$f_id, array_values($entries), $csv_path);
                }
            }
        }
    } else {
        // STANDARD CFDB7
        $table = $wpdb->prefix . 'db7_forms';
        $cf7_forms = get_posts(array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1));
        foreach ($cf7_forms as $form_post) {
            $rows = $wpdb->get_results($wpdb->prepare("SELECT form_value, form_date FROM $table WHERE form_post_id = %d ORDER BY form_date DESC LIMIT 1000", $form_post->ID), ARRAY_A);
            $entries = [];
            foreach ($rows as $idx => $r) {
                $d = @unserialize($r['form_value']);
                if (is_array($d)) { $d['date'] = $r['form_date']; $entries[$idx] = $d; }
            }
            im_functions_final_csv_writer($form_post->post_title, $entries, $csv_path);
        }
    }
    wp_suspend_cache_addition(false);
}

/**
 * CSV fájl írása (AZ EREDETI FÜGGVÉNYED, VÁLTOZATLANUL)
 */
function im_functions_final_csv_writer($title, $data, $path) {
    $csv_rows = [];
    $keys = [];
    $upload_url = trailingslashit(wp_upload_dir()['baseurl']) . 'cfdb7_uploads/';
    if (!empty($data)) {
        foreach ($data as $e) {
            $m = false;
            foreach ($e as $v) { if (stripos((string)$v, 'iparimarketing') !== false || stripos((string)$v, 'ipari marketing') !== false) { $m = true; break; } }
            if ($m) continue;
            foreach ($e as $k => $v) { if (is_string($v) && strpos($k, 'cfdb7_file') !== false && !empty($v)) { $e[$k] = $upload_url . $v; } }
            if (isset($e['date'])) { $e['date'] = "\u{200C}" . date('Y-m-d H:i', strtotime($e['date'])); }
            $csv_rows[] = $e;
            foreach (array_keys($e) as $k) { $keys[] = $k; }
        }
    }
    if (empty($keys)) { $keys = ['date', 'status']; }
    $keys = array_unique($keys);
    $f_path = $path . sanitize_file_name($title) . '.csv';
    $fp = fopen($f_path, 'w');
    if (!$fp) return;
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($fp, $keys);
    foreach ($csv_rows as $row) {
        $line = [];
        foreach ($keys as $k) { $val = isset($row[$k]) ? $row[$k] : ''; $line[] = is_array($val) ? implode(', ', $val) : $val; }
        fputcsv($fp, $line);
    }
    fclose($fp);
    echo "Mentve: " . esc_html(basename($f_path)) . " (" . count($csv_rows) . " sor)<br>";
}

/**
 * Admin értesítés a manuális export után
 */
add_action('admin_notices', function () {
    if (isset($_GET['exported'])) {
        $log = get_transient('im_export_debug_log');
        if ($log) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>Export folyamat lefutott:</strong></p>';
            echo '<div style="font-family:monospace; background:#eee; padding:10px; font-size:11px; margin-bottom:10px; border:1px solid #ccc;">' . wp_kses_post($log) . '</div></div>';
            delete_transient('im_export_debug_log');
        }
    }
});
