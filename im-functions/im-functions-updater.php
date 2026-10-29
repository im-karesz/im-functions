<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'IM_Functions_Updater' ) ) {
	final class IM_Functions_Updater {
		const SERVICE_URL = 'https://updater.industrialmarketing.dev';
		const MANIFEST_URL = 'https://updater.industrialmarketing.dev/v1/im-functions/manifest';
		const PUBLIC_KEY = 'beUHpIeUxvqnrpGqdQ2/1Si0XnLa4zlNLM5N7HPuMaA=';
		const CACHE_KEY = 'im_functions_signed_release';
		const CACHE_TTL = 5 * MINUTE_IN_SECONDS;
		const ERROR_CACHE_TTL = 10 * MINUTE_IN_SECONDS;
		const MAX_PACKAGE_BYTES = 50 * MB_IN_BYTES;

		private $plugin_file;
		private $plugin_basename;
		private $plugin_slug = 'im-functions';
		private $current_version;

		public function __construct( $plugin_file, $current_version ) {
			$this->plugin_file = $plugin_file;
			$this->plugin_basename = plugin_basename( $plugin_file );
			$this->current_version = (string) $current_version;
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
			add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
			add_filter( 'upgrader_pre_download', array( $this, 'verify_download' ), 10, 4 );
			add_action( 'admin_notices', array( $this, 'admin_update_notice' ) );
			add_action( 'delete_site_transient_update_plugins', array( $this, 'clear_cache' ) );
			add_action( 'upgrader_process_complete', array( $this, 'clear_cache_after_upgrade' ), 10, 2 );
		}

		public function check_for_update( $transient ) {
			if ( ! is_object( $transient ) || empty( $transient->checked ) ) return $transient;
			$release = $this->latest_release();
			if ( ! $release ) return $transient;
			$payload = $this->update_payload( $release );
			if ( version_compare( $this->current_version, $release['version'], '<' ) ) {
				if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) $transient->response = array();
				$transient->response[ $this->plugin_basename ] = (object) $payload;
			} else {
				if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) $transient->no_update = array();
				$transient->no_update[ $this->plugin_basename ] = (object) $payload;
			}
			return $transient;
		}

		public function plugin_info( $result, $action, $args ) {
			if ( 'plugin_information' !== $action || ! is_object( $args ) || empty( $args->slug ) || $this->plugin_slug !== $args->slug ) return $result;
			$release = $this->latest_release();
			if ( ! $release ) return $result;
			return (object) array(
				'name' => 'IM Functions',
				'slug' => $this->plugin_slug,
				'version' => $release['version'],
				'author' => '<a href="https://iparimarketing.hu">Ipari Marketing - Szente Károly</a>',
				'homepage' => self::SERVICE_URL . '/im-functions',
				'download_link' => $release['download_url'],
				'requires' => $release['requires'],
				'requires_php' => $release['requires_php'],
				'last_updated' => $release['published_at'],
				'external' => true,
				'sections' => array(
					'description' => 'Az Ipari Marketing webhelyein használt közös WordPress-funkciók.',
					'changelog' => nl2br( esc_html( $release['changelog'] ) ),
				),
			);
		}

		public function verify_download( $reply, $package, $upgrader, $hook_extra ) {
			unset( $upgrader );
			if ( false !== $reply || ! $this->is_our_upgrade( $package, $hook_extra ) ) return $reply;
			$release = $this->latest_release( true );
			if ( ! $release ) return new WP_Error( 'im_functions_manifest_unavailable', 'Az aláírt IM Functions-kiadás nem ellenőrizhető.' );
			if ( ! function_exists( 'download_url' ) ) require_once ABSPATH . 'wp-admin/includes/file.php';
			$file = download_url( $release['download_url'], 60, false );
			if ( is_wp_error( $file ) ) return $file;
			$size = is_file( $file ) ? filesize( $file ) : false;
			$hash = is_file( $file ) ? hash_file( 'sha256', $file ) : false;
			if ( false === $size || (int) $release['package_size'] !== (int) $size || ! is_string( $hash ) || ! hash_equals( $release['package_sha256'], strtolower( $hash ) ) ) {
				if ( is_file( $file ) ) unlink( $file );
				return new WP_Error( 'im_functions_integrity_failed', 'A letöltött IM Functions-csomag ellenőrzőösszege vagy mérete eltér az aláírt kiadástól.' );
			}
			return $file;
		}

		public function admin_update_notice() {
			if ( ! current_user_can( 'update_plugins' ) ) return;
			$screen = get_current_screen();
			if ( ! $screen || 'dashboard' !== $screen->base ) return;
			$release = $this->latest_release();
			if ( ! $release || ! version_compare( $this->current_version, $release['version'], '<' ) ) return;
			printf( '<div class="notice notice-info is-dismissible"><p><strong>IM Functions:</strong> Új, digitálisan aláírt verzió érhető el: %1$s. <a href="%2$s">Frissítés megnyitása</a>.</p></div>', esc_html( $release['version'] ), esc_url( admin_url( 'update-core.php' ) ) );
		}

		public function clear_cache() { delete_site_transient( self::CACHE_KEY ); }

		public function clear_cache_after_upgrade( $upgrader, $hook_extra ) {
			unset( $upgrader );
			if ( empty( $hook_extra['type'] ) || 'plugin' !== $hook_extra['type'] ) return;
			$plugins = ! empty( $hook_extra['plugins'] ) ? (array) $hook_extra['plugins'] : ( ! empty( $hook_extra['plugin'] ) ? array( $hook_extra['plugin'] ) : array() );
			if ( in_array( $this->plugin_basename, $plugins, true ) ) $this->clear_cache();
		}

		private function latest_release( $force = false ) {
			if ( ! $this->sodium_available() ) return false;
			if ( ! $force ) {
				$cached = get_site_transient( self::CACHE_KEY );
				if ( is_array( $cached ) && array_key_exists( 'available', $cached ) ) return ! empty( $cached['available'] ) && ! empty( $cached['release'] ) ? $cached['release'] : false;
			}
			$response = wp_safe_remote_get( self::MANIFEST_URL, array( 'timeout' => 15, 'redirection' => 0, 'headers' => array( 'Accept' => 'application/json', 'User-Agent' => 'IM-Functions/' . $this->current_version ) ) );
			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				set_site_transient( self::CACHE_KEY, array( 'available' => false ), self::ERROR_CACHE_TTL );
				return false;
			}
			$release = $this->normalize_release( json_decode( wp_remote_retrieve_body( $response ), true ) );
			if ( ! $release ) {
				set_site_transient( self::CACHE_KEY, array( 'available' => false ), self::ERROR_CACHE_TTL );
				return false;
			}
			$ttl = max( 60, min( self::CACHE_TTL, (int) $release['expires_at'] - time() - 30 ) );
			set_site_transient( self::CACHE_KEY, array( 'available' => true, 'release' => $release ), $ttl );
			return $release;
		}

		private function normalize_release( $payload ) {
			if ( ! is_array( $payload ) || empty( $payload['signature'] ) ) return false;
			$signature = base64_decode( (string) $payload['signature'], true );
			unset( $payload['signature'] );
			$public_key = base64_decode( self::PUBLIC_KEY, true );
			if ( ! is_string( $signature ) || 64 !== strlen( $signature ) || ! is_string( $public_key ) || 32 !== strlen( $public_key ) ) return false;
			if ( ! sodium_crypto_sign_verify_detached( $signature, self::canonical_json( $payload ), $public_key ) ) return false;
			$required = array( 'schema', 'slug', 'version', 'download_url', 'package_sha256', 'package_size', 'requires', 'requires_php', 'published_at', 'expires_at', 'changelog' );
			foreach ( $required as $key ) if ( ! array_key_exists( $key, $payload ) ) return false;
			if ( 1 !== (int) $payload['schema'] || $this->plugin_slug !== $payload['slug'] ) return false;
			$version = trim( (string) $payload['version'] );
			$expires = (int) $payload['expires_at'];
			$download_url = $this->allowed_service_url( $payload['download_url'], '/v1/im-functions/package/' );
			$sha256 = strtolower( trim( (string) $payload['package_sha256'] ) );
			$size = (int) $payload['package_size'];
			if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) || $expires <= time() || $expires > time() + HOUR_IN_SECONDS ) return false;
			if ( ! $download_url || ! preg_match( '/^[a-f0-9]{64}$/', $sha256 ) || $size < 1024 || $size > self::MAX_PACKAGE_BYTES ) return false;
			return array( 'version' => $version, 'download_url' => $download_url, 'package_sha256' => $sha256, 'package_size' => $size, 'requires' => sanitize_text_field( (string) $payload['requires'] ), 'requires_php' => sanitize_text_field( (string) $payload['requires_php'] ), 'published_at' => sanitize_text_field( (string) $payload['published_at'] ), 'expires_at' => $expires, 'changelog' => substr( wp_strip_all_tags( (string) $payload['changelog'] ), 0, 50000 ) );
		}

		private function sodium_available() {
			if ( function_exists( 'sodium_crypto_sign_verify_detached' ) ) return true;
			$compat = defined( 'WPINC' ) ? ABSPATH . WPINC . '/sodium_compat/autoload.php' : '';
			if ( $compat && is_file( $compat ) ) require_once $compat;
			return function_exists( 'sodium_crypto_sign_verify_detached' );
		}

		private function is_our_upgrade( $package, $hook_extra ) {
			$plugins = ! empty( $hook_extra['plugins'] ) ? (array) $hook_extra['plugins'] : ( ! empty( $hook_extra['plugin'] ) ? array( $hook_extra['plugin'] ) : array() );
			if ( in_array( $this->plugin_basename, $plugins, true ) ) return true;
			$parts = wp_parse_url( (string) $package );
			return is_array( $parts ) && 'updater.industrialmarketing.dev' === strtolower( (string) ( $parts['host'] ?? '' ) ) && false !== strpos( (string) ( $parts['path'] ?? '' ), '/v1/im-functions/package/' );
		}

		private function update_payload( $release ) {
			return array( 'id' => self::SERVICE_URL . '/im-functions', 'slug' => $this->plugin_slug, 'plugin' => $this->plugin_basename, 'new_version' => $release['version'], 'url' => self::SERVICE_URL . '/im-functions', 'package' => $release['download_url'], 'requires' => $release['requires'], 'requires_php' => $release['requires_php'] );
		}

		private function allowed_service_url( $url, $path_prefix ) {
			$url = esc_url_raw( (string) $url, array( 'https' ) );
			$parts = $url ? wp_parse_url( $url ) : false;
			if ( ! is_array( $parts ) || 'https' !== ( $parts['scheme'] ?? '' ) || 'updater.industrialmarketing.dev' !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) return '';
			if ( 0 !== strpos( (string) ( $parts['path'] ?? '' ), $path_prefix ) ) return '';
			return $url;
		}

		private static function canonical_json( $value ) { return (string) wp_json_encode( self::canonicalize( $value ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); }

		private static function canonicalize( $value ) {
			if ( ! is_array( $value ) ) return $value;
			if ( $value !== array_values( $value ) ) ksort( $value, SORT_STRING );
			foreach ( $value as $key => $item ) $value[ $key ] = self::canonicalize( $item );
			return $value;
		}
	}
}
