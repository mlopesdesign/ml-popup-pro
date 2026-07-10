<?php
/**
 * Plugin Name: ML Popup Pro
 * Plugin URI: https://mlopesdesign.com.br
 * Update URI: https://github.com/mlopesdesign/ml-popup-pro
 * Description: Gerenciador premium de popups para WordPress. Campanhas, regras de exibição, agendamento, templates, analytics e shortcodes com identidade visual ML.
 * Version: 1.5.2
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

define( 'MLPP_VERSION', '1.5.2' );
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

// Activation hook wrapped in try/catch so a failed schema migration never
// produces the WordPress "critical error" recovery screen. If anything
// throws inside activate(), we log it, drop a transient for the admin
// notice, and still leave the plugin in a usable (Free) state.
register_activation_hook( __FILE__, function () {
	try {
		MLPP_Activator::activate();
	} catch ( \Throwable $e ) {
		error_log( '[ml-popup-pro] activation failed: ' . $e->getMessage() );
		set_transient( 'mlpp_activation_error', $e->getMessage(), DAY_IN_SECONDS );
	}
} );
register_deactivation_hook( __FILE__, function () {
	try {
		MLPP_Activator::deactivate();
	} catch ( \Throwable $e ) {
		error_log( '[ml-popup-pro] deactivation failed: ' . $e->getMessage() );
	}
} );

// Boot updater immediately while the plugin file is loaded, before WordPress builds update transients.
try {
	$GLOBALS['mlpp_updater'] = new MLPP_Updater();
	$GLOBALS['mlpp_updater']->init();
} catch ( \Throwable $e ) {
	error_log( '[ml-popup-pro] updater init failed: ' . $e->getMessage() );
}

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
 * Surface deferred activation / boot errors to the admin.
 * Reads transients set by the wrapped activation hook and the boot
 * sequence. Auto-dismisses once the user clicks "Reparar banco".
 */
add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$msg = get_transient( 'mlpp_activation_error' );
	if ( ! is_string( $msg ) || '' === $msg ) {
		return;
	}
	printf(
		'<div class="notice notice-warning is-dismissible"><p><strong>ML Popup Pro:</strong> %s</p><p>Abra <em>Configurações → Atualizações</em> e clique em <strong>🛠 Reparar banco de dados</strong> para completar a migração. Verifique também <code>wp-content/debug.log</code> para detalhes.</p></div>',
		esc_html( $msg )
	);
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
