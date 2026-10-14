<?php
/**
 * Plugin bootstrap.
 *
 * Lazy-loads admin vs frontend context after WordPress reaches
 * the `plugins_loaded` hook. Self-heal upgrades run here so updating
 * over an existing install never leaves a broken schema.
 *
 * @package ML_Popup_Pro
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_Plugin {

	/**
	 * Construct both admin and frontend; only one actually runs `init()`
	 * per request, depending on the WP context.
	 *
	 * Defensive: construction is wrapped per side so a failed backend call
	 * (corrupt DB row, unexpected option shape, third-party filter throwing)
	 * takes down the single affected side only, not the whole site.
	 */
	public function __construct() {
		$admin    = null;
		$frontend = null;
		try { $admin    = new MLPP_Admin();    } catch ( \Throwable $e ) { error_log( '[ml-popup-pro] admin construct failed: ' . $e->getMessage() ); }
		try { $frontend = new MLPP_Frontend(); } catch ( \Throwable $e ) { error_log( '[ml-popup-pro] frontend construct failed: ' . $e->getMessage() ); }

		if ( is_admin() ) {
			if ( $admin ) {
				try { $admin->init(); } catch ( \Throwable $e ) { error_log( '[ml-popup-pro] admin init failed: ' . $e->getMessage() ); }
			}
		} else {
			if ( $frontend ) {
				try { $frontend->init(); } catch ( \Throwable $e ) { error_log( '[ml-popup-pro] frontend init failed: ' . $e->getMessage() ); }
			}
		}
	}
}
