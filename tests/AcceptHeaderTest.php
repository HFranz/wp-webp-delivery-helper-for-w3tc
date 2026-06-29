<?php

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * Tests for W3TC_WebP_Accept_Header::accepts().
 */
class AcceptHeaderTest extends TestCase {

	/** @dataProvider acceptHeaderProvider */
	#[\PHPUnit\Framework\Attributes\DataProvider( 'acceptHeaderProvider' )]
	public function testAcceptsWebp( string $header, bool $expected ): void {
		$this->assertSame( $expected, W3TC_WebP_Accept_Header::accepts( $header ) );
	}

	public static function acceptHeaderProvider(): array {
		return array(
			'standard browser'            => array( 'image/avif,image/webp,*/*;q=0.8', true ),
			'exact match'                 => array( 'image/webp', true ),
			'exact match uppercase'       => array( 'Image/WebP', true ),
			'exact q=0 not acceptable'    => array( 'image/webp;q=0', false ),
			'exact q=0.001 acceptable'    => array( 'image/webp;q=0.001', true ),
			'image/* wildcard'            => array( 'image/*', true ),
			'image/* q=0'                 => array( 'image/*;q=0', false ),
			'global wildcard'             => array( '*/*', true ),
			'global wildcard q=0'         => array( '*/*;q=0', false ),
			'no webp entry'               => array( 'text/html,application/json', false ),
			'exact q=0 overrides image/*' => array( 'image/webp;q=0,image/*', false ),
			'exact overrides wildcard q0' => array( 'image/webp,*/*;q=0', true ),
			'spaces around tokens'        => array( ' image/webp ; q=1 ', true ),
		);
	}
}

