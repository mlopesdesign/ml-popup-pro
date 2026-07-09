<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Storage {

	/** Last database error from save_popup(), empty when the last save succeeded. */
	public string $last_error = '';

	public function get_all_popups(): array {
		global $wpdb;
		$t = $wpdb->prefix . 'mlpp_popups';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT * FROM {$t} ORDER BY priority DESC, id DESC", ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	public function get_popup( int $id ): ?array {
		global $wpdb;
		$t = $wpdb->prefix . 'mlpp_popups';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE id = %d", $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public function get_active_popups(): array {
		global $wpdb;
		$t = $wpdb->prefix . 'mlpp_popups';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT * FROM {$t} WHERE status = 'active' ORDER BY priority DESC", ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	/**
	 * Insert or update a popup. Returns the popup id on success, or 0 on
	 * failure (check $this->last_error for the database error).
	 */
	public function save_popup( array $data ): int {
		global $wpdb;
		$this->last_error = '';
		$t  = $wpdb->prefix . 'mlpp_popups';
		$id = absint( $data['id'] ?? 0 );
		unset( $data['id'] );

		if ( $id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$ok = $wpdb->update( $t, $data, [ 'id' => $id ] );
			if ( false === $ok ) {
				$this->last_error = $wpdb->last_error;
				return 0;
			}
			return $id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert( $t, $data );
		if ( false === $ok ) {
			$this->last_error = $wpdb->last_error;
			return 0;
		}
		return (int) $wpdb->insert_id;
	}

	public function delete_popup( int $id ): bool {
		global $wpdb;
		$t = $wpdb->prefix . 'mlpp_popups';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->delete( $t, [ 'id' => $id ] ) !== false;
	}

	public function decode_popup( array $row ): array {
		foreach ( [ 'design','triggers','rules','storage_cfg' ] as $f ) {
			$val = $row[ $f ] ?? null;
			if ( is_string( $val ) ) {
				$d   = json_decode( $val, true );
				$val = is_array( $d ) ? $d : [];
			} elseif ( ! is_array( $val ) ) {
				$val = [];
			}
			$row[ $f ] = $val;
		}
		return $row;
	}

	public function export_popups(): array {
		return array_map( [ $this, 'decode_popup' ], $this->get_all_popups() );
	}

	public function import_popups( array $popups, bool $overwrite = false ): array {
		$results = [ 'inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0 ];
		foreach ( $popups as $popup ) {
			if ( ! is_array( $popup ) ) { $results['errors']++; continue; }
			$import_id = absint( $popup['id'] ?? 0 );

			// Accept JSON-string or array forms for the structured fields, then sanitize the whole record.
			foreach ( [ 'design','triggers','rules','storage_cfg' ] as $f ) {
				if ( isset( $popup[ $f ] ) && is_string( $popup[ $f ] ) ) {
					$decoded     = json_decode( $popup[ $f ], true );
					$popup[ $f ] = is_array( $decoded ) ? $decoded : [];
				}
			}
			$clean = MLPP_Security::sanitize_popup( $popup );

			$exists = $import_id > 0 ? $this->get_popup( $import_id ) : null;

			if ( $exists ) {
				if ( ! $overwrite ) {
					$results['skipped']++;
					continue;
				}
				$clean['id'] = $import_id;
				$this->save_popup( $clean );
				$results['updated']++;
			} else {
				unset( $clean['id'] );
				$this->save_popup( $clean );
				$results['inserted']++;
			}
		}
		return $results;
	}
}
