<?php
/**
 * Plugin Name: W3TC WebP Helper
 * Description: Replaces image URLs in post content with WebP versions when the browser supports WebP, based on W3 Total Cache ImageService conversion status.
 * Author: Heinrich Franz
 * Author URI: https://sevmatic.com
 * Plugin URI: https://github.com/HFranz/wp-w3tc-webp-helper
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: w3-total-cache
 *
 * @package W3tcWebpHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-accept-header.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-content-filter.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-cache-handler.php';

/**
 * Register hooks only when W3TC is active.
 * For mu-plugins "Requires Plugins" has no effect – this guard handles the check at runtime.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! defined( 'W3TC' ) ) {
			return;
		}

		$cache_handler = new W3TC_WebP_Cache_Handler();
		add_action( 'send_headers', array( $cache_handler, 'send_vary_header' ) );
		add_filter( 'w3tc_pagecache_cache_key', array( $cache_handler, 'extend_cache_key' ) );

		$content_filter = new W3TC_WebP_Content_Filter();
		add_filter( 'the_content', array( $content_filter, 'filter' ), 20 );
	}
);
