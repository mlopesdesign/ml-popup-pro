<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Analytics {

	private function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mlpp_events';
	}

	public function record( int $popup_id, string $event_type, string $page_url = '', string $device_type = '' ): void {
		$settings = get_option( 'mlpp_settings', [] );
		if ( ! empty( $settings['disable_analytics'] ) && $settings['disable_analytics'] === '1' ) return;
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $this->table(), [
			'popup_id'    => $popup_id,
			'event_type'  => sanitize_key( $event_type ),
			'page_url'    => esc_url_raw( $page_url ),
			'device_type' => sanitize_key( $device_type ),
			'created_at'  => current_time( 'mysql' ),
		] );
	}

	public function get_totals(): array {
		global $wpdb;
		$t = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT event_type, COUNT(*) as cnt FROM {$t} GROUP BY event_type", ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) $out[ $row['event_type'] ] = (int) $row['cnt'];
		return $out;
	}

	public function get_popup_stats( int $popup_id ): array {
		global $wpdb;
		$t = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT event_type, COUNT(*) as cnt FROM {$t} WHERE popup_id = %d GROUP BY event_type", $popup_id ), ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) $out[ $row['event_type'] ] = (int) $row['cnt'];
		return $out;
	}

	public function get_best_popup(): ?array {
		global $wpdb;
		$t = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$row = $wpdb->get_row( "SELECT popup_id, COUNT(*) as clicks FROM {$t} WHERE event_type IN ('primary_click','secondary_click','image_click','conversion') GROUP BY popup_id ORDER BY clicks DESC LIMIT 1", ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public function get_recent_events( int $limit = 20 ): array {
		global $wpdb;
		$t = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$t} ORDER BY created_at DESC LIMIT %d", $limit ), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	public function get_popup_impressions_by_id(): array {
		global $wpdb;
		$t = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results( "SELECT popup_id, COUNT(*) as cnt FROM {$t} WHERE event_type = 'impression' GROUP BY popup_id", ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) $out[ (int) $row['popup_id'] ] = (int) $row['cnt'];
		return $out;
	}

	public function clear_popup_events( int $popup_id ): void {
		global $wpdb;
		$t = $this->table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $t, [ 'popup_id' => $popup_id ] );
	}

	public function handle_ajax_event(): void {
		check_ajax_referer( 'mlpp_frontend_nonce', 'nonce' );
		$popup_id   = absint( $_POST['popup_id'] ?? 0 );
		$event_type = sanitize_key( $_POST['event_type'] ?? '' );
		$page_url   = esc_url_raw( wp_unslash( $_POST['page_url'] ?? '' ) );
		$device     = sanitize_key( $_POST['device_type'] ?? '' );
		$valid = [ 'impression','open','close','primary_click','secondary_click','image_click','conversion' ];
		if ( ! $popup_id || ! in_array( $event_type, $valid, true ) ) wp_send_json_error( 'invalid' );
		$this->record( $popup_id, $event_type, $page_url, $device );
		wp_send_json_success();
	}
}
