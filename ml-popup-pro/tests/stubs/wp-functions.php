<?php
/**
 * Minimal WordPress function stubs used by the plugin during unit tests.
 * Only the WP functions called by Security/Rules/Storage/License during
 * the tests are implemented here.
 *
 * @package ML_Popup_Pro
 */

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( preg_replace( '/[\r\n\t]+/', ' ', (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( (string) $str );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $str ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_hex_color' ) ) {
	function sanitize_hex_color( $color ) {
		$color = ltrim( (string) $color, '#' );
		return (bool) preg_match( '/^[0-9a-fA-F]{6}$/', $color ) ? '#' . $color : null;
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $key, $default = false ) {
		return $GLOBALS['__mlpp_options'][$key] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $key, $value, $autoload = null ) {
		$GLOBALS['__mlpp_options'][$key] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $key ) {
		unset( $GLOBALS['__mlpp_options'][$key] );
		return true;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $key, $value, $expiration = 0 ) {
		$GLOBALS['__mlpp_transients'][ $key ] = $value;
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $key ) {
		return $GLOBALS['__mlpp_transients'][ $key ] ?? false;
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $key ) {
		unset( $GLOBALS['__mlpp_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'delete_site_transient' ) ) {
	function delete_site_transient( $key ) { return true; }
}
if ( ! function_exists( 'delete_site_option' ) ) {
	function delete_site_option( $key )   { return true; }
}
if ( ! function_exists( 'set_site_transient' ) ) {
	function set_site_transient( $key, $value, $ttl ) { return true; }
}
if ( ! function_exists( 'get_site_transient' ) ) {
	function get_site_transient( $key ) { return false; }
}
if ( ! function_exists( 'get_site_option' ) ) {
	function get_site_option( $key, $default = false ) { return $default; }
}
if ( ! function_exists( 'update_site_option' ) ) {
	function update_site_option( $key, $value ) { return true; }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $flags = 0, $depth = 512 ) {
		return json_encode( $data, $flags, $depth );
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null ) {
		echo json_encode( [ 'success' => true, 'data' => $data ] );
		throw new \RuntimeException( 'wp_send_json_success' );
	}
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null ) {
		echo json_encode( [ 'success' => false, 'data' => $data ] );
		throw new \RuntimeException( 'wp_send_json_error' );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof \WP_Error;
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public array $errors = [];
		public array $error_data = [];
		public function __construct( $code = '', $message = '', $data = null ) {
			if ( $code )    { $this->errors[$code][] = $message; }
			if ( $data )    { $this->error_data[$code] = $data; }
		}
		public function get_error_message( $code = '' ) {
			return '';
		}
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $msg = '' ) {
		throw new \RuntimeException( 'wp_die: ' . $msg );
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone() {
		return new \DateTimeZone( date_default_timezone_get() ?: 'UTC' );
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) { return true; }
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) { return true; }
}

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( $action ) { return 'test-nonce'; }
}
if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( $action = -1, $query_arg = false, $die = true ) {
		// Always succeed in tests. Real WP would compare the nonce field
		// against the stored value and wp_die() on mismatch.
		return true;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) { return (string) $data; }
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	function wp_generate_password( $length = 12, $special_chars = true ) {
		return bin2hex( random_bytes( max( 4, (int) ( $length / 2 ) ) ) );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '/' ) {
		return 'http://example.test' . $path;
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '/', $scheme = 'admin' ) {
		return 'http://example.test/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() { return false; }
}

if ( ! function_exists( 'is_front_page' ) ) {
	function is_front_page() { return false; }
}
if ( ! function_exists( 'is_home' ) ) {
	function is_home() { return false; }
}
if ( ! function_exists( 'is_single' ) ) {
	function is_single() { return false; }
}
if ( ! function_exists( 'is_page' ) ) {
	function is_page() { return false; }
}
if ( ! function_exists( 'is_category' ) ) {
	function is_category() { return false; }
}
if ( ! function_exists( 'has_category' ) ) {
	function has_category() { return false; }
}
if ( ! function_exists( 'is_tag' ) ) {
	function is_tag() { return false; }
}
if ( ! function_exists( 'has_tag' ) ) {
	function has_tag() { return false; }
}
if ( ! function_exists( 'wp_is_mobile' ) ) {
	function wp_is_mobile() { return false; }
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type = 'mysql' ) {
		return gmdate( 'Y-m-d H:i:s' );
	}
}

// Filter / action stubs. apply_filters returns the 2nd argument
// unless a registered callback registered for that hook transforms it
// via the in-memory store.
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		$cb = $GLOBALS['__mlpp_filter_callbacks'][ $hook ] ?? [];
		foreach ( $cb as $fn ) {
			if ( is_callable( $fn ) ) {
				$value = $fn( $value, ...$args );
			}
		}
		return $value;
	}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['__mlpp_filter_callbacks'][ $hook ][] = $callback;
		return true;
	}
}
if ( ! function_exists( 'remove_filter' ) ) {
	function remove_filter( $hook, $callback = '', $priority = 10 ) {
		if ( '' === $callback || empty( $GLOBALS['__mlpp_filter_callbacks'][ $hook ] ) ) {
			unset( $GLOBALS['__mlpp_filter_callbacks'][ $hook ] );
			return true;
		}
		$list = &$GLOBALS['__mlpp_filter_callbacks'][ $hook ];
		foreach ( $list as $i => $fn ) {
			if ( $fn === $callback ) { unset( $list[ $i ] ); }
		}
		$GLOBALS['__mlpp_filter_callbacks'][ $hook ] = array_values( $list );
		return true;
	}
}