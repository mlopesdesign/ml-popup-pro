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
	 */
	public function __construct() {
		$admin    = new MLPP_Admin();
		$frontend = new MLPP_Frontend();

		if ( is_admin() ) {
			$admin->init();
		} else {
			$frontend->init();
		}
	}
}
