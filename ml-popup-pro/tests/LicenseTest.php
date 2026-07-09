<?php
/**
 * Tests for MLPP_License status mapping.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class LicenseTest extends TestCase {

	/**
	 * @dataProvider provider_hub_status_to_internal
	 */
	public function test_status_map_translates_hub_codes_consistently(
		string $hub, string $expected_internal
	): void {
		$class  = new ReflectionClass( 'MLPP_License' );
		$method = $class->getMethod( 'map_status' );
		$method->setAccessible( true );

		$this->assertSame( $expected_internal, $method->invoke( null, $hub ) );
	}

	public function provider_hub_status_to_internal(): array {
		return [
			'active'              => [ 'active',           'valid' ],
			'expired'             => [ 'expired',          'expired' ],
			'suspended'           => [ 'suspended',        'revoked' ],
			'cancelled'           => [ 'cancelled',        'revoked' ],
			'revoked'             => [ 'revoked',          'revoked' ],
			'deleted'             => [ 'deleted',          'revoked' ],
			'not_found'           => [ 'not_found',        'invalid' ],
			'unknown_product'     => [ 'unknown_product',  'invalid' ],
			'bad_request'         => [ 'bad_request',      'invalid' ],
			'domain_mismatch'     => [ 'domain_mismatch',  'invalid' ],
			'unexpected_unknown'  => [ 'something_else',   'invalid' ],
		];
	}

	public function test_is_premium_returns_false_by_default(): void {
		// Make sure no leftover option / constant is set.
		unset( $GLOBALS['__mlpp_options'][ MLPP_License::OPTION_STATUS ] );
		$GLOBALS['__mlpp_options'][ MLPP_License::OPTION_STATUS ] = MLPP_License::STATUS_FREE;
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_is_premium_returns_true_when_valid_in_options(): void {
		$GLOBALS['__mlpp_options'][ MLPP_License::OPTION_STATUS ] = MLPP_License::STATUS_VALID;
		$this->assertTrue( MLPP_License::is_premium() );
	}
}