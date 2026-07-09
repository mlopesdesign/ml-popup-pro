<?php
/**
 * Minimal test bootstrap. Stubs the WP functions and globals we touch
 * in unit tests so the plugin classes load without a WP environment.
 *
 * Heavy integration tests should still run against a real WP install via
 * the WP test suite. This bootstrap is for fast pure-PHP unit tests of
 * rules, sanitization, license mapping, etc.
 *
 * @package ML_Popup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
}
if ( ! defined( 'YEAR_IN_SECONDS' ) ) {
	define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );
}
if ( ! defined( 'COOKIEPATH' ) ) {
	define( 'COOKIEPATH', '/' );
}
if ( ! defined( 'COOKIE_DOMAIN' ) ) {
	define( 'COOKIE_DOMAIN', '' );
}

if ( ! defined( 'MLPP_VERSION' ) ) {
	define( 'MLPP_VERSION', '1.4.0-test' );
}
if ( ! defined( 'MLPP_PLUGIN_DIR' ) ) {
	define( 'MLPP_PLUGIN_DIR', __DIR__ . '/../' );
}
if ( ! defined( 'MLPP_PLUGIN_URL' ) ) {
	define( 'MLPP_PLUGIN_URL', 'http://example.test/wp-content/plugins/ml-popup-pro/' );
}
if ( ! defined( 'MLPP_PLUGIN_FILE' ) ) {
	define( 'MLPP_PLUGIN_FILE', MLPP_PLUGIN_DIR . 'ml-popup-pro.php' );
}
if ( ! defined( 'MLPP_PLUGIN_BASENAME' ) ) {
	define( 'MLPP_PLUGIN_BASENAME', 'ml-popup-pro/ml-popup-pro.php' );
}

// Bootstrap the stubs.
require_once __DIR__ . '/stubs/wp-functions.php';

// Load each plugin class so test files can exercise them in isolation.
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-security.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-rules.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-storage.php';
require_once MLPP_PLUGIN_DIR . 'includes/class-mlpp-license.php';