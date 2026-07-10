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
	 * Defensive: each side's construction is wrapped so a corrupt DB read
	 * or a third-party filter throwing on a specific hook fails the
	 * single side only, never both. Worst case the admin or the frontend
	 * runs without that one feature instead of 500ing the site.
	 */
	public function __construct() {
		try {
			$admin = new MLPP_Admin();
		} catch ( \Throwable $e ) {
			error_log( '[ml-popup-pro] admin construct failed: ' . $e->getMessage() );
			$admin = null;
		}

		try {
			$frontend = new MLPP_Frontend();
		} catch ( \Throwable $e ) {
			error_log( '[ml-popup-pro] frontend construct failed: ' . $e->getMessage() );
			$frontend = null;
		}

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
