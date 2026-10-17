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
				'.safe-selector',         // valid
				'#another-safe',         // valid
				'input[type="email"]',   // valid (whitelist includes brackets)
				'input[type=submit]',    // valid
				'javascript:alert(1)',   // bad scheme
				'data:text/html',         // bad scheme
				'<script>',               // bad chars
				'a>b',                    // bad chars (< >)
				'foo bar\nbaz',           // newline
			],
		] );
		$decoded = json_decode( $clean['goal_selectors'], true );
		$this->assertSame(
			[ '.safe-selector', '#another-safe', 'input[type="email"]', 'input[type=submit]' ],
			$decoded
		);
	}

	public function test_sanitize_popup_image_radius_passes_through(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'x', 'image_radius' => '8px' ] );
		$this->assertSame( '8px', $clean['image_radius'] );
	}

	public function test_sanitize_popup_image_link_target_defaults_to_self(): void {
		$clean = MLPP_Security::sanitize_popup( [ 'name' => 'x', 'image_link_target' => 'download' ] );
		$this->assertSame( '_self', $clean['image_link_target'] );
	}

	// ---- License (1.6.0: critério do ML License Hub) -------------------------
	// O mapeamento de status do servidor antigo saiu junto com o servidor; os testes
	// abaixo conferem o critério novo (token assinado do Hub), no lugar deles.

	public function test_license_is_premium_defaults_to_locked(): void {
		$key = MLPP_Test_Hub::use_test_key();
		try {
			MLPP_Test_Hub::clear();
			$this->assertFalse( MLPP_License::is_premium() );
			$this->assertTrue( MLPP_License::is_locked() );
		} finally {
			MLPP_Test_Hub::restore_key( $key );
		}
	}

	public function test_license_legacy_valid_status_is_ignored(): void {
		$key = MLPP_Test_Hub::use_test_key();
		try {
			MLPP_Test_Hub::clear();
			update_option( MLPP_License::LEGACY_OPTION_STATUS, 'valid' );
			$this->assertFalse( MLPP_License::is_premium() );
		} finally {
			delete_option( MLPP_License::LEGACY_OPTION_STATUS );
			MLPP_Test_Hub::restore_key( $key );
		}
	}

	public function test_license_is_premium_with_signed_hub_token(): void {
		$key = MLPP_Test_Hub::use_test_key();
		try {
			MLPP_Test_Hub::store( 'license', [ 'plan' => 'annual' ], 'MLPP-ABCDE-12345' );
			$this->assertTrue( MLPP_License::is_premium() );
		} finally {
			MLPP_Test_Hub::clear();
			MLPP_Test_Hub::restore_key( $key );
		}
	}

	public function test_license_token_checked_with_production_key_is_not_accepted_with_test_signature(): void {
		// Sem trocar a chave: um token assinado por outra chave (a dos testes) não vale.
		MLPP_Test_Hub::store( 'license', [ 'plan' => 'annual' ], 'MLPP-ABCDE-12345' );
		try {
			$this->assertFalse( MLPP_License::is_premium() );
		} finally {
			MLPP_Test_Hub::clear();
		}
	}
}
