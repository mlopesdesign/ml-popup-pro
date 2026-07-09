<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MLPP_Plugin {

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
