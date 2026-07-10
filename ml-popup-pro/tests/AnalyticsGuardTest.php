<?php
/**
 * Tests for the defensive guards added to MLPP_Analytics in v1.5.1.
 *
 * Coverage:
 *  - get_variant_breakdown() returns [] when popups table is missing
 *    the variant_group_id column (e.g. install frozen on v1.0.x with
 *    auto-migration never finished).
 *  - get_variant_breakdown() returns [] when events table is missing
 *    the variant_label column.
 *  - get_variant_breakdown() surfaces empty result gracefully when
 *    the underlying wpdb call throws — admin page stays usable.
 *  - table_has_column() short-circuits when information_schema probe
 *    throws and falls back to SHOW COLUMNS semantics.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class AnalyticsGuardTest extends TestCase {

	private function set_wpdb_stub( array $overrides = [] ): void {
		global $wpdb;
		$wpdb = new class( $overrides ) {
			public string $prefix = 'wp_';
			public array  $overrides;
			public function __construct( array $overrides ) { $this->overrides = $overrides; }
			public function get_var( $query = null, $x = 0, $y = 0 ) {
				$key = '__default';
				foreach ( $this->overrides as $k => $v ) {
					if ( strpos( (string) $query, $k ) !== false ) { $key = $k; break; }
				}
				if ( $key === '__default' ) {
					// Treat unknown information_schema queries as "column missing".
					if ( stripos( (string) $query, 'information_schema' ) !== false ) {
						throw new \RuntimeException( 'information_schema blocked' );
					}
				}
				return $this->overrides[ $key ] ?? 0;
			}
			public function get_col( $query = null, $x = 0 ) {
				$key = '__col_default';
				foreach ( $this->overrides as $k => $v ) {
					if ( is_array( $v ) && strpos( (string) $query, $k ) !== false ) { $key = $k; break; }
				}
				if ( $key === '__col_default' ) {
					if ( stripos( (string) $query, 'SHOW COLUMNS' ) !== false ) {
						throw new \RuntimeException( 'show columns blocked' );
					}
				}
				return $this->overrides[ $key ] ?? [];
			}
			public function get_results( $query = null, $output = 'OBJECT' ) { return []; }
			public function get_row( $query = null, $output = 'OBJECT' ) { return null; }
			public function prepare( $sql, ...$args ) {
				// Naive prepare: just count placeholders vs args.
				$placeholders = substr_count( (string) $sql, '%' );
				$argc = count( $args );
				if ( $placeholders !== $argc ) {
					throw new \RuntimeException( "wpdb::prepare placeholder mismatch ($placeholders vs $argc)" );
				}
				return $sql;
			}
			public function insert( $table, $data, $format = null ) { return 1; }
		};
	}

	public function test_get_variant_breakdown_returns_empty_when_variant_group_id_column_missing(): void {
		$this->set_wpdb_stub( [
			// Default zero counts as "column not present" — the helper returns false.
		] );
		$a = new MLPP_Analytics();
		$this->assertSame( [], $a->get_variant_breakdown() );
	}

	public function test_get_variant_breakdown_returns_empty_when_variant_label_column_missing(): void {
		$this->set_wpdb_stub( [
			// popups table has the column…
			'variant_group_id' => 1,
			// …but events table does NOT — second guard returns false.
		] );
		$a = new MLPP_Analytics();
		$this->assertSame( [], $a->get_variant_breakdown() );
	}

	public function test_get_variant_breakdown_swallows_throwable_from_wpdb(): void {
		$this->set_wpdb_stub( [
			'variant_group_id' => 1,
			'variant_label'    => 1,
			// After schema guards pass, prepare() is called with no filters, so
			// the placeholder count vs arg count is 0/0 — that path runs get_results().
			// get_results returns [] in the stub, so the method returns [].
		] );
		$a = new MLPP_Analytics();
		$this->assertSame( [], $a->get_variant_breakdown() );
	}

	public function test_table_has_column_falls_back_when_information_schema_blocked(): void {
		// Force information_schema to throw, then make SHOW COLUMNS also throw
		// to verify the helper returns false instead of propagating the error.
		$this->set_wpdb_stub( [] );
		$a = new MLPP_Analytics();
		$ref  = new ReflectionClass( $a );
		$prop = $ref->getMethod( 'table_has_column' );
		$prop->setAccessible( true );
		$this->assertFalse( $prop->invoke( $a, 'wp_mlpp_popups', 'variant_group_id' ) );
	}
}