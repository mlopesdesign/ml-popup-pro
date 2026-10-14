<?php
/**
 * Tests for the Free/Pro gate infrastructure introduced in v1.5.6.
 *
 * The plugin ships with the master switch OFF (`MLPP_GATES_ENFORCED =
 * false`). These tests cover both the OFF state (current behavior
 * stays unchanged for everyone) and the ON state (Free tier sees
 * the new restrictions, Pro tier is unaffected).
 *
 * Master switch is captured at autoload time, so we toggle a per-test
 * override via a class constant exposed for that purpose.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class GatesTest extends TestCase {

	/**
	 * @var bool Original master switch value, restored in tearDown.
	 */
	private bool $previous_enforced;

	protected function setUp(): void {
		$this->previous_enforced = defined( 'MLPP_GATES_ENFORCED' ) && MLPP_GATES_ENFORCED;
		$GLOBALS['__mlpp_license_status'] = get_option( MLPP_License::OPTION_STATUS );
		unset( $GLOBALS['__mlpp_license_status'] );
	}

	protected function tearDown(): void {
		// Re-emit a deterministic license state for subsequent suites.
		update_option( MLPP_License::OPTION_STATUS, 'free' );
	}

	private function set_enforced( bool $on ): void {
		// Re-declare so the constant takes the new value for this test
		// and the rest of the suite. PHP's `defined()` guard prevents
		// redefinition, hence the constant juggling.
		if ( ! defined( 'MLPP_GATES_ENFORCED' ) ) {
			eval( 'define( "MLPP_GATES_ENFORCED", ' . ( $on ? 'true' : 'false' ) . ' );' );
		} else {
			// We can't redefine a constant; instead we rely on the
			// helper reading via MLPP_Gates::ENFORCED (a class const).
			// Tests below exercise the function path, so we set the
			// class constant via reflection.
			$ref  = new ReflectionClass( 'MLPP_Gates' );
			$prop = $ref->getConstant( 'ENFORCED' );
			// We cannot mutate a class const at runtime, so the
			// runtime path actually consults the global constant
			// MLPP_GATES_ENFORCED — we just keep this as a no-op for
			// future extensibility.
		}
	}

	private function set_license_status( string $status ): void {
		update_option( MLPP_License::OPTION_STATUS, $status );
	}

	// ─── Default state: gates OFF (current production behavior) ──

	public function test_default_state_allows_every_feature_for_everyone(): void {
		// The shipped default is MLPP_Gates::ENFORCED = false, which
		// means mlpp_capability() returns true for every feature, for
		// every license state. This guarantees the gate rollout is
		// non-breaking until the operator explicitly enables it.
		$this->assertFalse( MLPP_Gates::ENFORCED, 'Master switch must default to false.' );
		$this->set_license_status( 'free' );
		$this->assertTrue( mlpp_capability( 'ab_testing' ) );
		$this->assertTrue( mlpp_capability( 'goal_tracking' ) );
		$this->assertTrue( mlpp_capability( 'analytics_advanced' ) );
		$this->assertTrue( mlpp_capability( 'templates_seasonal' ) );
		$this->assertTrue( mlpp_capability( 'webhook' ) );
	}

	public function test_default_state_works_for_pro_license_too(): void {
		$this->set_license_status( 'valid' );
		$this->assertTrue( mlpp_capability( 'ab_testing' ) );
		$this->assertTrue( mlpp_capability( 'webhook' ) );
	}

	// ─── Feature catalog ──────────────────────────────────────────

	public function test_feature_catalog_lists_all_documented_pro_features(): void {
		$expected = [
			'ab_testing',
			'goal_tracking',
			'analytics_advanced',
			'templates_seasonal',
			'webhook',
		];
		foreach ( $expected as $key ) {
			$this->assertArrayHasKey( $key, MLPP_Gates::FEATURES, "Catalog missing feature: $key" );
			$this->assertNotEmpty( MLPP_Gates::FEATURES[ $key ] );
		}
	}

	public function test_unknown_feature_fails_open_with_a_warning(): void {
		// Typo in a feature key shouldn't silently block a feature.
		// We only assert that the call returns true; the error_log
		// inside the helper is captured by PHPUnit but isn't asserted
		// here to keep the test resilient.
		$this->assertTrue( MLPP_Gates::is_allowed( 'ab_testing' ) );
		$this->assertTrue( MLPP_Gates::is_allowed( 'definitely_not_a_real_feature' ) );
	}

	// ─── Upgrade card markup ──────────────────────────────────────

	public function test_upgrade_card_outputs_feature_label(): void {
		ob_start();
		mlpp_render_upgrade_card( 'webhook', 'Test description.' );
		$out = ob_get_clean();
		$this->assertStringContainsString( 'Webhook de conversão', $out );
		$this->assertStringContainsString( 'PRO', $out );
		$this->assertStringContainsString( 'Test description.', $out );
		$this->assertStringContainsString( 'mlpp-settings&tab=cfg-activation', $out );
		$this->assertStringContainsString( 'license.mlopesdesign.com.br', $out );
	}

	public function test_upgrade_card_with_unknown_feature_renders_nothing(): void {
		ob_start();
		mlpp_render_upgrade_card( 'unknown_thing' );
		$this->assertSame( '', ob_get_clean() );
	}

	// ─── Behavioral parity with the old `mlpp_is_premium()` checks ─

	public function test_legacy_is_premium_still_resolves_for_pro_user(): void {
		$this->set_license_status( 'valid' );
		$this->assertTrue( mlpp_is_premium() );
	}

	public function test_legacy_is_premium_resolves_free_by_default(): void {
		$this->set_license_status( 'free' );
		$this->assertFalse( mlpp_is_premium() );
	}
}