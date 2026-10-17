<?php
/**
 * Licença pelo ML License Hub (1.6.0+).
 *
 * Substitui os testes do mapeamento de status do servidor antigo
 * (license.mlopesdesign.com.br), que não é mais chamado. O critério agora é o
 * token assinado pelo Hub: estes testes conferem que só um token válido, deste
 * produto, deste site, dentro da validade e assinado pela chave certa libera o painel.
 *
 * @package ML_Popup_Pro
 */

use PHPUnit\Framework\TestCase;

final class LicenseTest extends TestCase {

	private string $prod_key = '';

	protected function setUp(): void {
		$this->prod_key = MLPP_Test_Hub::use_test_key();
		MLPP_Test_Hub::clear();
		$GLOBALS['__mlpp_http'] = [];
	}

	protected function tearDown(): void {
		MLPP_Test_Hub::clear();
		MLPP_Test_Hub::restore_key( $this->prod_key );
		delete_option( MLPP_License::AUTO_TRIAL );
		unset( $GLOBALS['__mlpp_doing_ajax'] );
	}

	// ---- configuração fixa do Hub ------------------------------------------

	public function test_client_points_to_the_tools_site(): void {
		$this->assertSame( 'https://tools.mlopesdesign.com.br/wp-json/mllh/v1', MLPP_License::API_BASE );
		$this->assertSame( 'ml-popup-pro', MLPP_License::PRODUCT_ID );
		$this->assertSame( 'VGZQp2X3RJMuDmQBlIEXS4fqsZD1CnFlIJgfVtY/pHo=', $this->prod_key, 'O cliente deve nascer com a chave pública do Hub de produção.' );
		$this->assertSame( 'mllc_ml_popup_pro', MLPP_License::client()->option_name() );
		$this->assertSame( 'https://tools.mlopesdesign.com.br/precos/', MLPP_License::BUY_URL );
		$this->assertSame( 'https://tools.mlopesdesign.com.br/minha-conta/', MLPP_License::ACCOUNT_URL );
	}

	public function test_public_key_appears_in_a_single_plugin_file(): void {
		$hits = [];
		$it   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( (string) realpath( MLPP_PLUGIN_DIR ), FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			$path = (string) $file;
			if ( '.php' !== substr( $path, -4 ) || false !== strpos( $path, '/vendor/' ) || false !== strpos( $path, '/tests/' ) ) {
				continue;
			}
			$n = substr_count( (string) file_get_contents( $path ), 'VGZQp2X3RJMuDmQBlIEXS4fqsZD1CnFlIJgfVtY/pHo=' );
			if ( $n ) {
				$hits[ basename( $path ) ] = $n;
			}
		}
		$this->assertSame( [ 'class-mlpp-license.php' => 1 ], $hits );
	}

	public function test_old_license_servers_are_not_referenced_in_code(): void {
		$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( (string) realpath( MLPP_PLUGIN_DIR ), FilesystemIterator::SKIP_DOTS ) );
		foreach ( $it as $file ) {
			$path = (string) $file;
			if ( '.php' !== substr( $path, -4 ) && '.js' !== substr( $path, -3 ) ) {
				continue;
			}
			if ( false !== strpos( $path, '/vendor/' ) || false !== strpos( $path, '/tests/' ) ) {
				continue;
			}
			$code = (string) file_get_contents( $path );
			foreach ( [ 'license.mlopesdesign.com.br', 'hub.mlopesdesign.com.br', 'api/license.php' ] as $old ) {
				$this->assertStringNotContainsString( $old, $code, basename( $path ) . " ainda cita $old" );
			}
		}
	}

	// ---- critério de liberação ---------------------------------------------

	public function test_nothing_stored_is_not_premium(): void {
		$this->assertFalse( MLPP_License::is_premium() );
		$this->assertTrue( MLPP_License::is_locked() );
		$this->assertFalse( mlpp_is_premium() );
	}

	public function test_legacy_valid_status_no_longer_unlocks(): void {
		update_option( MLPP_License::LEGACY_OPTION_STATUS, 'valid' );
		update_option( MLPP_License::LEGACY_OPTION_KEY, 'AAAA-BBBB-CCCC-DDDD' );
		$this->assertFalse( MLPP_License::is_premium(), 'A licença do servidor antigo não vale mais.' );
		// As options antigas não são apagadas.
		$this->assertSame( 'valid', get_option( MLPP_License::LEGACY_OPTION_STATUS ) );
		delete_option( MLPP_License::LEGACY_OPTION_STATUS );
		delete_option( MLPP_License::LEGACY_OPTION_KEY );
	}

	public function test_legacy_wp_config_constant_no_longer_unlocks(): void {
		if ( ! defined( 'MLPP_LICENSE_KEY' ) ) {
			define( 'MLPP_LICENSE_KEY', 'QUALQUER-COISA' );
		}
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_signed_trial_token_unlocks(): void {
		MLPP_Test_Hub::store( 'trial' );
		$this->assertTrue( MLPP_License::is_premium() );
		$this->assertFalse( MLPP_License::is_locked() );
		$this->assertTrue( mlpp_is_premium() );
	}

	public function test_signed_license_token_unlocks(): void {
		MLPP_Test_Hub::store( 'license', [ 'plan' => 'annual' ], 'MLPP-ABCDE-12345' );
		$this->assertTrue( MLPP_License::is_premium() );
	}

	public function test_expired_token_locks(): void {
		MLPP_Test_Hub::store( 'trial', [ 'exp' => time() - 60 ] );
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_token_without_premium_flag_locks(): void {
		MLPP_Test_Hub::store( 'license', [ 'premium' => false ], 'MLPP-ABCDE-12345' );
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_token_for_another_product_is_rejected(): void {
		MLPP_Test_Hub::store( 'license', [ 'product' => 'ml-gif-pro' ], 'MLPP-ABCDE-12345' );
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_token_for_another_site_is_rejected(): void {
		MLPP_Test_Hub::store( 'license', [ 'instance' => hash( 'sha256', 'ml-popup-pro|outro-site.com' ) ], 'MLPP-ABCDE-12345' );
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_token_signed_with_another_key_is_rejected(): void {
		$other = sodium_crypto_sign_secretkey( sodium_crypto_sign_keypair() );
		update_option( MLPP_Test_Hub::option(), [ 'mode' => 'license', 'license_key' => 'X', 'token' => MLPP_Test_Hub::token( [], $other ) ] );
		MLPP_License::forget();
		$this->assertFalse( MLPP_License::is_premium() );
	}

	public function test_tampered_payload_is_rejected(): void {
		$token = MLPP_Test_Hub::token( [ 'premium' => false ] );
		[ , $sig ] = explode( '.', $token );
		$forged = rtrim( strtr( base64_encode( (string) json_encode( [ 'product' => 'ml-popup-pro', 'instance' => MLPP_License::client()->instance_id(), 'premium' => true, 'exp' => time() + 3600 ] ) ), '+/', '-_' ), '=' );
		update_option( MLPP_Test_Hub::option(), [ 'mode' => 'license', 'license_key' => 'X', 'token' => $forged . '.' . $sig ] );
		MLPP_License::forget();
		$this->assertFalse( MLPP_License::is_premium() );
	}

	// ---- auto-teste ----------------------------------------------------------

	public function test_auto_trial_runs_once_per_site_and_calls_the_hub(): void {
		MLPP_License::maybe_auto_trial();
		$this->assertNotEmpty( get_option( MLPP_License::AUTO_TRIAL ), 'Marca a tentativa para não repetir.' );
		$this->assertCount( 1, $GLOBALS['__mlpp_http'] );
		$this->assertSame( 'https://tools.mlopesdesign.com.br/wp-json/mllh/v1/license/trial', $GLOBALS['__mlpp_http'][0]['url'] );

		MLPP_License::maybe_auto_trial();
		$this->assertCount( 1, $GLOBALS['__mlpp_http'], 'Segunda visita não tenta de novo.' );
	}

	public function test_auto_trial_is_skipped_on_ajax_and_with_existing_license(): void {
		$GLOBALS['__mlpp_doing_ajax'] = true;
		MLPP_License::maybe_auto_trial();
		$this->assertFalse( get_option( MLPP_License::AUTO_TRIAL ) );
		unset( $GLOBALS['__mlpp_doing_ajax'] );

		MLPP_Test_Hub::store( 'license', [ 'plan' => 'annual' ], 'MLPP-ABCDE-12345' );
		MLPP_License::maybe_auto_trial();
		$this->assertSame( [], $GLOBALS['__mlpp_http'], 'Com licença válida não pede teste.' );
	}

	// ---- resumo da aba Licença -----------------------------------------------

	public function test_summary_for_trial_and_for_locked_site(): void {
		MLPP_Test_Hub::store( 'trial' );
		$s = MLPP_License::summary();
		$this->assertSame( 'Teste', $s['label'] );
		$this->assertTrue( $s['is_trial'] );
		$this->assertFalse( $s['can_start_trial'] );
		$this->assertSame( 'Teste grátis', $s['plan_label'] );
		$this->assertMatchesRegularExpression( '/^\d+$/', $s['days_left'] );

		MLPP_Test_Hub::clear();
		$s = MLPP_License::summary();
		$this->assertSame( 'Sem licença', $s['label'] );
		$this->assertFalse( $s['active'] );
		$this->assertTrue( $s['can_start_trial'] );
		$this->assertSame( '—', $s['valid_until'] );
	}
}
