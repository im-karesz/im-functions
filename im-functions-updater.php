<?php
// IM Functions saját updater GitHub alapján

if (!class_exists('IM_Functions_Updater')) {

    class IM_Functions_Updater {
        private $plugin_file;
        private $plugin_slug;
        private $repo_owner;
        private $repo_name;
        private $github_api_url;
        private $current_version;

        public function __construct($plugin_file, $repo_owner, $repo_name) {
            $this->plugin_file = $plugin_file;
            $this->plugin_slug = plugin_basename($plugin_file);
            $this->repo_owner = $repo_owner;
            $this->repo_name = $repo_name;
            $this->github_api_url = "https://api.github.com/repos/{$repo_owner}/{$repo_name}/releases/latest";
            $this->current_version = get_plugin_data($plugin_file)['Version'];

            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
            add_action('admin_notices', [$this, 'admin_update_notice']);
        }

        public function check_for_update($transient) {
            if (empty($transient->checked)) {
                return $transient;
            }

            $release = $this->get_latest_release();

            if ($release && version_compare($this->current_version, ltrim($release->tag_name, 'v'), '<')) {
                $plugin_data = [
                    'slug' => dirname($this->plugin_slug),
                    'plugin' => $this->plugin_slug,
                    'new_version' => ltrim($release->tag_name, 'v'),
                    'url' => $release->html_url,
                    'package' => $release->zipball_url,
                ];

                $transient->response[$this->plugin_slug] = (object) $plugin_data;
            }

            return $transient;
        }

        public function plugin_info($res, $action, $args) {
            if ($action !== 'plugin_information' || $args->slug !== dirname($this->plugin_slug)) {
                return $res;
            }

            $release = $this->get_latest_release();

            if ($release) {
                $res = (object) [
                    'name' => 'IM Functions',
                    'slug' => dirname($this->plugin_slug),
                    'version' => ltrim($release->tag_name, 'v'),
                    'author' => '<a href="https://iparimarketing.hu">Szente Károly</a>',
                    'homepage' => $release->html_url,
                    'download_link' => $release->zipball_url,
                    'sections' => [
                        'description' => nl2br($release->body),
                    ],
                ];
            }

            return $res;
        }

        private function get_latest_release() {
            $response = wp_remote_get($this->github_api_url, [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress Plugin Updater',
                ]
            ]);

            if (is_wp_error($response)) {
                return false;
            }

            $body = json_decode(wp_remote_retrieve_body($response));
            return $body;
        }

        public function admin_update_notice() {
            if (!current_user_can('update_plugins')) {
                return;
            }

            $screen = get_current_screen();
            if (empty($screen) || $screen->base !== 'dashboard') {
                return; // Csak dashboard oldalon jelenjen meg
            }

            $release = $this->get_latest_release();
            if (!$release) {
                return;
            }

            $latest_version = ltrim($release->tag_name, 'v');

            if (version_compare($this->current_version, $latest_version, '<')) {
                $update_url = admin_url('update-core.php');

                echo '<div class="notice notice-success is-dismissible">';
                echo '<p><strong>IM Functions:</strong> Új verzió érhető el (' . esc_html($latest_version) . '). ';
                echo '<a href="' . esc_url($update_url) . '">Frissítés most</a>.</p>';
                echo '</div>';
            }
        }
    }

}

// Itt példányosítjuk az updater-t
new IM_Functions_Updater(__FILE__, 'im-karesz', 'im-functions');
