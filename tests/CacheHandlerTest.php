<?php

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * Tests for W3TC_WebP_Cache_Handler.
 *
 * Note: send_vary_header() relies on the PHP built-in functions header(),
 * headers_sent(), and headers_list(), which cannot be mocked without a
 * native extension (uopz / runkit7). That method is covered by code review.
 * extend_cache_key() has no such limitation and is fully tested here.
 */
class CacheHandlerTest extends TestCase {

	private W3TC_WebP_Cache_Handler $handler;

	protected function setUp(): void {
		$this->handler = new W3TC_WebP_Cache_Handler();
	}

	protected function tearDown(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
	}

	// ── extend_cache_key() ────────────────────────────────────────────────────

	public function testCacheKeyGetsWebpSuffixForWebpCapableBrowser(): void {
		$_SERVER['HTTP_ACCEPT'] = 'image/avif,image/webp,*/*;q=0.8';

		$result = $this->handler->extend_cache_key( 'base-key' );

		$this->assertSame( 'base-key:webp', $result );
	}

	public function testCacheKeyGetsNoWebpSuffixForNonWebpBrowser(): void {
		$_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9';

		$result = $this->handler->extend_cache_key( 'base-key' );

		$this->assertSame( 'base-key:no-webp', $result );
	}

	public function testCacheKeyGetsNoWebpSuffixWhenAcceptHeaderIsMissing(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );

		$result = $this->handler->extend_cache_key( 'base-key' );

		$this->assertSame( 'base-key:no-webp', $result );
	}

	public function testCacheKeyGetsNoWebpSuffixWhenWebpIsExplicitlyDenied(): void {
		$_SERVER['HTTP_ACCEPT'] = 'image/webp;q=0,*/*';

		$result = $this->handler->extend_cache_key( 'base-key' );

		$this->assertSame( 'base-key:no-webp', $result );
	}

	public function testOriginalKeyIsPreservedAsPrefix(): void {
		$_SERVER['HTTP_ACCEPT'] = 'image/webp';

		$result = $this->handler->extend_cache_key( 'my:complex:key' );

		$this->assertStringStartsWith( 'my:complex:key:', $result );
	}
}

