<?php
/**
 * Accept header detection for WebP support.
 *
 * @package W3tcWebpHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Handles detection of WebP support via the HTTP Accept header.
 */
class W3TC_WebP_Accept_Header {

	/**
	 * Case-insensitively checks whether the Accept header accepts image/webp (q > 0).
	 *
	 * Respects specificity according to RFC 7231:
	 *   image/webp  >  image/*  >  *\/*
	 * An explicit q=0 is treated as "not accepted", even if a broader
	 * wildcard entry would theoretically cover image/webp.
	 *
	 * @param string $accept Value of the HTTP Accept header.
	 * @return bool True if image/webp is accepted.
	 */
	public static function accepts( string $accept ): bool {
		$webp_q     = null;
		$image_q    = null;
		$wildcard_q = null;

		foreach ( array_map( 'trim', explode( ',', $accept ) ) as $token ) {
			$parts = array_map( 'trim', explode( ';', $token ) );
			$type  = strtolower( $parts[0] );

			$q = 1.0;
			foreach ( array_slice( $parts, 1 ) as $param ) {
				if ( stripos( $param, 'q=' ) === 0 ) {
					$q = (float) substr( $param, 2 );
					break;
				}
			}

			if ( 'image/webp' === $type ) {
				$webp_q = $q; // Exact match has highest priority.
			} elseif ( 'image/*' === $type && null === $image_q ) {
				$image_q = $q;
			} elseif ( '*/*' === $type && null === $wildcard_q ) {
				$wildcard_q = $q;
			}
		}

		// Most specific match wins.
		if ( null !== $webp_q ) {
			return $webp_q > 0.0;
		}
		if ( null !== $image_q ) {
			return $image_q > 0.0;
		}
		if ( null !== $wildcard_q ) {
			return $wildcard_q > 0.0;
		}

		return false;
	}
}

