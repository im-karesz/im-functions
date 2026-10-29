<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'WPINC', 'wp-includes' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'MB_IN_BYTES', 1048576 );
function add_filter() {}
function add_action() {}
function plugin_basename( $file ) { return 'im-functions/' . basename( $file ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function esc_url_raw( $url ) { return filter_var( $url, FILTER_VALIDATE_URL ) ? $url : ''; }
function wp_parse_url( $url ) { return parse_url( $url ); }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function wp_strip_all_tags( $value ) { return strip_tags( $value ); }

require dirname( __DIR__ ) . '/im-functions/im-functions-updater.php';

$canonicalize = static function ( $value ) use ( &$canonicalize ) {
	if ( ! is_array( $value ) ) return $value;
	if ( $value !== array_values( $value ) ) ksort( $value, SORT_STRING );
	foreach ( $value as $key => $item ) $value[ $key ] = $canonicalize( $item );
	return $value;
};
$payload = array(
	'schema' => 1,
	'slug' => 'im-functions',
	'version' => '2.1.0',
	'download_url' => 'https://updater.industrialmarketing.dev/v1/im-functions/package/2.1.0?installation_id=public&expires=' . ( time() + 600 ) . '&signature=test',
	'package_sha256' => str_repeat( 'a', 64 ),
	'package_size' => 2048,
	'requires' => '6.4',
	'requires_php' => '7.4',
	'published_at' => gmdate( 'c' ),
	'expires_at' => time() + 600,
	'changelog' => 'Aláírt tesztkiadás.',
);
$key_file = getenv( 'IM_FUNCTIONS_SIGNING_KEY_FILE' );
if ( ! is_string( $key_file ) || '' === $key_file || ! is_readable( $key_file ) ) {
	echo "IM Functions signed updater test skipped: set IM_FUNCTIONS_SIGNING_KEY_FILE.\n";
	exit( 0 );
}
$secret = base64_decode( trim( (string) file_get_contents( $key_file ) ), true );
$payload['signature'] = base64_encode( sodium_crypto_sign_detached( json_encode( $canonicalize( $payload ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ), $secret ) );

$updater = new IM_Functions_Updater( dirname( __DIR__ ) . '/im-functions/im-functions.php', '2.1.0' );
$method = new ReflectionMethod( $updater, 'normalize_release' );
$normalized = $method->invoke( $updater, $payload );
if ( ! is_array( $normalized ) || '2.1.0' !== $normalized['version'] ) throw new RuntimeException( 'Az érvényes aláírt manifest elutasításra került.' );
$payload['version'] = '9.9.9';
if ( false !== $method->invoke( $updater, $payload ) ) throw new RuntimeException( 'A módosított manifest aláírása elfogadásra került.' );

echo "IM Functions signed updater tests passed.\n";
