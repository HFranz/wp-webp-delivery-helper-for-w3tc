<?php
/**
 * Cache handler for WebP-aware page caching.
 *
 * @package W3tcWebpHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Handles Vary: Accept header and W3TC page cache key extension
 * to ensure WebP and non-WebP responses are cached separately.
 */
class W3TC_WebP_Cache_Handler {

	/**
	 * Sends a Vary: Accept header, merging with any existing Vary values.
	 *
	 * W3TC's own page cache ignores the Vary header and builds its cache key
	 * internally. This header targets browsers and upstream caches (CDN, reverse proxy)
	 * so they store separate versions for WebP-capable and non-WebP-capable clients.
	 *
	 * @return void
	 */
	public function send_vary_header(): void {
		if ( headers_sent() ) {
			return;
		}

		// Read existing Vary values, append "Accept", and set as a single header.
		$vary_values = array( 'Accept' );

		foreach ( headers_list() as $header ) {
			if ( stripos( $header, 'Vary:' ) === 0 ) {
				$existing    = array_map( 'trim', explode( ',', substr( $header, 5 ) ) );
				$vary_values = array_merge( $vary_values, $existing );
			}
		}

		// Remove duplicates (case-insensitive) and replace existing Vary headers.
		$seen   = array();
		$unique = array();
		foreach ( $vary_values as $value ) {
			$key = strtolower( $value );
			if ( ! isset( $seen[ $key ] ) ) {
				$seen[ $key ] = true;
				$unique[]     = $value;
			}
		}

		header( 'Vary: ' . implode( ', ', $unique ) );
	}

	/**
	 * Extends the W3TC page cache key to separate WebP and non-WebP caches.
	 *
	 * @param string $key Original cache key.
	 * @return string Extended cache key.
	 */
	public function extend_cache_key( string $key ): string {
		$supports_webp = ! empty( $_SERVER['HTTP_ACCEPT'] )
			&& W3TC_WebP_Accept_Header::accepts( $_SERVER['HTTP_ACCEPT'] );

		return $key . ( $supports_webp ? ':webp' : ':no-webp' );
	}
}

