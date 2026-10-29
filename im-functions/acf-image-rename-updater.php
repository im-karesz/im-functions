<?php
/**
 * ACF + Elementor Image Rename Updater
 * Frissíti a fájlneveket az ACF mezőkben és az Elementor (vagy más) page builderek post_content mezőiben is
 */

if (!defined('ABSPATH')) {
    exit;
}

class ACF_Image_Rename_Updater {
    private static $updated_meta_count = 0;
    private static $cache_cleared = false;

    public static function init() {
        add_action('pmr_renaming_successful', [__CLASS__, 'update_all_meta_values'], 10, 2);
        add_action('admin_notices', [__CLASS__, 'admin_notice']);
    }

    public static function update_all_meta_values($old_filename, $new_filename) {
        global $wpdb;

        self::$updated_meta_count = 0;
        self::$cache_cleared = false;

        error_log('--- ACF + Elementor Image Rename Updater START ---');
        error_log('Old filename: ' . $old_filename);
        error_log('New filename: ' . $new_filename);

        // Kezelt kiterjesztések
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf'];

        foreach ($extensions as $ext) {
            $old_full = $old_filename . '.' . $ext;
            $new_full = $new_filename . '.' . $ext;

            // ACF mezők frissítése
            $meta_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",
                    '%' . $wpdb->esc_like($old_full) . '%'
                ),
                ARRAY_A
            );

            if (!empty($meta_rows)) {
                foreach ($meta_rows as $meta) {
                    $meta_id = $meta['meta_id'];
                    $original_value = $meta['meta_value'];
                    $meta_value = maybe_unserialize($original_value);

                    $updated_meta_value = self::replace_in_meta_value($meta_value, $old_full, $new_full);

                    if ($updated_meta_value !== $meta_value) {
                        self::$updated_meta_count++;
                        error_log("Updated meta_id: {$meta_id}");
                        $wpdb->update(
                            $wpdb->postmeta,
                            ['meta_value' => maybe_serialize($updated_meta_value)],
                            ['meta_id' => $meta_id],
                            ['%s'],
                            ['%d']
                        );
                    }
                }
            }

            // post_content (Elementor vagy más) frissítése
            $elementor_posts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_content LIKE %s",
                    '%' . $wpdb->esc_like($old_full) . '%'
                )
            );

            if (!empty($elementor_posts)) {
                foreach ($elementor_posts as $post) {
                    $updated_content = str_replace($old_full, $new_full, $post->post_content);
                    if ($updated_content !== $post->post_content) {
                        wp_update_post([
                            'ID' => $post->ID,
                            'post_content' => $updated_content,
                        ]);
                        self::$updated_meta_count++;
                        error_log("Updated post_content in post ID: {$post->ID}");
                    }
                }
            }
        }

        // Cache törlés, ha van elérhető cache plugin
        self::clear_cache();

        // Admin notice-hoz adat eltárolása
        set_transient('acf_image_rename_updater_notice', [
            'count' => self::$updated_meta_count,
            'cache' => self::$cache_cleared,
        ], 60);

        error_log('--- ACF + Elementor Image Rename Updater END ---');
    }

    private static function replace_in_meta_value($meta_value, $old_full, $new_full) {
        if (is_array($meta_value)) {
            foreach ($meta_value as &$value) {
                $value = self::replace_in_meta_value($value, $old_full, $new_full);
            }
            unset($value);
            return $meta_value;
        } elseif (is_string($meta_value) && strpos($meta_value, $old_full) !== false) {
            return str_replace($old_full, $new_full, $meta_value);
        }
        return $meta_value;
    }

    private static function clear_cache() {
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
            self::$cache_cleared = true;
        }

        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
            self::$cache_cleared = true;
        }

        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
            self::$cache_cleared = true;
        }

        if (function_exists('do_action')) {
            do_action('litespeed_purge_all');
            do_action('swift_performance_clear_cache');
            self::$cache_cleared = true;
        }
    }

    public static function admin_notice() {
        $notice = get_transient('acf_image_rename_updater_notice');

        if ($notice && !empty($notice['count'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>ACF + Elementor Rename:</strong> <?php echo intval($notice['count']); ?> helyen sikeresen frissítve. <?php echo $notice['cache'] ? 'Cache törölve.' : 'Cache nem került törlésre.'; ?></p>
            </div>
            <?php
            delete_transient('acf_image_rename_updater_notice');
        }
    }
}

ACF_Image_Rename_Updater::init();
