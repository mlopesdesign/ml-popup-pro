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
			if ( '' === $code ) {
				$code = $this->get_error_code();
			}
			return (string) ( $this->errors[ $code ][0] ?? '' );
		}
		public function get_error_code() {
			$codes = array_keys( $this->errors );
			return $codes[0] ?? '';
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

// ---- 1.6.0: stubs usados pelo cliente do ML License Hub e pela trava do painel ----

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $value ) {
		return rtrim( (string) $value, '/\\' );
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		$GLOBALS['__mlpp_actions'][ $hook ][] = $callback;
		return true;
	}
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook ) {
		return $GLOBALS['__mlpp_cron'][ $hook ] ?? false;
	}
}
if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook ) {
		$GLOBALS['__mlpp_cron'][ $hook ] = $timestamp;
		return true;
	}
}
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, $component );
	}
}
if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return ! empty( $GLOBALS['__mlpp_doing_ajax'] );
	}
}
if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null ) {
		return gmdate( $format, $timestamp ?? time() );
	}
}
if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
		return 1;
	}
}
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( $args, $url = '' ) {
		$sep = false === strpos( (string) $url, '?' ) ? '?' : '&';
		return $url . $sep . http_build_query( (array) $args );
	}
}
if ( ! class_exists( 'MLPP_Test_Redirect' ) ) {
	/** Lançada pelo stub de wp_safe_redirect para o teste conferir o destino sem sair do processo. */
	class MLPP_Test_Redirect extends \RuntimeException {
		public string $url;
		public function __construct( string $url ) {
			parent::__construct( 'redirect: ' . $url );
			$this->url = $url;
		}
	}
}
if ( ! function_exists( 'wp_safe_redirect' ) ) {
	function wp_safe_redirect( $location, $status = 302 ) {
		throw new MLPP_Test_Redirect( (string) $location );
	}
}
if ( ! function_exists( 'wp_remote_post' ) ) {
	/** Sem rede nos testes: registra a chamada e responde "offline". */
	function wp_remote_post( $url, $args = [] ) {
		$GLOBALS['__mlpp_http'][] = [ 'url' => (string) $url, 'args' => $args ];
		return new WP_Error( 'http_request_failed', 'offline (testes)' );
	}
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
	}
}
if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return is_array( $response ) ? (int) ( $response['response']['code'] ?? 0 ) : 0;
	}
}
