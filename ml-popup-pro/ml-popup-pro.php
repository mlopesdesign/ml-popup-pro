<?php
/**
 * Plugin Name: ML Popup Pro
 * Plugin URI: https://mlopesdesign.com.br
 * Update URI: https://github.com/mlopesdesign/ml-popup-pro
 * Description: Gerenciador premium de popups para WordPress. Campanhas, regras de exibição, agendamento, templates, analytics e shortcodes com identidade visual ML.
 * Version: 1.5.1
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Author: ML Lopes Design
 * Author URI: https://mlopesdesign.com.br
 * License: GPL2+
 * Text Domain: ml-popup-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MLPP_VERSION', '1.5.1' );
define( 'MLPP_PLUGIN_FILE', __FILE__ );
define( 'MLPP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MLPP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-activator.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-security.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-license.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-storage.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-analytics.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-rules.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-templates.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-updater.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-admin.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-frontend.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-plugin.php';

register_activation_hook( __FILE__, [ 'MLPP_Activator', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'MLPP_Activator', 'deactivate' ] );

// Boot updater immediately while the plugin file is loaded, before WordPress builds update transients.
$GLOBALS['mlpp_updater'] = new MLPP_Updater();
$GLOBALS['mlpp_updater']->init();

add_action( 'plugins_loaded', function () {
	static $instance = null;
	if ( $instance === null ) {
		// Defensive boot: schema migration and Plugin construction are
		// wrapped so a failed auto-migration on a stale install never
		// 500s the whole site. Worst case the plugin runs Free with the
		// last-known-good schema and surfaces an admin notice to retry
		// the repair from Configurações > Atualizações.
		try {
			MLPP_Activator::maybe_upgrade();
		} catch ( \Throwable $e ) {
			error_log( '[ml-popup-pro] maybe_upgrade failed: ' . $e->getMessage() );
		}
		try {
			$instance = new MLPP_Plugin();
		} catch ( \Throwable $e ) {
			error_log( '[ml-popup-pro] boot failed: ' . $e->getMessage() );
			$instance = null;
		}
	}
} );

/**
 * Load the plugin text domain for translations.
 * Looks for `wp-content/languages/plugins/ml-popup-pro-<locale>.mo` first,
 * then falls back to the bundled `languages/` directory inside the plugin.
 */
add_action( 'init', function () {
	load_plugin_textdomain(
		'ml-popup-pro',
		false,
		dirname( MLPP_PLUGIN_BASENAME ) . '/languages'
	);
} );
