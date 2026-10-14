<?php
/**
 * ML Popup Pro – Premium gates (infrastructure, OFF by default).
 *
 * Provides the runtime that lets the plugin ship with a complete
 * "Free / Pro" feature split already wired, but the gates stay open
 * until the operator flips the master switch.
 *
 * Master switch:
 *   MLPP_GATES_ENFORCED — when true, every premium feature is gated.
 *   When false, the plugin behaves exactly as it did before — all
 *   features run for everyone, regardless of license status.
 *
 * Activation path (when you decide to ship paid plans):
 *   1. Set MLPP_GATES_ENFORCED to true in this file (single commit).
 *   2. Push a release.
 *   3. The Free tier now sees upgrade cards in place of the gated
 *      features; the Pro tier (with a valid serial) sees them all.
 *
 * License resolution order (matches MLPP_License::is_premium()):
 *   1. Constant `MLPP_LICENSE_KEY` defined in wp-config.php.
 *   2. Stored option `mlpp_license_status === 'valid'`.
 *   3. Otherwise: free.
 *
 * What this file does NOT touch:
 *   - The Hub endpoint at `https://license.mlopesdesign.com.br/...`
 *   - MLPP_License::verify_remote() / map_status() / activate() / deactivate()
 *   - Any code that talks to the license server.
 *
 * @package ML_Popup_Pro
 * @since   1.5.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_Gates {

	/**
	 * Master switch. Default false so existing installs keep working
	 * exactly as before until the operator explicitly enables the
	 * Free/Pro split. When true, every mlpp_capability() check enforces
	 * the gate; when false, every check returns true (open).
	 */
	const ENFORCED = false;

	/**
	 * Catalog of premium features. Each key is the canonical feature
	 * slug used by the gate helper. Adding a new key here is the
	 * single source of truth for the Pro split — no need to touch
	 * anything else when introducing a new paid feature.
	 */
	const FEATURES = [
		'ab_testing'         => 'A/B testing de popups',
		'goal_tracking'      => 'Goal tracking por CSS selector',
		'analytics_advanced' => 'Analytics avançado (filtros + device breakdown)',
		'templates_seasonal' => 'Templates sazonais (Black Friday, Natal, Exit Survey, Free Shipping)',
		'webhook'            => 'Webhook de conversão',
	];

	/**
	 * Returns true if the feature should be available to the current
	 * site. Three states:
	 *   - ENFORCED = false  -> always true (gates open)
	 *   - ENFORCED = true, Pro license -> true
	 *   - ENFORCED = true, Free license -> false
	 *
	 * Backward compatible: when the constant isn't set yet (very old
	 * code path), we treat it as false.
	 */
	public static function is_allowed( string $feature ): bool {
		if ( ! defined( 'MLPP_GATES_ENFORCED' ) ) {
			return true;
		}
		if ( ! MLPP_GATES_ENFORCED ) {
			return true;
		}
		if ( ! isset( self::FEATURES[ $feature ] ) ) {
			// Unknown feature key — fail open with a logged warning so
			// typos in the gate call don't silently block features.
			error_log( '[ml-popup-pro] MLPP_Gates::is_allowed unknown feature: ' . $feature );
			return true;
		}
		return function_exists( 'mlpp_is_premium' ) ? mlpp_is_premium() : false;
	}
}

if ( ! defined( 'MLPP_GATES_ENFORCED' ) ) {
	// Single source of truth for the master switch. Operators flip this
	// to true in a release commit when they're ready to ship the
	// Free/Pro split — no other code needs to change.
	define( 'MLPP_GATES_ENFORCED', MLPP_Gates::ENFORCED );
}

/**
 * Convenience helper. Use everywhere a feature decision is needed.
 * Returns true if the feature is currently available to the site
 * (Pro license, or gates not enforced yet).
 */
function mlpp_capability( string $feature ): bool {
	return MLPP_Gates::is_allowed( $feature );
}

/**
 * Renders the standard "recurso Pro" card. Use it wherever a gated
 * feature would otherwise output content. Accepts a feature key so
 * the card can carry a precise label and link anchor.
 */
function mlpp_render_upgrade_card( string $feature, string $description = '' ): void {
	if ( ! isset( MLPP_Gates::FEATURES[ $feature ] ) ) {
		return;
	}
	$title = MLPP_Gates::FEATURES[ $feature ];
	if ( '' === $description ) {
		$description = sprintf(
			/* translators: %s: feature name shown to the operator */
			__( '“%s” está disponível no plano Pro do ML Popup Pro.', 'ml-popup-pro' ),
			$title
		);
	}
	$activate_url = admin_url( 'admin.php?page=mlpp-settings&tab=cfg-activation' );
	$hub_url      = 'https://license.mlopesdesign.com.br/admin/';
	?>
	<article class="mlpp-card mlpp-card-locked" data-mlpp-gate="<?php echo esc_attr( $feature ); ?>" style="border:1px dashed #f59e0b;background:#fffbeb;border-radius:14px;padding:24px;margin:18px 0;">
		<div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
			<div style="font-size:32px;line-height:1" aria-hidden="true">🔒</div>
			<div style="flex:1 1 280px;min-width:240px">
				<h2 style="margin:0 0 6px;font-size:18px;color:#92400e"><?php echo esc_html( $title ); ?> <span style="font-size:11px;background:#fde68a;color:#92400e;padding:2px 8px;border-radius:999px;font-weight:700;vertical-align:middle;margin-left:6px">PRO</span></h2>
				<p style="margin:0;color:#78350f;font-size:14px;line-height:1.5"><?php echo esc_html( $description ); ?></p>
			</div>
			<div style="display:flex;gap:10px;flex-wrap:wrap">
				<a class="mlpp-btn" style="background:#b45309;color:#fff;padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600" href="<?php echo esc_url( $activate_url ); ?>">🔑 Ativar serial</a>
				<a class="mlpp-btn-secondary" style="background:#fff;color:#92400e;padding:8px 14px;border-radius:8px;text-decoration:none;font-weight:600;border:1px solid #fbbf24" href="<?php echo esc_url( $hub_url ); ?>" target="_blank" rel="noopener">Saiba mais</a>
			</div>
		</div>
	</article>
	<?php
}
