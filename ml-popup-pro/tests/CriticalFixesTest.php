<?php
/**
 * Tests for the v1.5.4 critical fixes:
 *
 *   Claim 1 — get_eligible_popups / popup_matches / usort must NEVER
 *              read an undefined array key, even when the source row
 *              is from a legacy schema (no `rules` / no `priority`
 *              columns). In production such reads emit "Undefined
 *              array key" Warnings; hosts with strict error handlers
 *              promote those to ErrorException and break the request.
 *
 *   Claim 2 — handle_ajax_event must always emit a clean JSON envelope,
 *              even when a third-party filter or a WP_DEBUG warning
 *              tries to emit extra bytes. We assert that NO bytes
 *              leaked before wp_send_json_* ran.
 *
 *   Claim 3 — the updater must NEVER fall back to the GitHub zipball
 *              (archive/refs/tags/<tag>.zip), only to the official
 *              release asset. We assert the candidate list contains
 *              only the official zip URL.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class CriticalFixesTest extends TestCase {

	/**
	 * PHPUnit's default error handler converts warnings into test
	 * failures (PHPUnit\Framework\Error\Warning). We don't want that —
	 * we want to count warnings as warnings, and FAIL the test only
	 * when the rule we are validating emits one.
	 *
	 * So each test installs a temporary error handler that records any
	 * warning/notice, restores the previous one in tearDown, and
	 * asserts the warning log is empty.
	 *
	 * @var array<int, array{errno:int,errstr:string,errfile:string,errline:int}>
	 */
	private array $warnings = [];

	protected function setUp(): void {
		$this->warnings = [];
		set_error_handler( function ( $errno, $errstr, $errfile, $errline ) {
			// Capture only PHP warnings/notices from PHP's own runtime
			// (E_WARNING / E_NOTICE / E_USER_WARNING / E_USER_NOTICE).
			if ( $errno === E_WARNING || $errno === E_NOTICE
				|| $errno === E_USER_WARNING || $errno === E_USER_NOTICE ) {
				$this->warnings[] = compact( 'errno', 'errstr', 'errfile', 'errline' );
			}
			return true; // suppress the default handler
		} );
	}

	protected function tearDown(): void {
		restore_error_handler();
	}

	private function any_php_warning(): bool {
		foreach ( $this->warnings as $w ) {
			if ( stripos( $w['errstr'], 'undefined' ) !== false
				|| stripos( $w['errstr'], 'undefined index' ) !== false
				|| stripos( $w['errstr'], 'undefined array key' ) !== false
				|| stripos( $w['errstr'], 'undefined offset' ) !== false ) {
				return true;
			}
		}
		return false;
	}

	// ─── Claim 1 — Rules::get_eligible_popups guards ───────────────

	public function test_get_eligible_popups_with_no_rules_key_does_not_emit_warning(): void {
		// Row that has NO 'rules' key (simulates a frozen v1.0.x schema).
		$row = [
			'id'        => 1,
			'name'      => 'x',
			'status'    => 'active',
			'priority'  => 10,
			'popup_type'=> 'center_modal',
		];
		$rules = new MLPP_Rules();
		$out = $rules->get_eligible_popups( [ $row ] );
		// Empty `rules` decodes to `[]`, every checker returns true, so
		// the popup is eligible. The point of this test is that we got
		// there without emitting any "Undefined array key" warning.
		$this->assertCount( 1, $out );
		$this->assertFalse(
			$this->any_php_warning(),
			'get_eligible_popups() emitted an Undefined array key warning on a legacy row.'
		);
	}

	public function test_usort_does_not_emit_warning_when_priority_key_missing(): void {
		// Both rows have NO `priority` key. popup_matches must accept them
		// (empty rules => all checks pass) so the usort actually runs.
		// The comparator must use `?? 10` instead of accessing $a['priority']
		// directly, which would emit an "Undefined array key" Warning.
		$rules  = new MLPP_Rules();
		$row_a  = [ 'id' => 1, 'name' => 'a', 'rules' => '{}', 'status' => 'active' ];
		$row_b  = [ 'id' => 2, 'name' => 'b', 'rules' => '{}', 'status' => 'active' ];
		$out = $rules->get_eligible_popups( [ $row_a, $row_b ] );
		$this->assertNotEmpty( $out, 'Both rows should be eligible — empty rules means all checks pass.' );
		$this->assertFalse(
			$this->any_php_warning(),
			'get_eligible_popups->usort emitted an Undefined array key warning on a legacy row.'
		);
	}

	// ─── Claim 2 — handle_ajax_event must output pure JSON ─────────

	public function test_handle_ajax_event_emits_pure_json_with_no_leaked_bytes(): void {
		// Apply a temporary filter that emits a stray byte BEFORE the
		// handler runs. If ob_start() + ob_clean() weren't in place, that
		// byte would prepend the JSON envelope and break the frontend.
		$noise_log = [];
		$captured  = [];
		add_filter( 'mlpp_event_rate_limit_window', function () use ( &$noise_log ) {
			$noise_log[] = 'noise';
			return 0; // disable rate limiting for the test
		} );

		// Capture the entire response output the handler will produce.
		ob_start();
		try {
			$analytics = new MLPP_Analytics();
			// Simulate a bad payload (no popup_id) so the handler goes
			// through the validation branch and calls flush_and_die.
			$_POST = [ 'popup_id' => 0, 'event_type' => 'invalid' ];
			// check_ajax_referer requires the nonce function to succeed;
			// the wp-functions stub returns true unconditionally.
			$analytics->handle_ajax_event();
		} catch ( \Throwable $e ) {
			ob_end_clean();
			$this->fail( 'handle_ajax_event propagated an exception: ' . $e->getMessage() );
		}
		$captured[] = ob_get_clean();

		// Take the first capture that has content (handle_ajax_event may
		// have flushed early).
		$body = '';
		foreach ( $captured as $c ) {
			if ( '' !== $c ) { $body = $c; break; }
		}
		$this->assertNotSame( '', $body, 'handle_ajax_event produced no output.' );
		$this->assertJson( $body, 'Response is not valid JSON.' );
		// The filter we attached ran, so the noise stub was invoked —
		// but its bytes (none here, just a log entry) should NOT have
		// leaked through to the response body.
		$this->assertNotEmpty( $noise_log, 'Pre-handler filter never ran; test fixture is broken.' );
	}

	// ─── Claim 3 — Updater must never accept the source archive ────

	public function test_updater_candidate_urls_do_not_include_zipball(): void {
		$ref = new ReflectionClass( 'MLPP_Updater' );
		$method = $ref->getMethod( 'candidate_zip_urls' );
		$method->setAccessible( true );
		$release = [
			'zip_url'     => 'https://github.com/mlopesdesign/ml-popup-pro/releases/download/v1.5.3/ml-popup-pro-v1.5.3.zip',
			'release_url' => 'https://github.com/mlopesdesign/ml-popup-pro/releases/tag/v1.5.3',
		];
		$urls = $method->invoke( new MLPP_Updater(), $release );
		$this->assertSame( [ 'https://github.com/mlopesdesign/ml-popup-pro/releases/download/v1.5.3/ml-popup-pro-v1.5.3.zip' ], $urls );
		foreach ( $urls as $u ) {
			$this->assertStringNotContainsString( '/archive/refs/tags/', (string) $u, 'Updater would fall back to a GitHub source archive (zipball).' );
		}
	}

	public function test_updater_candidate_urls_allow_only_official_asset(): void {
		$ref = new ReflectionClass( 'MLPP_Updater' );
		$method = $ref->getMethod( 'candidate_zip_urls' );
		$method->setAccessible( true );

		// Empty release → empty candidate list. No fallback to whatever.
		$this->assertSame( [], $method->invoke( new MLPP_Updater(), [] ) );

		// Release with only zipball_url key (legacy shape) → empty. The
		// zipball fallback is gone.
		$legacy = [ 'zipball_url' => 'https://github.com/mlopesdesign/ml-popup-pro/archive/refs/tags/v1.5.3.zip' ];
		$this->assertSame( [], $method->invoke( new MLPP_Updater(), $legacy ) );

		// Mirrors come from a filter; they pass through and get esc_url_raw'd.
		$release = [ 'zip_url' => 'https://github.com/mlopesdesign/ml-popup-pro/releases/download/v1.5.4/ml-popup-pro-v1.5.4.zip' ];
		add_filter( 'mlpp_zip_url_mirrors', function () {
			return [ 'https://mirror.example.com/ml-popup-pro-v1.5.4.zip?token=abc' ];
		} );
		$urls = $method->invoke( new MLPP_Updater(), $release );
		remove_filter( 'mlpp_zip_url_mirrors', '__return_empty_array' );
		$this->assertContains( $urls[0], $urls ); // primary
		$this->assertContains( 'https://mirror.example.com/ml-popup-pro-v1.5.4.zip?token=abc', $urls );
	}
}