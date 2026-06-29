<?php

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * Tests for W3TC_WebP_Content_Filter via the the_content filter.
 *
 * Each test uses unique file names to avoid interference caused by the
 * url_cache / meta_cache instance properties of W3TC_WebP_Content_Filter,
 * which persist for the lifetime of the registered filter object.
 */
class ContentFilterTest extends TestCase {

	private const string BASE = 'http://example.com/wp-content/uploads';

	protected function setUp(): void {
		WPTestStub::reset();
		$_SERVER['HTTP_ACCEPT'] = 'image/avif,image/webp,*/*;q=0.8';
	}

	protected function tearDown(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
	}

	private function filter( string $html ): string {
		return apply_filters( 'the_content', $html );
	}

	// ── WebP detection ──────────────────────────────────────────────────────────

	public function testNonWebpAcceptHeaderLeavesContentUnchanged(): void {
		// No image/webp, no wildcard → not accepted.
		$_SERVER['HTTP_ACCEPT'] = 'text/html,application/xhtml+xml,application/xml;q=0.9';
		WPTestStub::registerConverted( 1, self::BASE . '/no-webp-browser.jpg' );

		$html = '<img src="' . self::BASE . '/no-webp-browser.jpg">';

		$this->assertSame( $html, $this->filter( $html ) );
	}

	public function testMissingAcceptHeaderLeavesContentUnchanged(): void {
		unset( $_SERVER['HTTP_ACCEPT'] );
		WPTestStub::registerConverted( 2, self::BASE . '/missing-accept.jpg' );

		$html = '<img src="' . self::BASE . '/missing-accept.jpg">';

		$this->assertSame( $html, $this->filter( $html ) );
	}

	// ── Simple URL replacement ───────────────────────────────────────────────────

	public function testAbsoluteConvertedUrlIsReplaced(): void {
		WPTestStub::registerConverted( 10, self::BASE . '/abs-converted.jpg' );

		$result = $this->filter( '<img src="' . self::BASE . '/abs-converted.jpg">' );

		$this->assertStringContainsString( 'abs-converted.webp', $result );
		$this->assertStringNotContainsString( 'abs-converted.jpg', $result );
	}

	public function testAbsoluteNotConvertedUrlIsUnchanged(): void {
		WPTestStub::registerNotConverted( 11, self::BASE . '/abs-not-converted.jpg' );

		$result = $this->filter( '<img src="' . self::BASE . '/abs-not-converted.jpg">' );

		$this->assertStringContainsString( 'abs-not-converted.jpg', $result );
		$this->assertStringNotContainsString( 'abs-not-converted.webp', $result );
	}

	public function testNonUploadUrlIsNotTouched(): void {
		$html = '<img src="https://external-cdn.com/images/ext.jpg">';

		$this->assertSame( $html, $this->filter( $html ) );
	}

	// ── Relative URLs ────────────────────────────────────────────────────────────

	public function testRootRelativeUploadUrlIsReplaced(): void {
		// home_url('/wp-content/uploads/rel-img.jpg') → 'http://example.com/wp-content/uploads/rel-img.jpg'
		WPTestStub::registerConverted( 20, self::BASE . '/rel-img.jpg' );

		$result = $this->filter( '<img src="/wp-content/uploads/rel-img.jpg">' );

		// Output stays relative, only the extension changes.
		$this->assertStringContainsString( 'src="/wp-content/uploads/rel-img.webp"', $result );
	}

	// ── Quote preservation ──────────────────────────────────────────────────────

	public function testDoubleQuotesArePreserved(): void {
		WPTestStub::registerConverted( 30, self::BASE . '/dq.jpg' );

		$result = $this->filter( '<img src="' . self::BASE . '/dq.jpg">' );

		$this->assertMatchesRegularExpression( '/src="[^"]+\.webp"/', $result );
	}

	public function testSingleQuotesArePreserved(): void {
		WPTestStub::registerConverted( 31, self::BASE . '/sq.jpg' );

		$result = $this->filter( "<img src='" . self::BASE . "/sq.jpg'>" );

		$this->assertMatchesRegularExpression( "/src='[^']+\.webp'/", $result );
	}

	// ── Attributes ────────────────────────────────────────────────────────────────

	public function testDataSrcIsReplaced(): void {
		WPTestStub::registerConverted( 40, self::BASE . '/data-src.jpg' );

		$result = $this->filter( '<img data-src="' . self::BASE . '/data-src.jpg">' );

		$this->assertStringContainsString( 'data-src.webp', $result );
	}

	public function testDataSrcsetIsReplaced(): void {
		WPTestStub::registerConverted( 41, self::BASE . '/data-srcset.jpg' );

		$result = $this->filter( '<img data-srcset="' . self::BASE . '/data-srcset.jpg 800w">' );

		$this->assertStringContainsString( 'data-srcset.webp 800w', $result );
	}

	// ── srcset parsing ───────────────────────────────────────────────────────────

	public function testSrcsetMultipleEntriesAllReplaced(): void {
		WPTestStub::registerConverted( 50, self::BASE . '/multi-800.jpg' );
		WPTestStub::registerConverted( 51, self::BASE . '/multi-400.jpg' );

		$html   = '<img srcset="' . self::BASE . '/multi-800.jpg 800w, ' . self::BASE . '/multi-400.jpg 400w">';
		$result = $this->filter( $html );

		$this->assertStringContainsString( 'multi-800.webp 800w', $result );
		$this->assertStringContainsString( 'multi-400.webp 400w', $result );
	}

	public function testSrcsetWithCommaInQueryStringParsedCorrectly(): void {
		// URL contains a comma in the query string – must not be treated as a candidate separator.
		$url = self::BASE . '/comma-qs.jpg?v=1,2';
		WPTestStub::registerConverted( 52, $url );

		$result = $this->filter( '<img srcset="' . $url . ' 640w">' );

		// Extension replaced, query string + descriptor preserved.
		$this->assertStringContainsString( 'comma-qs.webp?v=1,2 640w', $result );
	}

	public function testSrcsetPixelDensityDescriptorPreserved(): void {
		WPTestStub::registerConverted( 53, self::BASE . '/retina.jpg' );

		$result = $this->filter( '<img srcset="' . self::BASE . '/retina.jpg 2x">' );

		$this->assertStringContainsString( 'retina.webp 2x', $result );
	}

	// ── Thumbnail fallback ───────────────────────────────────────────────────────

	public function testThumbnailSuffixFallsBackToParentId(): void {
		// Thumbnail URL has no own attachment ID → plugin strips suffix and looks up original.
		WPTestStub::$attachment_ids[ self::BASE . '/thumb-fallback-150x150.jpg' ] = 0;
		WPTestStub::$attachment_ids[ self::BASE . '/thumb-fallback.jpg' ]         = 60;
		WPTestStub::$post_meta[60]['w3tc_imageservice']                           = array( 'status' => 'converted' );

		$result = $this->filter( '<img src="' . self::BASE . '/thumb-fallback-150x150.jpg">' );

		$this->assertStringContainsString( 'thumb-fallback-150x150.webp', $result );
	}

	public function testThumbnailWithoutParentIdIsUnchanged(): void {
		// No parent found → URL unchanged.
		WPTestStub::$attachment_ids[ self::BASE . '/orphan-300x300.jpg' ] = 0;
		// No parent URL registered.

		$result = $this->filter( '<img src="' . self::BASE . '/orphan-300x300.jpg">' );

		$this->assertStringContainsString( 'orphan-300x300.jpg', $result );
		$this->assertStringNotContainsString( 'orphan-300x300.webp', $result );
	}

	// ── .jpg ↔ .jpeg mismatch ────────────────────────────────────────────────────

	public function testJpgInContentMatchesJpegInDatabase(): void {
		WPTestStub::$attachment_ids[ self::BASE . '/mismatch-a.jpg' ]  = 0;
		WPTestStub::$attachment_ids[ self::BASE . '/mismatch-a.jpeg' ] = 70;
		WPTestStub::$post_meta[70]['w3tc_imageservice']                = array( 'status' => 'converted' );

		$result = $this->filter( '<img src="' . self::BASE . '/mismatch-a.jpg">' );

		$this->assertStringContainsString( 'mismatch-a.webp', $result );
	}

	public function testJpegInContentMatchesJpgInDatabase(): void {
		WPTestStub::$attachment_ids[ self::BASE . '/mismatch-b.jpeg' ] = 0;
		WPTestStub::$attachment_ids[ self::BASE . '/mismatch-b.jpg' ]  = 71;
		WPTestStub::$post_meta[71]['w3tc_imageservice']                = array( 'status' => 'converted' );

		$result = $this->filter( '<img src="' . self::BASE . '/mismatch-b.jpeg">' );

		$this->assertStringContainsString( 'mismatch-b.webp', $result );
	}

	// ── Query string and fragment preservation ────────────────────────────────────

	public function testQueryStringIsPreservedAfterExtensionReplacement(): void {
		WPTestStub::registerConverted( 80, self::BASE . '/with-qs.jpg' );

		$result = $this->filter( '<img src="' . self::BASE . '/with-qs.jpg?ver=1.5">' );

		$this->assertStringContainsString( 'with-qs.webp?ver=1.5', $result );
	}

	// ── Meta data edge cases ────────────────────────────────────────────────────

	public function testNonArrayMetaIsHandledGracefully(): void {
		WPTestStub::$attachment_ids[ self::BASE . '/bad-meta.jpg' ] = 90;
		WPTestStub::$post_meta[90]['w3tc_imageservice']             = 'not-an-array';

		$result = $this->filter( '<img src="' . self::BASE . '/bad-meta.jpg">' );

		$this->assertStringContainsString( 'bad-meta.jpg', $result );
		$this->assertStringNotContainsString( 'bad-meta.webp', $result );
	}

	public function testMissingMetaKeyIsHandledGracefully(): void {
		WPTestStub::$attachment_ids[ self::BASE . '/no-meta.jpg' ] = 91;
		// No w3tc_imageservice entry.

		$result = $this->filter( '<img src="' . self::BASE . '/no-meta.jpg">' );

		$this->assertStringContainsString( 'no-meta.jpg', $result );
		$this->assertStringNotContainsString( 'no-meta.webp', $result );
	}

	// ── Attribute boundary safety ──────────────────────────────────────────────

	public function testSrcsetNotMatchedAsSuffixOfDataSrcset(): void {
		// Without a word-boundary check "srcset" starting at position 5 in "data-srcset"
		// would match and strip "data-" from the attribute name.
		WPTestStub::registerConverted( 200, self::BASE . '/boundary.jpg' );

		$html   = '<img data-srcset="' . self::BASE . '/boundary.jpg 800w">';
		$result = $this->filter( $html );

		// Attribute name must remain data-srcset.
		$this->assertStringContainsString( 'data-srcset=', $result );
		$this->assertStringNotContainsString( ' srcset=', $result );
	}

	public function testSrcNotMatchedAsSuffixOfDataSrc(): void {
		WPTestStub::registerConverted( 201, self::BASE . '/boundary-src.jpg' );

		$html   = '<img data-src="' . self::BASE . '/boundary-src.jpg">';
		$result = $this->filter( $html );

		$this->assertStringContainsString( 'data-src=', $result );
		$this->assertStringNotContainsString( ' src=', $result );
	}
}

