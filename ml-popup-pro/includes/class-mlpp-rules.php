<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_Rules {

	public function get_eligible_popups( array $active_popups ): array {
		$eligible = [];
		foreach ( $active_popups as $raw_popup ) {
			$popup = is_array( $raw_popup['rules'] ) ? $raw_popup : $this->decode( $raw_popup );
			if ( $this->popup_matches( $popup ) ) {
				$eligible[] = $popup;
			}
		}

		usort( $eligible, fn( $a, $b ) => (int) $b['priority'] - (int) $a['priority'] );

		// A/B testing: collapse each `variant_group_id` to one variant per visitor.
		$eligible = $this->select_variants( $eligible );

		$settings = get_option( 'mlpp_settings', [] );
		$allow_multiple = ! empty( $settings['allow_multiple_popups'] ) && $settings['allow_multiple_popups'] === '1';

		$eligible = $allow_multiple ? $eligible : ( $eligible ? [ $eligible[0] ] : [] );

		/**
		 * Filters the final list of popups eligible to be displayed on the
		 * current page request. Receives the already-filtered, sorted,
		 * single-or-multi list. Return an array of popup rows in the same
		 * shape as $popups (decoded: design/triggers/rules/storage_cfg as arrays).
		 *
		 * @param array $eligible   Popups cleared all rules.
		 * @param array $popups     All active popups before rule filtering (raw).
		 */
		return (array) apply_filters( 'mlpp_eligible_popups', $eligible, $popups );
	}

	private function decode( array $popup ): array {
		foreach ( [ 'design', 'triggers', 'rules', 'storage_cfg' ] as $f ) {
			if ( isset( $popup[ $f ] ) && is_string( $popup[ $f ] ) ) {
				$decoded = json_decode( $popup[ $f ], true );
				$popup[ $f ] = is_array( $decoded ) ? $decoded : [];
			}
		}
		return $popup;
	}

	/**
	 * A/B variant selection: when multiple active popups share a
	 * `variant_group_id`, only one is shown per request, chosen by
	 * the relative `variant_split` weights. Deterministic per
	 * `(visitor cookie, group_id)` so the same visitor keeps seeing
	 * the same variant across page loads.
	 *
	 * @return array<int, array>  Selected popups (always at most one per group).
	 */
	private function select_variants( array $eligible ): array {
		$groups = [];
		foreach ( $eligible as $p ) {
			$gid = (int) ( $p['variant_group_id'] ?? 0 );
			if ( $gid <= 0 ) {
				$groups['_solo'][] = $p;
				continue;
			}
			if ( ! isset( $groups[ $gid ] ) ) {
				$groups[ $gid ] = [];
			}
			$groups[ $gid ][] = $p;
		}

		$selected = [];
		foreach ( $groups as $gid => $variants ) {
			if ( '_solo' === $gid ) {
				foreach ( $variants as $v ) {
					$selected[] = $v;
				}
				continue;
			}
			if ( count( $variants ) === 1 ) {
				$selected[] = $variants[0];
				continue;
			}
			// Weighted pick per visitor. Split must sum > 0.
			$sum = 0.0;
			foreach ( $variants as $v ) {
				$sum += max( 1, (int) ( $v['variant_split'] ?? 100 ) );
			}
			$bucket = (int) ( ( $this->visitor_hash( (int) $gid ) % 1000000 ) / 1000000 * $sum );
			$acc = 0.0;
			$picked = $variants[0];
			foreach ( $variants as $v ) {
				$acc += max( 1, (int) ( $v['variant_split'] ?? 100 ) );
				if ( $bucket <= $acc ) {
					$picked = $v;
					break;
				}
			}
			$selected[] = $picked;
		}

		return $selected;
	}

	private function visitor_hash( int $gid ): int {
		$cookie = isset( $_COOKIE[ 'mlpp_visitor_' . $gid ] ) ? (string) $_COOKIE[ 'mlpp_visitor_' . $gid ] : '';
		if ( '' === $cookie ) {
			$cookie = wp_generate_password( 12, false );
			if ( ! headers_sent() ) {
				setcookie( 'mlpp_visitor_' . $gid, $cookie, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
			}
		}
		return abs( crc32( $cookie . '|' . $gid ) );
	}

	private function popup_matches( array $popup ): bool {
		$rules = is_array( $popup['rules'] ) ? $popup['rules'] : [];

		// Scheduling.
		if ( ! $this->check_schedule( $rules ) ) {
			return false;
		}

		// Device targeting.
		if ( ! $this->check_device( $rules ) ) {
			return false;
		}

		// User targeting.
		if ( ! $this->check_user( $rules ) ) {
			return false;
		}

		// Scope / page targeting.
		if ( ! $this->check_scope( $rules ) ) {
			return false;
		}

		return true;
	}

	private function check_schedule( array $rules ): bool {
		$timezone = wp_timezone();
		$now      = new DateTimeImmutable( 'now', $timezone );

		if ( ! empty( $rules['start_date'] ) ) {
			$start = $this->parse_schedule_datetime( (string) $rules['start_date'], $timezone );
			if ( $start && $now < $start ) {
				return false;
			}
		}

		if ( ! empty( $rules['end_date'] ) ) {
			$end = $this->parse_schedule_datetime( (string) $rules['end_date'], $timezone );
			if ( $end && $now > $end ) {
				return false;
			}
		}

		if ( ! empty( $rules['days_of_week'] ) && is_array( $rules['days_of_week'] ) ) {
			$today = (int) $now->format( 'w' ); // 0=Domingo.
			if ( ! in_array( $today, array_map( 'intval', $rules['days_of_week'] ), true ) ) {
				return false;
			}
		}

		$time_now   = $now->format( 'H:i' );
		$time_start = (string) ( $rules['time_start'] ?? '' );
		$time_end   = (string) ( $rules['time_end'] ?? '' );

		if ( '' !== $time_start && '' !== $time_end ) {
			if ( $time_start <= $time_end ) {
				if ( $time_now < $time_start || $time_now > $time_end ) return false;
			} elseif ( $time_now < $time_start && $time_now > $time_end ) {
				// Janela que atravessa a meia-noite, por exemplo 22:00–02:00.
				return false;
			}
		} elseif ( '' !== $time_start && $time_now < $time_start ) {
			return false;
		} elseif ( '' !== $time_end && $time_now > $time_end ) {
			return false;
		}

		return true;
	}

	private function parse_schedule_datetime( string $value, DateTimeZone $timezone ): ?DateTimeImmutable {
		$value = str_replace( 'T', ' ', trim( $value ) );
		if ( '' === $value ) return null;

		foreach ( [ '!Y-m-d H:i', '!Y-m-d' ] as $format ) {
			$date = DateTimeImmutable::createFromFormat( $format, $value, $timezone );
			$errors = DateTimeImmutable::getLastErrors();
			if ( false !== $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
				return $date;
			}
		}

		return null;
	}

	private function check_device( array $rules ): bool {
		if ( empty( $rules['devices'] ) || ! is_array( $rules['devices'] ) ) {
			return true;
		}
		$ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
		$mobile = wp_is_mobile();
		$tablet = preg_match( '/tablet|ipad/i', $ua ) === 1;
		$desktop = ! $mobile && ! $tablet;

		foreach ( $rules['devices'] as $d ) {
			if ( $d === 'mobile' && $mobile && ! $tablet ) {
				return true;
			}
			if ( $d === 'tablet' && $tablet ) {
				return true;
			}
			if ( $d === 'desktop' && $desktop ) {
				return true;
			}
		}
		return false;
	}

	private function check_user( array $rules ): bool {
		$targeting = $rules['user_targeting'] ?? 'all';
		if ( $targeting === 'guests' && is_user_logged_in() ) {
			return false;
		}
		if ( $targeting === 'logged_in' && ! is_user_logged_in() ) {
			return false;
		}
		if ( $targeting === 'roles' && ! empty( $rules['user_roles'] ) && is_array( $rules['user_roles'] ) ) {
			$user = wp_get_current_user();
			$match = false;
			foreach ( $rules['user_roles'] as $role ) {
				if ( in_array( $role, (array) $user->roles, true ) ) {
					$match = true;
					break;
				}
			}
			if ( ! $match ) {
				return false;
			}
		}
		return true;
	}

	private function check_scope( array $rules ): bool {
		$scope = $rules['scope'] ?? 'entire_site';

		if ( $scope === 'entire_site' ) {
			return $this->check_url_exclusions( $rules );
		}

		if ( $scope === 'homepage' ) {
			return is_front_page() || is_home();
		}

		if ( $scope === 'posts_only' ) {
			return is_single() && $this->check_url_exclusions( $rules );
		}

		if ( $scope === 'pages_only' ) {
			return is_page() && $this->check_url_exclusions( $rules );
		}

		if ( $scope === 'specific_posts' && ! empty( $rules['post_ids'] ) ) {
			return is_single( $rules['post_ids'] ) || is_page( $rules['post_ids'] );
		}

		if ( $scope === 'categories' && ! empty( $rules['categories'] ) ) {
			return is_category( $rules['categories'] ) || has_category( $rules['categories'] );
		}

		if ( $scope === 'tags' && ! empty( $rules['tags'] ) ) {
			return is_tag( $rules['tags'] ) || has_tag( $rules['tags'] );
		}

		if ( $scope === 'include_urls' && ! empty( $rules['include_urls'] ) ) {
			return $this->current_url_matches( $rules['include_urls'] );
		}

		if ( $scope === 'woo_products' && function_exists( 'is_product' ) ) {
			return is_product();
		}

		return true;
	}

	private function check_url_exclusions( array $rules ): bool {
		if ( empty( $rules['exclude_urls'] ) ) {
			return true;
		}
		return ! $this->current_url_matches( $rules['exclude_urls'] );
	}

	private function current_url_matches( string $patterns ): bool {
		$current = (string) home_url( add_query_arg( [] ) );
		foreach ( array_filter( array_map( 'trim', explode( "\n", $patterns ) ) ) as $pattern ) {
			if ( fnmatch( $pattern, $current ) || strpos( $current, $pattern ) !== false ) {
				return true;
			}
		}
		return false;
	}
}
