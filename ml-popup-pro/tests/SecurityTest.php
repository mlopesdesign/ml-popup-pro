<?php
/**
 * Tests for MLPP_Security sanitizer.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class SecurityTest extends TestCase {

	public function test_sanitize_choice_returns_default_for_invalid(): void {
		$class  = new ReflectionClass( 'MLPP_Security' );
		$method = $class->getMethod( 'sanitize_choice' );
		$method->setAccessible( true );

		$this->assertSame( 'a', $method->invoke( null, 'a', [ 'a', 'b' ] ) );
		$this->assertSame( 'b', $method->invoke( null, 'zzz', [ 'a', 'b' ] ) );
	}

	public function test_sanitize_popup_truncates_priority(): void {
		$raw   = [ 'name' => 'Teste', 'priority' => 999, 'status' => 'invalid' ];
		$clean = MLPP_Security::sanitize_popup( $raw );
		$this->assertSame( 'Teste', $clean['name'] );
		$this->assertSame( 999, $clean['priority'] );
		$this->assertSame( 'draft', $clean['status'], 'invalid status falls back to draft' );
		$this->assertSame( 100, $clean['variant_split'] );
	}

	public function test_sanitize_popup_strips_dangerous_goal_selectors(): void {
		$raw = [
			'goal_selectors' => [ '.safe-selector', "javascript:alert(1)", '<script>', 'a]b' ],
		];
		$clean = MLPP_Security::sanitize_popup( $raw );
		$decoded = json_decode( $clean['goal_selectors'], true );
		$this->assertSame( [ '.safe-selector' ], $decoded );
	}

	public function test_sanitize_choice_rejects_unknown(): void {
		$class  = new ReflectionClass( 'MLPP_Security' );
		$method = $class->getMethod( 'sanitize_choice' );
		$method->setAccessible( true );
		$this->assertSame( 'x', $method->invoke( null, 'X', [ 'x' ] ) );
	}
}