<?php
/**
 * Minimal stub of wp-admin/includes/upgrade.php so unit tests can call
 * dbDelta() without standing up a full WP install.
 *
 * Tests that want to simulate a migration failure can override these
 * globals (dbDelta_throw / dbDelta_log) after the bootstrap loads.
 */

if ( ! isset( $GLOBALS['dbDelta_log'] ) ) {
	$GLOBALS['dbDelta_log'] = [];
}
if ( ! isset( $GLOBALS['dbDelta_throw'] ) ) {
	$GLOBALS['dbDelta_throw'] = null;
}

if ( ! function_exists( 'dbDelta' ) ) {
	function dbDelta( $sql = '' ): array {
		if ( $GLOBALS['dbDelta_throw'] instanceof \Throwable ) {
			throw $GLOBALS['dbDelta_throw'];
		}
		$GLOBALS['dbDelta_log'][] = (string) $sql;
		return [];
	}
}