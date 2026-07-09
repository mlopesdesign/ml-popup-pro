<?php
/**
 * Tests for MLPP_Security sanitizer + MLPP_License status mapping.
 *
 * Pragma: this is a pure-PHP unit suite. Heavier integration tests
 * (Rules scheduling over real WP time, Storage with real wpdb) live
 * behind the WP test suite in a separate run.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class SanitizerAndLicenseTest extends TestCase {

	// ---- Security::sanitize_popup -----------------------------------------

	public function test_sanitize_popup_status_falls_back_to_draft_when_unknown(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'p', 'status' => 'invalid' ] );
		$this->assertSame( 'draft', $clean['status'] );
	}

	public function test_sanitize_popup_priority_is_cast_to_int(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'p', 'priority' => '15' ] );
		$this->assertSame( 15, $clean['priority'] );
	}

	public function test_sanitize_popup_variant_split_is_clamped_0_to_100(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'p', 'variant_split' => 9999 ] );
		$this->assertSame( 100, $clean['variant_split'] );

		$clean2 = MLPP_Security::sanitize_popup( [ 'name' => 'p', 'variant_split' => -50 ] );
		$this->assertSame( 0, $clean2['variant_split'] );
	}

	public function test_sanitize_popup_strips_dangerous_goal_selectors(): void {
		$clean = MLPP_Security::sanitize_popup( [
			'name'           => 'p',
			'goal_selectors' => [
				'.safe-selector',
				'#another-safe',
				'javascript:alert(1)',   // bad scheme
				'data:text/html',         // bad scheme
				'a>b',                    // bad chars
				'<script>',
				'a]b',                    // not in whitelist
			],
		] );
		$decoded = json_decode( $clean['goal_selectors'], true );
		$this->assertSame( [ '.safe-selector', '#another-safe' ], $decoded );
	}

	public function test_sanitize_popup_image_radius_passes_through(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'x', 'image_radius' => '8px' ] );
		$this->assertSame( '8px', $clean['image_radius'] );
	}

	public function test_sanitize_popup_image_link_target_defaults_to_self(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'x', 'image_link_target' => 'download' ] );
		$this->assertSame( '_self', $clean['image_link_target'] );
	}

	// ---- License::map_status ----------------------------------------------

	public function test_license_map_status_active_to_valid(): void {
		$class  = new ReflectionClass( 'MLPP_License' );
		$method = $class->getMethod( 'map_status' );
		$method->setAccessible( true );
		$this->assertSame( 'valid', $method->invoke( null, 'active' ) );
	}

	public function test_license_map_status_expired_passes_through(): void {
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

	public function test_license_map_status_revoked_codes_consistently(): void {
		$class  = new ReflectionClass( 'MLPP_License' );
		$method = $class->getMethod( 'map_status' );
		$method->setAccessible( true );
		foreach ( [ 'suspended', 'cancelled', 'revoked', 'deleted' ] as $code ) {
			$this->assertSame(
				'revoked',
				$method->invoke( null, $code ),
				"$code should map to revoked"
			);
		}
	}

	// ---- License::is_premium ----------------------------------------------

	public function test_license_is_premium_defaults_to_free(): void {
		$GLOBALS['__mlpp_options'][ MLPP_License::OPTION_STATUS ] = MLPP_License::STATUS_FREE;
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_license_is_premium_when_status_valid(): void {
		$GLOBALS['__mlpp_options'][ MLPP_License::OPTION_STATUS ] = MLPP_License::STATUS_VALID;
		$this->assertTrue( MLPP_License::is_premium() );
	}
}