<?php
/**
 * ML Popup Pro – License / activation
 *
 * Manages the Free vs Pro separation. A Pro license unlocks premium
 * features (A/B testing, advanced analytics filters, seasonal templates,
 * goal tracking). Without an active license the plugin runs as Free.
 *
 * Activation sources (evaluated in order):
 *   1. Constant `MLPP_LICENSE_KEY` defined in wp-config.php (highest priority).
 *   2. Option `mlpp_license_key` + verified `mlpp_license_status = 'valid'`.
 *   3. Bundled Hub at `MLPP_PLUGIN_DIR . 'hub/'` (provided by the user).
 *
 * The plugin falls back to Free when none of the above is in effect.
 *
 * @package ML_Popup_Pro
 * @since   1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_License {

	const STATUS_FREE      = 'free';
	const STATUS_VALID     = 'valid';
	const STATUS_INVALID   = 'invalid';
	const STATUS_EXPIRED   = 'expired';
	const STATUS_PENDING   = 'pending';

	const OPTION_KEY       = 'mlpp_license_key';
	const OPTION_STATUS    = 'mlpp_license_status';
	const OPTION_DETAILS   = 'mlpp_license_details';

	/** Hub directory expected inside the plugin (user-provided). */
	const HUB_DIR = 'hub';

	/**
	 * Returns true when a Pro license is currently active.
	 *
	 * This is the single source of truth for premium gating. Use it inside
	 * admin views and feature implementations to render UI or run code
	 * only when Pro is active.
	 */
	public static function is_premium(): bool {
		// 1. Constant in wp-config.php (instant override for hosts / staging).
		if ( defined( 'MLPP_LICENSE_KEY' ) && self::STATUS_VALID === self::validate_format( (string) MLPP_LICENSE_KEY ) ) {
			return true;
		}

		// 2. Stored option validated previously.
		$status = (string) get_option( self::OPTION_STATUS, self::STATUS_FREE );
		if ( self::STATUS_VALID === $status ) {
			return true;
		}

		// 3. Bundled Hub directory signals an installed Pro tier.
		if ( self::hub_present() && self::hub_enabled() ) {
			return true;
		}

		return false;
	}

	/** Persist a license key and mark the status accordingly. */
	public static function activate( string $key ): array {
		$key = trim( $key );
		if ( '' === $key ) {
			return [ 'ok' => false, 'message' => 'Serial vazio.' ];
		}

		update_option( self::OPTION_KEY, $key, false );

		// Verification against the remote Hub happens in verify_remote().
		// Until the user-supplied hub directory is in place we treat a
		// well-formed serial as valid in dev, and ship verify_remote() as
		// a no-op that returns 'valid' once the hub is connected.
		$verification = self::verify_remote( $key );

		if ( $verification['ok'] ) {
			update_option( self::OPTION_STATUS, self::STATUS_VALID, false );
			update_option( self::OPTION_DETAILS, $verification['details'] ?? [], false );
			return [ 'ok' => true, 'message' => 'Licença Pro ativada.' ];
		}

		update_option( self::OPTION_STATUS, $verification['status'] ?? self::STATUS_INVALID, false );
		delete_option( self::OPTION_DETAILS );
		return [
			'ok'      => false,
			'message' => $verification['message'] ?? 'Serial inválido ou expirado.',
		];
	}

	public static function deactivate(): void {
		delete_option( self::OPTION_KEY );
		delete_option( self::OPTION_STATUS );
		delete_option( self::OPTION_DETAILS );
	}

	/**
	 * Remote verification against the user's hub. Until the hub directory
	 * is present this returns a sentinel that lets the user store the
	 * serial in dev without contacting anything external.
	 *
	 * @return array{ok:bool,status?:string,message?:string,details?:array}
	 */
	private static function verify_remote( string $key ): array {
		if ( self::hub_present() && function_exists( 'mlpp_hub_verify_license' ) ) {
			try {
				$res = mlpp_hub_verify_license( $key );
				if ( is_array( $res ) ) {
					return $res;
				}
			} catch ( \Throwable $e ) {
				return [ 'ok' => false, 'status' => self::STATUS_INVALID, 'message' => 'Falha na verificação: ' . $e->getMessage() ];
			}
		}
		// Hub not in place yet: accept well-formed keys locally so the
		// activation flow can be exercised; once the hub ships, this
		// code path is replaced by the real verification above.
		if ( self::validate_format( $key ) ) {
			return [
				'ok'      => true,
				'status'  => self::STATUS_VALID,
				'details' => [
					'tier'    => 'pro',
					'channel' => 'standalone',
					'note'    => 'Hub local ainda nao instalado - ativacao em modo dev.',
				],
			];
		}
		return [ 'ok' => false, 'status' => self::STATUS_INVALID, 'message' => 'Formato de serial invalido.' ];
	}

	/** Returns true when the bundled Hub directory is on disk. */
	public static function hub_present(): bool {
		return is_dir( MLPP_PLUGIN_DIR . self::HUB_DIR );
	}

	/**
	 * Returns true when the bundled hub is enabled by its own bootstrap.
	 * Falls back to false so the plugin still works Free by default.
	 */
	private static function hub_enabled(): bool {
		return function_exists( 'mlpp_hub_is_enabled' ) ? (bool) mlpp_hub_is_enabled() : false;
	}

	/** Crude sanity check on a license key format. Tweak when the hub dictates real format. */
	private static function validate_format( string $key ): bool {
		$key = trim( $key );
		if ( '' === $key ) {
			return false;
		}
		// Accept any non-empty string up to 64 chars containing alnum + dashes.
		return (bool) preg_match( '/^[A-Za-z0-9\-]{8,64}$/', $key );
	}

	/** Human-readable status for the UI. */
	public static function status_label(): string {
		$status = (string) get_option( self::OPTION_STATUS, self::STATUS_FREE );
		switch ( $status ) {
			case self::STATUS_VALID:   return 'Pro ativa';
			case self::STATUS_EXPIRED: return 'Pro expirada';
			case self::STATUS_PENDING: return 'Verificação pendente';
			case self::STATUS_INVALID: return 'Serial inválido';
			default:                    return 'Free';
		}
	}
}

/**
 * Convenience helper: true when Pro is active. Use this in views and
 * runtime gates instead of calling MLPP_License::is_premium() directly.
 *
 * @return bool
 */
function mlpp_is_premium(): bool {
	return MLPP_License::is_premium();
}