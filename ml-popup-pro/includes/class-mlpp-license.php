<?php
/**
 * ML Popup Pro – License / activation
 *
 * Talks to the user's central License Hub over HTTP and exposes a
 * `mlpp_is_premium()` helper used everywhere to gate Pro features.
 *
 * Endpoint contract (POST):
 *   url:    MLPP_LICENSE_SERVER (default: https://license.mlopesdesign.com.br/api/license.php)
 *   body:   action=validate_license
 *           product_id = MLPP_PRODUCT_ID (slug of this plugin)
 *           license_key = serial entered in Configurações > Ativação
 *           domain = host of the current site (HTTP_HOST, lowercased)
 *           site_url = home_url()
 *           version = current plugin version
 *
 * Response (JSON):
 *   { valid: bool, status: string, message: string,
 *     plan?, domain?, expires_at?, grace_until? }
 *
 * Statuses observed in the hub:
 *   active, expired, suspended, cancelled, deleted, not_found,
 *   bad_request, invalid_action, unknown_product, domain_mismatch,
 *   not_installed.
 *
 * The plugin falls back to Free when:
 *   - no constant MLPP_LICENSE_KEY is set,
 *   - no stored option key, or
 *   - the stored key fails validation against the hub (or the hub is unreachable).
 *
 * @package ML_Popup_Pro
 * @since   1.1.0
 * @updated 1.2.0  Hub integration via HTTP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_License {

	const STATUS_FREE      = 'free';
	const STATUS_VALID     = 'valid';
	const STATUS_PENDING   = 'pending';
	const STATUS_INVALID   = 'invalid';
	const STATUS_EXPIRED   = 'expired';
	const STATUS_REVOKED   = 'revoked';
	const STATUS_UNREACH   = 'unreachable';

	const OPTION_KEY       = 'mlpp_license_key';
	const OPTION_STATUS    = 'mlpp_license_status';
	const OPTION_DETAILS   = 'mlpp_license_details';
	const OPTION_CACHE     = 'mlpp_license_cache';
	const OPTION_PRODUCT   = 'mlpp_product_id';

	const DEFAULT_SERVER   = 'https://license.mlopesdesign.com.br/api/license.php';
	const DEFAULT_PRODUCT  = 'ml-popup-pro';
	const CACHE_TTL        = 12 * HOUR_IN_SECONDS;
	const API_TIMEOUT      = 15;

	/**
	 * Single source of truth for premium gating. Used in admin views and
	 * runtime feature checks. Resolves in this order:
	 *   1. Constant `MLPP_LICENSE_KEY` defined in wp-config.php.
	 *   2. Stored option `mlpp_license_status === 'valid'`.
	 *
	 * @return bool
	 */
	public static function is_premium(): bool {
		if ( defined( 'MLPP_LICENSE_KEY' ) && '' !== (string) MLPP_LICENSE_KEY ) {
			return true;
		}
		$status = (string) get_option( self::OPTION_STATUS, self::STATUS_FREE );
		return self::STATUS_VALID === $status;
	}

	/**
	 * Persist a serial and validate it against the hub. If the hub is
	 * unreachable the serial is stored as 'pending' so the user can
	 * retry; if it returns a structured error, the option reflects the
	 * exact status (expired, invalid, etc.).
	 *
	 * @return array{ok:bool,status:string,message:string}
	 */
	public static function activate( string $key, bool $force = false ): array {
		$key = trim( $key );
		if ( '' === $key ) {
			return [
				'ok'      => false,
				'status'  => self::STATUS_INVALID,
				'message' => 'Serial vazio.',
			];
		}

		update_option( self::OPTION_KEY, $key, false );

		$verification = self::verify_remote( $key, $force );
		$status       = $verification['status'];
		update_option( self::OPTION_STATUS, $status, false );

		if ( self::STATUS_VALID === $status ) {
			update_option( self::OPTION_DETAILS, $verification['details'] ?? [], false );
			update_option( self::OPTION_CACHE, $verification, false );
			return [
				'ok'      => true,
				'status'  => $status,
				'message' => $verification['message'] ?? 'Pro ativa.',
			];
		}

		// Keep the cache so the admin can inspect the precise failure mode.
		update_option( self::OPTION_CACHE, $verification, false );

		// If invalid/revoked/expired, scrub details so the previous license
		// doesn't leak across reinstalls.
		if ( in_array( $status, [ self::STATUS_INVALID, self::STATUS_REVOKED, self::STATUS_EXPIRED ], true ) ) {
			delete_option( self::OPTION_DETAILS );
		}

		return [
			'ok'      => false,
			'status'  => $status,
			'message' => $verification['message'] ?? 'Serial rejeitado pela hub.',
		];
	}

	public static function deactivate(): void {
		delete_option( self::OPTION_KEY );
		delete_option( self::OPTION_STATUS );
		delete_option( self::OPTION_DETAILS );
		delete_option( self::OPTION_CACHE );
	}

	/**
	 * Validate a key against the hub. Honours the cache (CACHE_TTL) when
	 * not forced; serial changes bypass the cache automatically.
	 *
	 * @return array{ok:bool,status:string,message:string,details?:array,raw?:array}
	 */
	public static function verify_remote( string $key, bool $force = false ): array {
		$key = trim( $key );
		if ( '' === $key ) {
			return [ 'ok' => false, 'status' => self::STATUS_INVALID, 'message' => 'Serial vazio.' ];
		}

		$cached = get_option( self::OPTION_CACHE, null );
		$stored_key = (string) get_option( self::OPTION_KEY, '' );
		if ( ! $force && is_array( $cached ) && isset( $cached['checked_key'], $cached['expires_at'] ) ) {
			if ( (string) $cached['checked_key'] === $key && (int) $cached['expires_at'] > time() ) {
				return $cached;
			}
		}

		$server   = self::server_url();
		$product  = self::product_id();
		$domain   = self::current_domain();
		$site_url = self::current_site_url();

		$response = wp_remote_post(
			$server,
			[
				'timeout'   => self::API_TIMEOUT,
				'sslverify' => true,
				'headers'   => [
					'Accept'     => 'application/json',
					'User-Agent' => 'ML-Popup-Pro-Updater/' . MLPP_VERSION . ' (' . $domain . ')',
				],
				'body'      => [
					'action'      => 'validate_license',
					'product_id'  => $product,
					'license_key' => $key,
					'domain'      => $domain,
					'site_url'    => $site_url,
					'version'     => MLPP_VERSION,
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'         => false,
				'status'     => self::STATUS_UNREACH,
				'message'    => 'Não foi possível contactar o servidor de licença: ' . $response->get_error_message(),
				'checked_at' => time(),
				'expires_at' => time() + 600, // back-off 10min before retrying
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || ! is_array( $body ) ) {
			return [
				'ok'         => false,
				'status'     => self::STATUS_UNREACH,
				'message'    => 'Servidor de licença respondeu HTTP ' . $code . '.',
				'checked_at' => time(),
				'expires_at' => time() + 600,
			];
		}

		$status = self::map_status( (string) ( $body['status'] ?? '' ) );
		$valid  = self::STATUS_VALID === $status;

		$out = [
			'ok'         => $valid,
			'status'     => $status,
			'message'    => (string) ( $body['message'] ?? '' ),
			'details'    => [
				'plan'       => (string) ( $body['plan']       ?? 'pro' ),
				'domain'     => (string) ( $body['domain']     ?? $domain ),
				'expires_at' => (string) ( $body['expires_at'] ?? '' ),
				'channel'    => 'hub',
			],
			'raw'        => $body,
			'checked_key' => $key,
			'checked_at' => time(),
			'expires_at' => time() + self::CACHE_TTL,
		];

		if ( ! $valid ) {
			// Use a shorter retry window for known permanent failures.
			$out['expires_at'] = time() + 900;
		}

		return $out;
	}

	/** Maps the hub's free-form status field to a stable internal value. */
	private static function map_status( string $hub_status ): string {
		$hub_status = strtolower( trim( $hub_status ) );
		switch ( $hub_status ) {
			case 'active':
				return self::STATUS_VALID;
			case 'expired':
				return self::STATUS_EXPIRED;
			case 'suspended':
			case 'cancelled':
			case 'revoked':
			case 'deleted':
				return self::STATUS_REVOKED;
			case 'not_found':
			case 'unknown_product':
			case 'bad_request':
			case 'invalid_action':
			case 'domain_mismatch':
				return self::STATUS_INVALID;
			default:
				return self::STATUS_INVALID;
		}
	}

	/** Returns the hub endpoint URL, overridable via `mlpp_license_server` filter. */
	public static function server_url(): string {
		$constant = defined( 'MLPP_LICENSE_SERVER' ) ? (string) MLPP_LICENSE_SERVER : '';
		$url      = $constant !== '' ? $constant : self::DEFAULT_SERVER;
		/**
		 * Filters the license hub endpoint URL.
		 *
		 * @param string $url  Hub endpoint, default https://license.mlopesdesign.com.br/api/license.php
		 */
		return (string) apply_filters( 'mlpp_license_server', $url );
	}

	/** Returns the product slug registered with the hub for this plugin. */
	public static function product_id(): string {
		$opt     = (string) get_option( self::OPTION_PRODUCT, '' );
		$product = $opt !== '' ? $opt : self::DEFAULT_PRODUCT;
		/**
		 * Filters the product_id sent to the hub (must match the slug
		 * registered in the hub admin under "Produtos").
		 *
		 * @param string $product  Default `ml-popup-pro`.
		 */
		return (string) apply_filters( 'mlpp_license_product_id', $product );
	}

	private static function current_domain(): string {
		$host = isset( $_SERVER['HTTP_HOST'] ) ? (string) $_SERVER['HTTP_HOST'] : (string) home_url();
		return strtolower( trim( preg_replace( '#^https?://#', '', $host ) ) );
	}

	private static function current_site_url(): string {
		return (string) home_url( '/' );
	}

	/** Human-readable status for the UI. */
	public static function status_label(): string {
		if ( self::is_premium() ) {
			return 'Pro ativa';
		}
		$status = (string) get_option( self::OPTION_STATUS, self::STATUS_FREE );
		switch ( $status ) {
			case self::STATUS_VALID:    return 'Pro ativa';
			case self::STATUS_EXPIRED:  return 'Pro expirada';
			case self::STATUS_REVOKED:  return 'Pro revogada';
			case self::STATUS_PENDING:  return 'Verificação pendente';
			case self::STATUS_UNREACH:  return 'Hub indisponível';
			case self::STATUS_INVALID:  return 'Serial inválido';
			default:                    return 'Free';
		}
	}

	/** Returns the last verification result (from cache) for the diagnostic card. */
	public static function last_verification(): array {
		$cached = get_option( self::OPTION_CACHE, [] );
		return is_array( $cached ) ? $cached : [];
	}
}

/**
 * Convenience helper: true when Pro is active. Use this in views and
 * runtime gates instead of calling MLPP_License::is_premium() directly.
 */
function mlpp_is_premium(): bool {
	return MLPP_License::is_premium();
}