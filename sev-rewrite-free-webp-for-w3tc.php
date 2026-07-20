<?php
/**
 * Plugin Name: SEV Rewrite-Free WebP for W3TC
 * Description: Replaces image URLs with WebP versions when supported and available through W3 Total Cache ImageService.
 * Author: Heinrich Franz
 * Author URI: https://sevmatic.com
 * Plugin URI: https://github.com/HFranz/wp-sev-rewrite-free-webp-for-w3tc
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 2.0.0
 * Text Domain: sev-rewrite-free-webp-for-w3tc
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Requires Plugins: w3-total-cache
 *
 * php version 8.0
 *
 * @package WebPDeliveryHelperForW3TC
 */

use WebPDeliveryHelperForW3TC\Cache_Handler;
use WebPDeliveryHelperForW3TC\Content_Filter;

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

		$cache_handler = new Cache_Handler();
		add_action( 'send_headers', array( $cache_handler, 'send_vary_header' ) );
		add_filter( 'w3tc_pagecache_cache_key', array( $cache_handler, 'extend_cache_key' ) );

		$content_filter = new Content_Filter();
		add_filter( 'the_content', array( $content_filter, 'filter' ), 20 );
	}
);
