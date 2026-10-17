<?php
/**
 * Trava do painel sem teste/licença (1.6.0).
 *
 * Sem teste nem licença válidos, as ações de gravação do painel não gravam nada e
 * voltam para a aba Licença. Com teste/licença, gravam normalmente. O front-end não
 * passa por aqui (os popups publicados continuam).
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class PanelLockTest extends TestCase {

	private string $prod_key = '';

	protected function setUp(): void {
		$this->prod_key = MLPP_Test_Hub::use_test_key();
		MLPP_Test_Hub::clear();
		$_REQUEST['_wpnonce'] = 'test-nonce';
		update_option( 'mlpp_settings', [ 'storage_method' => 'cookie', 'marcador' => 'original' ] );
		update_option( 'mlpp_brand', [ 'ml_brand' => '#155e6f' ] );
	}

	protected function tearDown(): void {
		MLPP_Test_Hub::clear();
		MLPP_Test_Hub::restore_key( $this->prod_key );
		unset( $_REQUEST['_wpnonce'], $_POST['mlpp_settings'], $_POST['mlpp_brand'], $_POST['popup_id'] );
		delete_option( 'mlpp_settings' );
		delete_option( 'mlpp_brand' );
	}

	private function run_handler( callable $fn ): ?string {
		try {
			$fn();
		} catch ( MLPP_Test_Redirect $r ) {
			return $r->url;
		}
		return null;
	}

	public function test_guard_write_redirects_to_license_tab_when_locked(): void {
		$url = $this->run_handler( [ 'MLPP_License', 'guard_write' ] );
		$this->assertNotNull( $url, 'Sem licença, guard_write precisa interromper a gravação.' );
		$this->assertStringContainsString( 'page=mlpp-settings&tab=cfg-activation', $url );
		$this->assertStringContainsString( 'mlpp_toast_type=error', $url );
	}

	public function test_guard_write_ajax_answers_error_when_locked(): void {
		ob_start();
		try {
			MLPP_License::guard_write( true );
			$this->fail( 'Sem licença, o AJAX de gravação precisa responder erro.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'wp_send_json_error', $e->getMessage() );
		} finally {
			$out = (string) ob_get_clean();
		}
		$this->assertStringContainsString( '"success":false', $out );
	}

	public function test_guard_write_lets_it_through_with_trial(): void {
		MLPP_Test_Hub::store( 'trial' );
		$this->assertNull( $this->run_handler( [ 'MLPP_License', 'guard_write' ] ) );
	}

	public function test_save_settings_is_blocked_without_license_and_keeps_data(): void {
		$_POST['mlpp_settings'] = [ 'storage_method' => 'localStorage' ];
		$url = $this->run_handler( [ new MLPP_Admin(), 'handle_save_settings' ] );
		$this->assertStringContainsString( 'tab=cfg-activation', (string) $url );
		$this->assertSame( 'original', get_option( 'mlpp_settings' )['marcador'] ?? null, 'Nada é gravado nem apagado.' );
		$this->assertSame( 'cookie', get_option( 'mlpp_settings' )['storage_method'] );
	}

	public function test_save_settings_works_with_trial(): void {
		MLPP_Test_Hub::store( 'trial' );
		$_POST['mlpp_settings'] = [ 'storage_method' => 'localStorage' ];
		$url = $this->run_handler( [ new MLPP_Admin(), 'handle_save_settings' ] );
		$this->assertStringNotContainsString( 'tab=cfg-activation', (string) $url );
		$this->assertSame( 'localStorage', get_option( 'mlpp_settings' )['storage_method'] ?? null );
	}

	public function test_save_brand_is_blocked_without_license(): void {
		$_POST['mlpp_brand'] = [ 'ml_brand' => '#000000' ];
		$url = $this->run_handler( [ new MLPP_Admin(), 'handle_save_brand' ] );
		$this->assertStringContainsString( 'tab=cfg-activation', (string) $url );
		$this->assertSame( '#155e6f', get_option( 'mlpp_brand' )['ml_brand'] );
	}

	public function test_delete_popup_is_blocked_before_touching_the_database(): void {
		// Sem $wpdb nos testes: se a trava falhasse, o handler quebraria ao consultar o banco.
		$_POST['popup_id'] = 7;
		$url = $this->run_handler( [ new MLPP_Admin(), 'handle_delete_popup' ] );
		$this->assertStringContainsString( 'tab=cfg-activation', (string) $url );
	}

	public function test_save_popup_and_import_are_blocked_without_license(): void {
		foreach ( [ 'handle_save_popup', 'handle_import' ] as $handler ) {
			$url = $this->run_handler( [ new MLPP_Admin(), $handler ] );
			$this->assertStringContainsString( 'tab=cfg-activation', (string) $url, $handler );
		}
	}

	public function test_clear_stats_ajax_is_blocked_without_license(): void {
		ob_start();
		try {
			( new MLPP_Admin() )->ajax_clear_stats();
			$this->fail( 'Zerar estatísticas precisa ser bloqueado sem licença.' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'wp_send_json_error', $e->getMessage() );
		} finally {
			$out = (string) ob_get_clean();
		}
		$this->assertStringContainsString( 'ML Popup Pro', $out );
	}

	public function test_front_end_features_stay_open_without_license(): void {
		// Padrão ML: sem licença o que está publicado continua (gates do front-end abertos).
		$this->assertFalse( MLPP_License::is_premium() );
		foreach ( array_keys( MLPP_Gates::FEATURES ) as $feature ) {
			$this->assertTrue( mlpp_capability( $feature ), $feature );
		}
	}
}
