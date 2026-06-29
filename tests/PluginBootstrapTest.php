<?php

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * Tests for the main plugin file (w3tc-webp-helper.php).
 *
 * Verifies that the bootstrap correctly registers all hooks with the right
 * object types and priorities after plugins_loaded has fired.
 *
 * The bootstrap (tests/bootstrap.php) defines W3TC and fires plugins_loaded
 * once for the entire test process, so the hooks are registered exactly once.
 */
class PluginBootstrapTest extends TestCase {

	// ── Hook registration ─────────────────────────────────────────────────────

	public function testTheContentFilterIsRegistered(): void {
		$this->assertArrayHasKey( 'the_content', $GLOBALS['wp_filter'] );
	}

	public function testTheContentFilterIsRegisteredAtPriority20(): void {
		$this->assertArrayHasKey( 20, $GLOBALS['wp_filter']['the_content'] );
	}

	public function testTheContentFilterCallbackIsContentFilterInstance(): void {
		$callbacks = $GLOBALS['wp_filter']['the_content'][20];
		$found     = false;

		foreach ( $callbacks as $callback ) {
			if ( is_array( $callback ) && $callback[0] instanceof W3TC_WebP_Content_Filter ) {
				$found = true;
				break;
			}
		}

		$this->assertTrue( $found, 'Expected a W3TC_WebP_Content_Filter instance to be registered for the_content.' );
	}

	public function testSendHeadersActionIsRegistered(): void {
		$this->assertArrayHasKey( 'send_headers', $GLOBALS['wp_filter'] );
	}

	public function testSendHeadersCallbackIsCacheHandlerInstance(): void {
		$found = false;

		foreach ( $GLOBALS['wp_filter']['send_headers'] as $priority_group ) {
			foreach ( $priority_group as $callback ) {
				if ( is_array( $callback ) && $callback[0] instanceof W3TC_WebP_Cache_Handler ) {
					$found = true;
					break 2;
				}
			}
		}

		$this->assertTrue( $found, 'Expected a W3TC_WebP_Cache_Handler instance to be registered for send_headers.' );
	}

	public function testPageCacheCacheKeyFilterIsRegistered(): void {
		$this->assertArrayHasKey( 'w3tc_pagecache_cache_key', $GLOBALS['wp_filter'] );
	}

	public function testPageCacheCacheKeyCallbackIsCacheHandlerInstance(): void {
		$found = false;

		foreach ( $GLOBALS['wp_filter']['w3tc_pagecache_cache_key'] as $priority_group ) {
			foreach ( $priority_group as $callback ) {
				if ( is_array( $callback ) && $callback[0] instanceof W3TC_WebP_Cache_Handler ) {
					$found = true;
					break 2;
				}
			}
		}

		$this->assertTrue( $found, 'Expected a W3TC_WebP_Cache_Handler instance to be registered for w3tc_pagecache_cache_key.' );
	}

	// ── Guard: W3TC not active ────────────────────────────────────────────────
	// When W3TC is not defined, plugins_loaded must not register any hooks.
	// This scenario cannot be tested in the same process (W3TC is already defined
	// as a constant). It is covered by code review of the guard condition in
	// w3tc-webp-helper.php.
}

