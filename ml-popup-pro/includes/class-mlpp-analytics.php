<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Analytics {

	private function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mlpp_events';
	}

	public function record( int $popup_id, string $event_type, string $page_url = '', string $device_type = '', string $variant_label = '' ): void {
		$settings = get_option( 'mlpp_settings', [] );
		if ( ! empty( $settings['disable_analytics'] ) && $settings['disable_analytics'] === '1' ) return;
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $this->table(), [
			'popup_id'      => $popup_id,
			'variant_label' => sanitize_text_field( $variant_label ),
			'event_type'    => sanitize_key( $event_type ),
			'page_url'      => esc_url_raw( $page_url ),
			'device_type'   => sanitize_key( $device_type ),
			'created_at'    => current_time( 'mysql' ),
		] );
	}

	public function get_totals( array $filters = [] ): array {
		global $wpdb;
		$t = $this->table();
		[ $where, $params ] = $this->build_where( $filters );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sql = "SELECT event_type, COUNT(*) as cnt FROM {$t} {$where} GROUP BY event_type";
		$rows = $params
			? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) $out[ $row['event_type'] ] = (int) $row['cnt'];
		return $out;
	}

	public function get_popup_stats( int $popup_id, array $filters = [] ): array {
		global $wpdb;
		$t = $this->table();
		$filters['popup_id'] = $popup_id;
		[ $where, $params ] = $this->build_where( $filters );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sql = "SELECT event_type, COUNT(*) as cnt FROM {$t} {$where} GROUP BY event_type";
		$rows = $params
			? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) $out[ $row['event_type'] ] = (int) $row['cnt'];
		return $out;
	}

	public function get_best_popup( array $filters = [] ): ?array {
		global $wpdb;
		$t = $this->table();
		$filters['event_in'] = [ 'primary_click','secondary_click','image_click','conversion' ];
		[ $where, $params ] = $this->build_where( $filters );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sql = "SELECT popup_id, COUNT(*) as clicks FROM {$t} {$where} GROUP BY popup_id ORDER BY clicks DESC LIMIT 1";
		$row = $params
			? $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A )
			: $wpdb->get_row( $sql, ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public function get_recent_events( int $limit = 20, array $filters = [] ): array {
		global $wpdb;
		$t = $this->table();
		[ $where, $params ] = $this->build_where( $filters );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sql = "SELECT * FROM {$t} {$where} ORDER BY created_at DESC LIMIT %d";
		$params[] = $limit;
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
		return is_array( $rows ) ? $rows : [];
	}

	public function get_popup_impressions_by_id( array $filters = [] ): array {
		global $wpdb;
		$t = $this->table();
		$filters['event_in'] = [ 'impression' ];
		[ $where, $params ] = $this->build_where( $filters );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sql = "SELECT popup_id, COUNT(*) as cnt FROM {$t} {$where} GROUP BY popup_id";
		$rows = $params
			? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );
		$out  = [];
		foreach ( (array) $rows as $row ) $out[ (int) $row['popup_id'] ] = (int) $row['cnt'];
		return $out;
	}

	public function get_device_breakdown( array $filters = [] ): array {
		global $wpdb;
		$t = $this->table();
		[ $where, $params ] = $this->build_where( $filters );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$sql = "SELECT device_type, COUNT(*) as cnt FROM {$t} {$where} GROUP BY device_type";
		$rows = $params
			? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );
		$out  = [ 'desktop' => 0, 'tablet' => 0, 'mobile' => 0 ];
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row['device_type'] ] = (int) $row['cnt'];
		}
		return $out;
	}

	/**
	 * Returns A/B test breakdown for popups with variant_group_id > 0.
	 * Each variant row carries its label, weighted show rate (when
	 * available) and event counts so the admin can compare CTR/CVR.
	 *
	 * Defensive: if the variant columns are missing in the DB (install
	 * frozen on an older version, auto-migration failed, etc.) this method
	 * returns an empty array instead of throwing. The admin view treats
	 * the empty result as "no A/B tests yet" and stays usable.
	 *
	 * @return array<int, array{group_id:int,popup_id:int,popup_name:string,variant_label:string,split:int,impressions:int,clicks:int,conversions:int}>
	 */
	public function get_variant_breakdown( array $filters = [] ): array {
		global $wpdb;
		$popups_table = $wpdb->prefix . 'mlpp_popups';
		$events_table = $this->table();

		// Defensive: if either table is missing the variant columns, skip silently.
		if ( ! $this->table_has_column( $popups_table, 'variant_group_id' ) ) {
			return [];
		}
		if ( ! $this->table_has_column( $events_table, 'variant_label' ) ) {
			return [];
		}

		[ $where, $params ] = $this->build_where( $filters );
		// Restrict to A/B participating popups.
		$where_extra = $where ? ' AND popups.variant_group_id > 0' : 'WHERE popups.variant_group_id > 0';

		$sql = "SELECT popups.variant_group_id AS group_id,
				popups.id AS popup_id,
				popups.name AS popup_name,
				events.variant_label,
				popups.variant_split AS variant_split,
				SUM(CASE WHEN events.event_type = 'impression' THEN 1 ELSE 0 END) AS impressions,
				SUM(CASE WHEN events.event_type IN ('primary_click','secondary_click','image_click') THEN 1 ELSE 0 END) AS clicks,
				SUM(CASE WHEN events.event_type = 'conversion' THEN 1 ELSE 0 END) AS conversions
			FROM {$events_table} AS events
			JOIN {$popups_table} AS popups ON popups.id = events.popup_id
			{$where}{$where_extra}
			GROUP BY events.popup_id, events.variant_label
			ORDER BY popups.variant_group_id ASC, popups.id ASC, events.variant_label ASC";

		try {
			$rows = $params
				? $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A )
				: $wpdb->get_results( $sql, ARRAY_A );
		} catch ( \Throwable $e ) {
			error_log( '[ml-popup-pro] get_variant_breakdown failed: ' . $e->getMessage() );
			return [];
		}

		if ( ! is_array( $rows ) ) {
			return [];
		}

		$out = [];
		foreach ( $rows as $r ) {
			$out[] = [
				'group_id'      => (int) ( $r['group_id'] ?? 0 ),
				'popup_id'      => (int) ( $r['popup_id'] ?? 0 ),
				'popup_name'    => (string) ( $r['popup_name'] ?? '' ),
				'variant_label' => (string) ( $r['variant_label'] ?? '' ),
				'split'         => (int) ( $r['variant_split'] ?? 0 ),
				'impressions'   => (int) ( $r['impressions'] ?? 0 ),
				'clicks'        => (int) ( $r['clicks'] ?? 0 ),
				'conversions'   => (int) ( $r['conversions'] ?? 0 ),
			];
		}
		return $out;
	}

	/**
	 * True when the table exists and the column is present in its schema.
	 * Used to guard analytics queries that reference newer columns
	 * added in v1.3.0+ against installs frozen on older schema versions.
	 */
	private function table_has_column( string $table, string $column ): bool {
		global $wpdb;
		try {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = %s AND table_name = %s AND column_name = %s",
					DB_NAME,
					$table,
					$column
				)
			);
			return (int) $exists > 0;
		} catch ( \Throwable $e ) {
			// information_schema may be unavailable (e.g. some managed hosts / hyperscaler);
			// fall back to a SHOW COLUMNS probe.
			try {
				$cols = $wpdb->get_col( "SHOW COLUMNS FROM {$table}" );
				return is_array( $cols ) && in_array( $column, array_map( 'strval', $cols ), true );
			} catch ( \Throwable $e2 ) {
				return false;
			}
		}
	}

	/**
	 * Build a parameterized WHERE clause for analytics queries.
	 *
	 * Supported filters:
	 *   - period: 'all' | '7d' | '30d' | '90d'
	 *   - popup_id: int
	 *   - device: '' | 'desktop' | 'tablet' | 'mobile'
	 *   - event_in: array of event_type strings
	 *
	 * Returns [ 'WHERE ...' (or ''), array of $wpdb->prepare args ].
	 */
	private function build_where( array $filters ): array {
		global $wpdb;
		$clauses = [];
		$params  = [];

		if ( ! empty( $filters['period'] ) && 'all' !== $filters['period'] ) {
			$days = (int) $filters['period'];
			if ( in_array( $days, [ 7, 30, 90 ], true ) ) {
				$since = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
				$clauses[] = 'created_at >= %s';
				$params[]  = $since;
			}
		}

		if ( ! empty( $filters['popup_id'] ) ) {
			$clauses[] = 'popup_id = %d';
			$params[]  = (int) $filters['popup_id'];
		}

		if ( ! empty( $filters['device'] ) ) {
			$clauses[] = 'device_type = %s';
			$params[]  = (string) $filters['device'];
		}

		if ( ! empty( $filters['event_in'] ) && is_array( $filters['event_in'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $filters['event_in'] ), '%s' ) );
			$clauses[] = "event_type IN ({$placeholders})";
			foreach ( (array) $filters['event_in'] as $ev ) {
				$params[] = (string) $ev;
			}
		}

		$where = $clauses ? 'WHERE ' . implode( ' AND ', $clauses ) : '';
		return [ $where, $params ];
	}

	/**
	 * Parse filters from $_GET/$_POST and return a normalized array safe for queries.
	 *
	 * @return array{period:string,popup_id:int,device:string}
	 */
	public function parse_filters( array $source ): array {
		$period  = isset( $source['period'] ) ? sanitize_key( (string) $source['period'] ) : 'all';
		if ( ! in_array( $period, [ 'all', '7d', '30d', '90d' ], true ) ) {
			$period = 'all';
		}
		$popup_id = isset( $source['popup_id'] ) ? absint( $source['popup_id'] ) : 0;
		$device   = isset( $source['device'] ) ? sanitize_key( (string) $source['device'] ) : '';
		if ( ! in_array( $device, [ '', 'desktop', 'tablet', 'mobile' ], true ) ) {
			$device = '';
		}
		return [
			'period'   => $period,
			'popup_id' => $popup_id,
			'device'   => $device,
		];
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

		// Rate limit: 1 evento do mesmo tipo por popup por IP por janela (default 5s).
		// Impede que visitantes (ou bots) inflem a tabela de eventos em loop.
		$window = (int) apply_filters( 'mlpp_event_rate_limit_window', 5 );
		if ( $window > 0 && $this->is_rate_limited( $popup_id, $event_type, $window ) ) {
			wp_send_json_error( 'rate_limited' );
		}

		$this->record( $popup_id, $event_type, $page_url, $device, (string) ( $_POST['variant_label'] ?? '' ) );
		wp_send_json_success();
	}

	/**
	 * Returns true when the same IP+popup+event has fired within the
	 * rate-limit window. Allows real visitors through (one event per N
	 * seconds) while blocking scripted floods.
	 */
	private function is_rate_limited( int $popup_id, string $event_type, int $window ): bool {
		$ip = $this->client_ip();
		if ( '' === $ip ) {
			return false;
		}
		$key  = 'mlpp_rl_' . md5( $ip . '|' . $popup_id . '|' . $event_type );
		$seen = get_site_transient( $key );
		if ( $seen ) {
			return true;
		}
		set_site_transient( $key, 1, $window );
		return false;
	}

	/**
	 * Best-effort client IP. Trusts REMOTE_ADDR by default; theme/addon
	 * can override via the `mlpp_client_ip` filter (e.g. behind a CDN).
	 */
	private function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
		$ip = sanitize_text_field( $ip );
		/**
		 * Filters the client IP used for analytics rate limiting.
		 *
		 * @param string $ip Remote address from $_SERVER['REMOTE_ADDR'].
		 */
		$ip = (string) apply_filters( 'mlpp_client_ip', $ip );
		return $ip;
	}
}
