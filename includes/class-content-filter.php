<?php
/**
 * Content filter for WebP URL replacement.
 *
 * @package W3tcWebpHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Replaces image URLs in post content with WebP variants
 * when W3TC has converted them and the browser supports WebP.
 */
class W3TC_WebP_Content_Filter {

	/** @var array<string, string> Per-request URL resolution cache. */
	private array $url_cache = array();

	/** @var array<int, string|null> Per-request attachment meta cache. */
	private array $meta_cache = array();

	/**
	 * Filters the_content: replaces qualifying image URLs with their WebP versions.
	 *
	 * @param string $content Post content HTML.
	 * @return string Filtered content.
	 */
	public function filter( string $content ): string {
		if ( empty( $_SERVER['HTTP_ACCEPT'] ) || ! W3TC_WebP_Accept_Header::accepts( $_SERVER['HTTP_ACCEPT'] ) ) {
			return $content;
		}

		$upload_dir   = wp_upload_dir();
		$uploads_host = strtolower( (string) wp_parse_url( $upload_dir['baseurl'], PHP_URL_HOST ) );
		$uploads_path = (string) wp_parse_url( $upload_dir['baseurl'], PHP_URL_PATH );

		return preg_replace_callback(
			'/(?<![a-zA-Z0-9_-])(src|data-src|srcset|data-srcset)=(["\'])([^"\']+)\2/i',
			function ( $matches ) use ( $uploads_host, $uploads_path ) {
				$attr  = $matches[1];
				$quote = $matches[2];
				$value = $matches[3];

				if ( strcasecmp( $attr, 'srcset' ) === 0 || strcasecmp( $attr, 'data-srcset' ) === 0 ) {
					$value = $this->replace_srcset( $value, $uploads_host, $uploads_path );
				} else {
					$value = $this->replace_url( $value, $uploads_host, $uploads_path );
				}

				return $attr . '=' . $quote . $value . $quote;
			},
			$content
		);
	}

	/**
	 * Replaces a single image URL with its WebP variant if W3TC has converted it.
	 *
	 * @param string $url          Original URL.
	 * @param string $uploads_host Expected uploads host.
	 * @param string $uploads_path Expected uploads path prefix.
	 * @return string WebP URL or original URL.
	 */
	private function replace_url( string $url, string $uploads_host, string $uploads_path ): string {
		if ( array_key_exists( $url, $this->url_cache ) ) {
			return $this->url_cache[ $url ];
		}

		$parsed = wp_parse_url( $url );

		// Root-relative URLs (/wp-content/...) have no host – normalize against
		// home_url() for internal lookups; the output retains the original format.
		$lookup_url = ( empty( $parsed['host'] ) && str_starts_with( $url, '/' ) )
			? home_url( $url )
			: $url;

		$parsed = wp_parse_url( $lookup_url );

		// Check host and path prefix – prevents false positives caused by baseurl in query strings.
		$url_host = strtolower( (string) ( $parsed['host'] ?? '' ) );
		$url_path = (string) ( $parsed['path'] ?? '' );

		if ( $url_host !== $uploads_host || ! str_starts_with( $url_path, $uploads_path ) ) {
			return $this->url_cache[ $url ] = $url;
		}

		// Determine attachment post ID from the absolute URL.
		$attachment_id = attachment_url_to_postid( $lookup_url );

		// Fallback: handle .jpg ↔ .jpeg mismatch (e.g. content has .jpg, file is named .jpeg).
		if ( ! $attachment_id ) {
			$alt_url = preg_replace( '/\.jpeg(?=[?#]|$)/i', '.jpg', $lookup_url );
			if ( $alt_url === $lookup_url ) {
				$alt_url = preg_replace( '/\.jpg(?=[?#]|$)/i', '.jpeg', $lookup_url );
			}
			$attachment_id = attachment_url_to_postid( $alt_url );
		}

		// Fallback: for thumbnail sizes (e.g. -300x300.jpg) strip the size suffix and look up the original.
		if ( ! $attachment_id ) {
			$original_url  = preg_replace( '/-\d+x\d+(\.[^.?#]+)/i', '$1', $lookup_url );
			$attachment_id = attachment_url_to_postid( $original_url );
		}

		if ( ! $attachment_id ) {
			return $this->url_cache[ $url ] = $url;
		}

		// Check W3TC ImageService meta – load only once per attachment ID.
		if ( ! array_key_exists( $attachment_id, $this->meta_cache ) ) {
			$imageservice_data                  = get_post_meta( $attachment_id, 'w3tc_imageservice', true );
			$this->meta_cache[ $attachment_id ] = is_array( $imageservice_data )
				? ( $imageservice_data['status'] ?? null )
				: null;
		}

		if ( 'converted' !== $this->meta_cache[ $attachment_id ] ) {
			return $this->url_cache[ $url ] = $url;
		}

		// Replace extension; query string and fragment are preserved.
		return $this->url_cache[ $url ] = preg_replace( '/\.(jpe?g|png|gif)(?=[?#]|$)/i', '.webp', $url );
	}

	/**
	 * Parses a srcset attribute value and replaces each URL with its WebP variant.
	 *
	 * Parses according to the HTML specification:
	 * https://html.spec.whatwg.org/#parse-a-srcset-attribute
	 *
	 * explode(',', ...) would incorrectly treat commas in query parameters (e.g. ?a=1,2)
	 * as candidate separators. The iterative parser reads the URL as a contiguous
	 * non-whitespace sequence and splits candidates only at standalone commas.
	 *
	 * @param string $srcset       Original srcset value.
	 * @param string $uploads_host Expected uploads host.
	 * @param string $uploads_path Expected uploads path prefix.
	 * @return string Filtered srcset value.
	 */
	private function replace_srcset( string $srcset, string $uploads_host, string $uploads_path ): string {
		$result   = array();
		$position = 0;
		$length   = strlen( $srcset );

		while ( $position < $length ) {
			// 1. Skip leading whitespace and commas (separators between candidates).
			while ( $position < $length && ( ',' === $srcset[ $position ] || ctype_space( $srcset[ $position ] ) ) ) {
				$position++;
			}

			if ( $position >= $length ) {
				break;
			}

			// 2. Collect URL: everything up to the next whitespace (commas in path/query are kept).
			$url_start = $position;
			while ( $position < $length && ! ctype_space( $srcset[ $position ] ) ) {
				$position++;
			}
			$url = rtrim( substr( $srcset, $url_start, $position - $url_start ), ',' );

			if ( '' === $url ) {
				continue;
			}

			// 3. Collect descriptor tokens (e.g. "320w", "2x") until the next comma or end.
			$descriptors = array();
			while ( $position < $length ) {
				while ( $position < $length && ctype_space( $srcset[ $position ] ) ) {
					$position++;
				}

				if ( $position >= $length ) {
					break;
				}

				if ( ',' === $srcset[ $position ] ) {
					$position++;
					break;
				}

				$token_start = $position;
				while ( $position < $length && ! ctype_space( $srcset[ $position ] ) && ',' !== $srcset[ $position ] ) {
					$position++;
				}
				$descriptors[] = substr( $srcset, $token_start, $position - $token_start );
			}

			$new_url  = $this->replace_url( $url, $uploads_host, $uploads_path );
			$result[] = empty( $descriptors ) ? $new_url : $new_url . ' ' . implode( ' ', $descriptors );
		}

		return implode( ', ', $result );
	}
}

