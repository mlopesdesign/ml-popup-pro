<?php
/**
 * Tests for MLPP_Security sanitizer + MLPP_License status mapping.
 *
 * A pragmatic starter suite — `composer install && vendor/bin/phpunit`
 * to run. Heavier scheduling/scope integration tests live in a separate
 * suite that boots the WordPress test framework.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class SanitizerAndLicenseTest extends TestCase {

	// ---- Security::sanitize_popup ---------------------------------------

	public function test_sanitize_popup_status_falls_back_to_draft_for_unknown_value(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'Teste', 'status' => 'invalid' ] );
		$this->assertSame( 'draft', $clean['status'] );
	}

	public function test_sanitize_popup_priority_is_int(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'p', 'priority' => '15' ] );
		$this->assertSame( 15, $clean['priority'] );
	}

	public function test_sanitize_popup_variant_split_clamped_to_100(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'v', 'variant_split' => 9999 ] );
		$this->assertSame( 100, $clean['variant_split'] );

		$clean2 = MLPP_Security::sanitize_popup( [ 'name' => 'v', 'variant_split' => -50 ] );
		$this->assertSame( 0, $clean2['variant_split'] );
	}

	public function test_sanitize_popup_strips_dangerous_goal_selectors(): void {
		$clean = MLPP_Security::sanitize_popup( [
			'name'            => 'v',
			'goal_selectors'  => [ '.safe-selector', 'javascript:alert(1)', '<script>', 'a]b' ],
		] );
		$decoded = json_decode( $clean['goal_selectors'], true );
		$this->assertSame( [ '.safe-selector' ], $decoded );
	}

	public function test_sanitize_popup_image_radius_passes_through(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'x', 'image_radius' => '8px' ] );
		$this->assertSame( '8px', $clean['image_radius'] );
	}

	// ---- License::map_status --------------------------------------------

	public function test_license_map_status_active_to_valid(): void {
		$class  = new ReflectionClass( 'MLPP_License' );
		$method = $class->getMethod( 'map_status' );
		$method->setAccessible( true );
		$this->assertSame( 'valid', $method->invoke( null, 'active' ) );
	}

	public function test_license_map_status_expired_passthrough(): void {
		$class  = new ReflectionClass( 'MLPP_License' );
		$method = $class->getMethod( 'map_status' );
		$method->setAccessible( true );
		$this->assertSame( 'expired', $method->invoke( null, 'expired' ) );
	}

	public function test_license_map_status_unknown_status_falls_to_invalid(): void {
		$class  = new ReflectionClass( 'MLPP_License' );
		$method = $class->getMethod( 'map_status' );
		$method->setAccessible( true );
		$this->assertSame( 'invalid', $method->invoke( null, 'weird_status' ) );
	}

	public function test_license_map_status_revoked_codes(): void {
		$class  = new ReflectionClass( 'MLPP_License' );
		$method = $class->getMethod( 'map_status' );
		$method->setAccessible( true );
		foreach ( [ 'suspended', 'cancelled', 'revoked', 'deleted' ] as $code ) {
			$this->assertSame( 'revoked', $method->invoke( null, $code ) );
		}
	}

	public function test_license_is_premium_default_free(): void {
		$GLOBALS['__mlpp_options'][ MLPP_License::OPTION_STATUS ] = MLPP_License::STATUS_FREE;
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_license_is_premium_when_status_valid(): void {
		$GLOBALS['__mlpp_options'][ MLPP_License::OPTION_STATUS ] = MLPP_License::STATUS_VALID;
		$this->assertTrue( MLPP_License::is_premium() );
	}
}