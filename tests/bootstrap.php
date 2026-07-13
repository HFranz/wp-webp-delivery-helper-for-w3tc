<?php /** @noinspection PhpUnused */

declare( strict_types=1 );

/*
 * Minimal WordPress stubs for PHPUnit tests without a full WP bootstrap.
 * Only the functions used by the plugin are provided.
 */

define( 'ABSPATH', dirname( __DIR__, 3 ) . '/' );

// ---------------------------------------------------------------------------
// Simple filter/action system
// ---------------------------------------------------------------------------

$GLOBALS['wp_filter'] = array();

function add_filter( string $tag, callable $callback, int $priority = 10 ): bool {
	$GLOBALS['wp_filter'][ $tag ][ $priority ][] = $callback;
	return true;
}

function add_action( string $tag, callable $callback, int $priority = 10 ): bool {
	$GLOBALS['wp_filter'][ $tag ][ $priority ][] = $callback;
	return true;
}

function apply_filters( string $tag, mixed $value, mixed ...$extra ): mixed {
	if ( empty( $GLOBALS['wp_filter'][ $tag ] ) ) {
		return $value;
	}
	ksort( $GLOBALS['wp_filter'][ $tag ] );
	foreach ( $GLOBALS['wp_filter'][ $tag ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$value = $callback( $value, ...$extra );
		}
	}
	return $value;
}

function do_action( string $tag, mixed ...$args ): void {
	if ( empty( $GLOBALS['wp_filter'][ $tag ] ) ) {
		return;
	}
	ksort( $GLOBALS['wp_filter'][ $tag ] );
	foreach ( $GLOBALS['wp_filter'][ $tag ] as $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$callback( ...$args );
		}
	}
}

// ---------------------------------------------------------------------------
// WordPress function stubs (delegate to WPTestStub)
// ---------------------------------------------------------------------------

function wp_upload_dir(): array {
	return WPTestStub::$upload_dir;
}

/** Thin wrapper – parse_url is already a PHP built-in function. */
function wp_parse_url( string $url, int $component = -1 ): mixed {
	return parse_url( $url, $component );
}

function attachment_url_to_postid( string $url ): int {
	// WordPress internally compares against the GUID without the query string.
	$clean = strtok( $url, '?' );
	return WPTestStub::$attachment_ids[ $url ] ?? WPTestStub::$attachment_ids[ $clean ] ?? 0;
}

function get_post_meta( int $post_id, string $key = '', bool $single = false ): mixed {
	if ( $single ) {
		return WPTestStub::$post_meta[ $post_id ][ $key ] ?? '';
	}
	return WPTestStub::$post_meta[ $post_id ][ $key ] ?? array();
}

function home_url( string $path = '' ): string {
	return rtrim( WPTestStub::$home_url, '/' ) . '/' . ltrim( $path, '/' );
}

function plugin_dir_path( string $file ): string {
	return rtrim( dirname( $file ), '/\\' ) . '/';
}

function wp_unslash( mixed $value ): mixed {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

function sanitize_text_field( string $str ): string {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $str ) ) );
}

// ---------------------------------------------------------------------------
// Configurable stub data store
// ---------------------------------------------------------------------------

class WPTestStub {

	/** @var array{baseurl: string, basedir: string} */
	public static array $upload_dir = array(
		'baseurl' => 'http://example.com/wp-content/uploads',
		'basedir' => '/srv/uploads',
	);

	/** url => post_id */
	public static array $attachment_ids = array();

	/** post_id => [ meta_key => value ] */
	public static array $post_meta = array();

	public static string $home_url = 'http://example.com';

	/** Simulated pre-existing response headers (for headers_list()). */
	public static array $existing_headers = array();

	/** Headers sent by the plugin (collected by the header() stub). */
	public static array $sent_headers = array();

	/** Resets all mock data (without clearing registered filters/actions). */
	public static function reset(): void {
		self::$attachment_ids   = array();
		self::$post_meta        = array();
		self::$existing_headers = array();
		self::$sent_headers     = array();
		self::$upload_dir       = array(
			'baseurl' => 'http://example.com/wp-content/uploads',
			'basedir' => '/srv/uploads',
		);
		self::$home_url         = 'http://example.com';
	}

	/** Registers an attachment as successfully converted by W3TC. */
	public static function registerConverted( int $id, string $absolute_url ): void {
		self::$attachment_ids[ $absolute_url ]       = $id;
		self::$post_meta[ $id ]['w3tc_imageservice'] = array( 'status' => 'converted' );
	}

	/** Registers an attachment as not converted. */
	public static function registerNotConverted( int $id, string $absolute_url ): void {
		self::$attachment_ids[ $absolute_url ]       = $id;
		self::$post_meta[ $id ]['w3tc_imageservice'] = array( 'status' => 'pending' );
	}
}

// ---------------------------------------------------------------------------
// Load plugin (registers add_filter / add_action)
// ---------------------------------------------------------------------------

// Simulate W3TC as active and fire plugins_loaded so the guard takes effect.
const W3TC = true;

require_once dirname( __DIR__ ) . '/w3tc-webp-helper.php';

do_action( 'plugins_loaded' );
