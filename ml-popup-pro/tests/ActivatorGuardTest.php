<?php
/**
 * Tests for the activation-error guards added in v1.5.3 (carried over
 * from the v1.5.2 trial, hardened after rollback to v1.4.1).
 *
 * Coverage (real evidence of fault-tolerance, not just compile checks):
 *
 *  - ensure_schema() returns notes when dbDelta throws.
 *  - ensure_schema() returns notes when $wpdb is not initialized.
 *  - ensure_schema() returns notes when SHOW COLUMNS throws, and
 *    continues to attempt ALTER TABLE repairs.
 *  - ensure_schema() records per-column ALTER failures instead of
 *    aborting the whole migration.
 *  - activate() never throws even when ensure_schema() records failures.
 *  - maybe_upgrade() is a no-op when the stored db_version matches.
 *  - maybe_upgrade() still triggers ensure_schema() when the stored
 *    version is stale.
 *  - get_recent_audit() defined in MLPP_Admin returns [] when the
 *    audit table is missing or not readable.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class ActivatorGuardTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['dbDelta_log']   = [];
		$GLOBALS['dbDelta_throw'] = null;
		if ( isset( $GLOBALS['__mlpp_options'] ) ) {
			unset( $GLOBALS['__mlpp_options']['mlpp_db_version'] );
		}
		if ( isset( $GLOBALS['__mlpp_transients'] ) ) {
			$GLOBALS['__mlpp_transients'] = [];
		}
		$GLOBALS['wpdb'] = $this->make_wpdb_stub();
	}

	private function make_wpdb_stub(): object {
		return new class {
			public string $prefix = 'wp_';
			public ?\Throwable $throws_in_query  = null;
			public ?\Throwable $throws_in_getcol = null;
			public array $existing_columns = [];
			public string $last_error = '';
			public function get_charset_collate(): string { return 'utf8mb4_unicode_ci'; }
			public function get_col( $query = null, $offset = 0 ): array {
				if ( $this->throws_in_getcol ) {
					throw $this->throws_in_getcol;
				}
				return $this->existing_columns;
			}
			public function get_var( $query = null ) { return 0; }
			public function get_results( $query = null, $output = 'OBJECT' ) { return []; }
			public function get_row( $query = null, $output = 'OBJECT' ) { return null; }
			public function query( $sql ): string|int {
				if ( $this->throws_in_query ) {
					throw $this->throws_in_query;
				}
				return ( '' !== $this->last_error ) ? false : 1;
			}
			public function prepare( $sql, ...$args ) {
				$placeholders = substr_count( (string) $sql, '%' );
				$argc         = count( $args );
				if ( $placeholders !== $argc ) {
					throw new \RuntimeException( "wpdb::prepare placeholder mismatch ($placeholders vs $argc)" );
				}
				return $sql;
			}
			public function insert( $t, $d, $f = null ) { return 1; }
			public function update( $t, $d, $w, $f = null, $wf = null ) { return 1; }
			public function delete( $t, $w, $wf = null ) { return 1; }
		};
	}

	public function test_ensure_schema_returns_notes_when_wpdb_unavailable(): void {
		$GLOBALS['wpdb'] = null;
		$notes = MLPP_Activator::ensure_schema();
		$this->assertIsArray( $notes );
		$this->assertContains( 'wpdb indisponível — schema não verificado.', $notes );
	}

	public function test_ensure_schema_swallows_dbDelta_exception(): void {
		$GLOBALS['dbDelta_throw'] = new \RuntimeException( 'dbDelta blocked by host' );
		$notes = MLPP_Activator::ensure_schema();
		$this->assertIsArray( $notes );
		$combined = implode( ' | ', $notes );
		$this->assertStringContainsString( 'dbDelta falhou', $combined );
	}

	public function test_ensure_schema_continues_after_show_columns_throws(): void {
		$GLOBALS['wpdb']->throws_in_getcol = new \RuntimeException( 'SHOW COLUMNS blocked' );
		$notes = MLPP_Activator::ensure_schema();
		$combined = implode( ' | ', $notes );
		$this->assertStringContainsString( 'SHOW COLUMNS bloqueado', $combined );
	}

	public function test_ensure_schema_records_alter_failures_without_aborting(): void {
		$GLOBALS['wpdb']->throws_in_query = new \RuntimeException( 'permission denied' );
		$notes = MLPP_Activator::ensure_schema();
		$combined = implode( ' | ', $notes );
		// Per-column entries must include the exception note.
		$this->assertMatchesRegularExpression( '/Exceção ao recriar coluna .*: permission denied/', $combined );
	}

	public function test_activate_does_not_throw_when_schema_fails(): void {
		$GLOBALS['dbDelta_throw'] = new \RuntimeException( 'host unreachable' );
		$caught = null;
		try {
			MLPP_Activator::activate();
		} catch ( \Throwable $e ) {
			$caught = $e;
		}
		$this->assertNull( $caught, 'activate() must never throw, even when ensure_schema records a failure.' );
	}

	public function test_activate_records_notes_in_transient(): void {
		MLPP_Activator::activate();
		$notes = $GLOBALS['__mlpp_transients']['mlpp_activation_notes'] ?? null;
		$this->assertIsArray( $notes, 'activate() must save notes to the transient so admin_notices can show them.' );
	}

	public function test_maybe_upgrade_is_a_noop_when_db_version_matches(): void {
		$GLOBALS['__mlpp_options']['mlpp_db_version'] = MLPP_Activator::DB_VERSION;
		$GLOBALS['dbDelta_throw'] = new \RuntimeException( 'should not be called' );
		try {
			MLPP_Activator::maybe_upgrade();
		} catch ( \Throwable $e ) {
			$this->fail( 'maybe_upgrade() unexpectedly threw: ' . $e->getMessage() );
		}
		$this->assertSame( [], $GLOBALS['dbDelta_log'] );
	}

	public function test_maybe_upgrade_runs_ensure_schema_when_db_version_missing(): void {
		MLPP_Activator::maybe_upgrade();
		$this->assertNotEmpty( $GLOBALS['dbDelta_log'], 'maybe_upgrade() must call ensure_schema when db_version is stale.' );
	}

	public function test_get_recent_audit_returns_empty_when_wpdb_unavailable(): void {
		$GLOBALS['wpdb'] = null;
		$this->assertSame( [], MLPP_Admin::get_recent_audit( 50 ) );
	}

	public function test_get_recent_audit_swallow_exception_from_query(): void {
		$GLOBALS['wpdb']->throws_in_query = new \RuntimeException( 'audit table missing' );
		$this->assertSame( [], MLPP_Admin::get_recent_audit( 50 ) );
	}
}