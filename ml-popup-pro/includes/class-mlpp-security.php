<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Security {

	public static function check_admin(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'ml-popup-pro' ) );
		}
	}

	public static function verify_nonce( string $action ): void {
		if ( ! isset( $_REQUEST['_wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( (string) $_REQUEST['_wpnonce'] ) ), $action ) ) {
			wp_die( esc_html__( 'Security check failed.', 'ml-popup-pro' ) );
		}
	}

	public static function sanitize_popup( array $raw ): array {
		return [
			'name'               => sanitize_text_field( $raw['name'] ?? '' ),
			'title'              => sanitize_text_field( $raw['title'] ?? '' ),
			'subtitle'           => sanitize_text_field( $raw['subtitle'] ?? '' ),
			'body'               => wp_kses_post( $raw['body'] ?? '' ),
			'image_url'          => esc_url_raw( $raw['image_url'] ?? '' ),
			'image_attachment_id'=> absint( $raw['image_attachment_id'] ?? 0 ),
			'image_alt'          => sanitize_text_field( $raw['image_alt'] ?? '' ),
			'image_link_url'     => esc_url_raw( $raw['image_link_url'] ?? '' ),
			'image_link_target'  => self::sanitize_choice( $raw['image_link_target'] ?? '_self', [ '_self','_blank' ] ),
			'image_position'     => self::sanitize_choice( $raw['image_position'] ?? 'top', [ 'top','left','right','background','only' ] ),
			'image_fit'          => self::sanitize_choice( $raw['image_fit'] ?? 'cover', [ 'cover','contain','original' ] ),
			'image_radius'       => sanitize_text_field( $raw['image_radius'] ?? '0px' ),
			'btn_primary_text'   => sanitize_text_field( $raw['btn_primary_text'] ?? '' ),
			'btn_primary_url'    => esc_url_raw( $raw['btn_primary_url'] ?? '' ),
			'btn_secondary_text' => sanitize_text_field( $raw['btn_secondary_text'] ?? '' ),
			'btn_secondary_url'  => esc_url_raw( $raw['btn_secondary_url'] ?? '' ),
			'custom_html'        => wp_kses_post( $raw['custom_html'] ?? '' ),
			'status'             => self::sanitize_choice( $raw['status'] ?? 'draft', [ 'draft','active','paused' ] ),
			'priority'           => absint( $raw['priority'] ?? 10 ),
			'popup_type'         => self::sanitize_choice( $raw['popup_type'] ?? 'center_modal', [ 'center_modal','bottom_bar','slide_in','fullscreen_overlay','floating_box' ] ),
			'design'             => wp_json_encode( self::sanitize_design( $raw['design'] ?? [] ) ),
			'triggers'           => wp_json_encode( self::sanitize_triggers( $raw['triggers'] ?? [] ) ),
			'rules'              => wp_json_encode( self::sanitize_rules( $raw['rules'] ?? [] ) ),
			'storage_cfg'        => wp_json_encode( self::sanitize_storage_cfg( $raw['storage_cfg'] ?? [] ) ),
			'goal_selectors'     => wp_json_encode( self::sanitize_goal_selectors( $raw['goal_selectors'] ?? '' ) ),
			'variant_group_id'  => absint( $raw['variant_group_id'] ?? 0 ),
			'variant_label'      => sanitize_text_field( $raw['variant_label'] ?? '' ),
			'variant_split'      => max( 0, min( 100, (int) ( $raw['variant_split'] ?? 100 ) ) ),
			'template_id'        => sanitize_key( $raw['template_id'] ?? '' ),
		];
	}

	private static function sanitize_choice( string $value, array $allowed ): string {
		return in_array( $value, $allowed, true ) ? $value : $allowed[0];
	}

	private static function sanitize_design( $raw ): array {
		if ( ! is_array( $raw ) ) return [];
		return [
			'width'           => sanitize_text_field( $raw['width'] ?? '600px' ),
			'max_width'       => sanitize_text_field( $raw['max_width'] ?? '95vw' ),
			'height'          => sanitize_text_field( $raw['height'] ?? 'auto' ),
			'max_height'      => sanitize_text_field( $raw['max_height'] ?? '90vh' ),
			'screen_position' => self::sanitize_choice( $raw['screen_position'] ?? 'bottom_right', [ 'bottom_right','bottom_left','top_right','top_left' ] ),
			'bg_color'        => sanitize_hex_color( $raw['bg_color'] ?? '#ffffff' ) ?? '#ffffff',
			'bg_opacity'      => max( 0, min( 100, (int) ( $raw['bg_opacity'] ?? 100 ) ) ),
			'text_color'      => sanitize_hex_color( $raw['text_color'] ?? '#102a43' ) ?? '#102a43',
			'btn_color'       => sanitize_hex_color( $raw['btn_color'] ?? '#155e6f' ) ?? '#155e6f',
			'btn_text_color'  => sanitize_hex_color( $raw['btn_text_color'] ?? '#ffffff' ) ?? '#ffffff',
			'btn2_color'      => sanitize_hex_color( $raw['btn2_color'] ?? '#64748b' ) ?? '#64748b',
			'btn2_text_color' => sanitize_hex_color( $raw['btn2_text_color'] ?? '#ffffff' ) ?? '#ffffff',
			'overlay_color'   => sanitize_text_field( $raw['overlay_color'] ?? 'rgba(0,0,0,0.55)' ),
			'border_radius'   => sanitize_text_field( $raw['border_radius'] ?? '16px' ),
			'shadow'          => sanitize_text_field( $raw['shadow'] ?? '0 24px 64px rgba(15,23,42,.22)' ),
			'padding'         => sanitize_text_field( $raw['padding'] ?? '36px 32px 28px' ),
			'text_align'      => self::sanitize_choice( $raw['text_align'] ?? 'left', [ 'left','center','right' ] ),
			'close_style'     => self::sanitize_choice( $raw['close_style'] ?? 'x', [ 'x','circle','text' ] ),
			'animation'       => ( $raw['animation'] ?? '1' ) === '1' ? '1' : '0',
			'animation_type'  => self::sanitize_choice( $raw['animation_type'] ?? 'fade', [ 'fade','slide_down','slide_up','zoom','none' ] ),
			'mobile_layout'   => self::sanitize_choice( $raw['mobile_layout'] ?? 'responsive', [ 'responsive','fullscreen','hidden' ] ),
		];
	}

	private static function sanitize_triggers( $raw ): array {
		if ( ! is_array( $raw ) ) return [];
		return [
			'trigger_type'   => self::sanitize_choice( $raw['trigger_type'] ?? 'immediate', [ 'immediate','delay','scroll','exit_intent','pageviews','selector','shortcode' ] ),
			'delay_seconds'  => absint( $raw['delay_seconds'] ?? 0 ),
			'scroll_percent' => absint( $raw['scroll_percent'] ?? 50 ),
			'selector'       => sanitize_text_field( $raw['selector'] ?? '' ),
			'pageviews'      => absint( $raw['pageviews'] ?? 1 ),
			'frequency'      => self::sanitize_choice( $raw['frequency'] ?? 'once_session', [ 'once_session','once_visitor','every_x_days','until_closed','always' ] ),
			'frequency_days' => absint( $raw['frequency_days'] ?? 7 ),
		];
	}

	private static function sanitize_rules( $raw ): array {
		if ( ! is_array( $raw ) ) return [];
		return [
			'scope'            => self::sanitize_choice( $raw['scope'] ?? 'entire_site', [ 'entire_site','homepage','posts_only','pages_only','specific_posts','categories','tags','include_urls','woo_products' ] ),
			'post_ids'         => array_map( 'absint', (array)( $raw['post_ids'] ?? [] ) ),
			'page_ids'         => array_map( 'absint', (array)( $raw['page_ids'] ?? [] ) ),
			'categories'       => array_map( 'absint', (array)( $raw['categories'] ?? [] ) ),
			'tags'             => array_map( 'absint', (array)( $raw['tags'] ?? [] ) ),
			'include_urls'     => sanitize_textarea_field( $raw['include_urls'] ?? '' ),
			'exclude_urls'     => sanitize_textarea_field( $raw['exclude_urls'] ?? '' ),
			'user_targeting'   => self::sanitize_choice( $raw['user_targeting'] ?? 'all', [ 'all','guests','logged_in','roles' ] ),
			'user_roles'       => array_map( 'sanitize_key', (array)( $raw['user_roles'] ?? [] ) ),
			'devices'          => array_map( 'sanitize_key', (array)( $raw['devices'] ?? [ 'desktop','tablet','mobile' ] ) ),
			'start_date'       => self::sanitize_datetime( $raw['start_date'] ?? '' ),
			'end_date'         => self::sanitize_datetime( $raw['end_date'] ?? '' ),
			'days_of_week'     => array_map( 'absint', (array)( $raw['days_of_week'] ?? [] ) ),
			'time_start'       => self::sanitize_time( $raw['time_start'] ?? '' ),
			'time_end'         => self::sanitize_time( $raw['time_end'] ?? '' ),
			'woo_products_only'=> ( $raw['woo_products_only'] ?? '0' ) === '1' ? '1' : '0',
		];
	}


	private static function sanitize_datetime( $value ): string {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' === $value ) return '';

		$value = str_replace( 'T', ' ', $value );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value ) ) return '';

		$date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $value, wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();
		if ( false === $date || ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) return '';

		return $date->format( 'Y-m-d H:i' );
	}

	private static function sanitize_time( $value ): string {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' === $value || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value ) ) return '';
		return $value;
	}

	private static function sanitize_storage_cfg( $raw ): array {
		if ( ! is_array( $raw ) ) return [];
		return [
			'storage_method'          => self::sanitize_choice( $raw['storage_method'] ?? '', [ '','cookie','localStorage','sessionStorage','none' ] ),
			'expiration_days'         => absint( $raw['expiration_days'] ?? 30 ),
			'block_on_seen'           => isset( $raw['block_on_seen'] ) ? '1' : '0',
			'block_on_closed'         => isset( $raw['block_on_closed'] ) ? '1' : '0',
			'block_on_primary_click'  => isset( $raw['block_on_primary_click'] ) ? '1' : '0',
			'block_on_secondary_click'=> isset( $raw['block_on_secondary_click'] ) ? '1' : '0',
			'block_on_converted'      => isset( $raw['block_on_converted'] ) ? '1' : '0',
			'seen_expire_days'        => absint( $raw['seen_expire_days'] ?? 30 ),
			'closed_expire_days'      => absint( $raw['closed_expire_days'] ?? 30 ),
			'click_expire_days'       => absint( $raw['click_expire_days'] ?? 30 ),
			'max_impressions'         => absint( $raw['max_impressions'] ?? 0 ),
		];
	}

	/**
	 * Goal selectors (one per line). Stored as a list of strings;
	 * invalid/empty lines are dropped. Used by the frontend to
	 * fire a `conversion` event when the visitor clicks a matching
	 * element inside the popup.
	 *
	 * Only characters valid in CSS selectors are allowed:
	 * alphanumeric, space, `,`, `.`, `#`, `:`, `-`, `_`, `[`, `]`, `>`,
	 * `+`, `~`, `(`, `)`, `*`, `=`, `'`, `"`, `/`, `@`.
	 */
	private static function sanitize_goal_selectors( $raw ): array {
		$raw_str = is_array( $raw ) ? implode( "\n", array_map( 'strval', $raw ) ) : (string) $raw;
		$lines   = preg_split( '/[\r\n]+/', $raw_str );
		$clean   = [];
		foreach ( (array) $lines as $line ) {
			$line = trim( sanitize_text_field( $line ) );
			if ( '' === $line ) continue;
			if ( strlen( $line ) > 500 ) continue;
			// Reject javascript: pseudo-schemes and any backtick/control chars.
			if ( preg_match( '/javascript:|data:|vbscript:|`/i', $line ) ) continue;
			if ( preg_match( '/[<>\\\\]/', $line ) ) continue;
			// Allow only CSS-selector-safe characters.
			if ( ! preg_match( '/^[A-Za-z0-9._\-\[\]\(\)#,:>*+~=@"\'\/ ]+$/', $line ) ) continue;
			$clean[] = $line;
		}
		return array_values( array_unique( $clean ) );
	}

	public static function sanitize_settings( array $raw ): array {
		return [
			'storage_method'           => self::sanitize_choice( $raw['storage_method'] ?? 'cookie', [ 'cookie','localStorage','sessionStorage','none' ] ),
			'default_expiration_days'  => absint( $raw['default_expiration_days'] ?? 30 ),
			'consent_mode'             => self::sanitize_choice( $raw['consent_mode'] ?? 'off', [ 'off','wait','functional' ] ),
			'allow_multiple_popups'    => ( $raw['allow_multiple_popups'] ?? '0' ) === '1' ? '1' : '0',
			'disable_analytics'        => ( $raw['disable_analytics'] ?? '0' ) === '1' ? '1' : '0',
			'delete_data_on_uninstall' => ( $raw['delete_data_on_uninstall'] ?? '0' ) === '1' ? '1' : '0',
		];
	}
}
