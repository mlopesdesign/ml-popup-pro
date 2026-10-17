<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// The "delete data on uninstall" flag is stored inside the mlpp_settings option array.
$settings = get_option( 'mlpp_settings', array() );
$delete   = is_array( $settings ) ? ( $settings['delete_data_on_uninstall'] ?? '0' ) : '0';

// Cache do updater e descartavel: limpa sempre.
delete_site_transient( 'mlpp_github_update_cache' );
delete_site_option( 'mlpp_github_update_last_error' );

// 1.6.0: licença pelo site tools — a licença local fica (reinstalar recupera); só a
// renovação diária é desagendada.
wp_clear_scheduled_hook( 'mllc_ml_popup_pro_refresh' );

if ( '1' !== $delete ) {
	return;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mlpp_popups" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mlpp_events" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}mlpp_meta" );

delete_option( 'mlpp_settings' );
delete_option( 'mlpp_db_version' );

// Clean updater cache/state.
delete_site_transient( 'mlpp_github_update_cache' );
delete_site_option( 'mlpp_github_update_last_error' );
