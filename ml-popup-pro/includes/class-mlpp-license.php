<?php
/**
 * ML Popup Pro – Licença.
 *
 * 1.6.0: a licença passa a ser emitida e conferida pelo site tools.mlopesdesign.com.br
 * (módulo ML License Hub, cliente ML_License_Client_V2). O servidor antigo
 * (endpoint "license" do domínio antigo de licenças) não é mais consultado, e os campos
 * "Servidor" e "Produto" saíram da tela.
 *
 * Regras do padrão ML:
 *   - teste grátis de 30 dias por site (uma vez por site; reinstalar não reinicia);
 *   - licença anual vale em 1 site; a chave do Pacote Agência vale em todos os plugins ML;
 *   - quem já usava o plugin recebe o teste automaticamente na primeira visita ao painel;
 *   - sem teste/licença válidos, só o painel do plugin trava para gravar (criar, editar,
 *     apagar, importar, salvar configurações, zerar estatísticas). Os popups publicados
 *     continuam aparecendo no site, as estatísticas continuam sendo registradas e nenhum
 *     dado é apagado.
 *
 * As options antigas (mlpp_license_key, mlpp_license_status, mlpp_license_details,
 * mlpp_license_cache, mlpp_product_id) ficam no banco, mas não são mais usadas: uma
 * option antiga "valid" ou a constante MLPP_LICENSE_KEY não liberam mais o plugin.
 *
 * @package ML_Popup_Pro
 * @since   1.1.0
 * @updated 1.6.0  Licença pelo ML License Hub (tools.mlopesdesign.com.br)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ml-license-client.php';

final class MLPP_License {

	const API_BASE    = 'https://tools.mlopesdesign.com.br/wp-json/mllh/v1';
	const PUBLIC_KEY  = 'VGZQp2X3RJMuDmQBlIEXS4fqsZD1CnFlIJgfVtY/pHo=';
	const PRODUCT_ID  = 'ml-popup-pro';
	const BUY_URL     = 'https://tools.mlopesdesign.com.br/precos/';
	const ACCOUNT_URL = 'https://tools.mlopesdesign.com.br/minha-conta/';
	const AUTO_TRIAL  = 'mlpp_auto_trial_tried';
	const TAB_URL     = 'admin.php?page=mlpp-settings&tab=cfg-activation';

	/** Options antigas (até 1.5.7). Ficam no banco; não são mais lidas para liberar o plugin. */
	const LEGACY_OPTION_KEY     = 'mlpp_license_key';
	const LEGACY_OPTION_STATUS  = 'mlpp_license_status';
	const LEGACY_OPTION_DETAILS = 'mlpp_license_details';
	const LEGACY_OPTION_CACHE   = 'mlpp_license_cache';
	const LEGACY_OPTION_PRODUCT = 'mlpp_product_id';

	/** @var ML_License_Client_V2|null */
	private static $client = null;

	/** @var bool|null Resultado guardado durante a requisição (zera quando a licença muda). */
	private static $locked = null;

	public static function client(): ML_License_Client_V2 {
		if ( null === self::$client ) {
			self::$client = new ML_License_Client_V2(
				[
					'api_base'   => self::API_BASE,
					'product'    => self::PRODUCT_ID,
					'public_key' => self::PUBLIC_KEY,
					'version'    => MLPP_VERSION,
				]
			);
		}
		return self::$client;
	}

	/**
	 * Único critério de liberação: teste grátis ou licença válidos neste site,
	 * conforme o token assinado pelo ML License Hub.
	 */
	public static function is_premium(): bool {
		return self::client()->is_premium();
	}

	/** Painel travado para gravar (sem teste nem licença). */
	public static function is_locked(): bool {
		if ( null === self::$locked ) {
			self::$locked = ! self::is_premium();
		}
		return self::$locked;
	}

	public static function forget(): void {
		self::$locked = null;
	}

	/* ------------------------------------------------------------------ ganchos */

	public static function boot(): void {
		self::client(); // agenda a renovação diária
		add_action( 'admin_init', [ __CLASS__, 'maybe_auto_trial' ], 5 );
		add_action( 'admin_notices', [ __CLASS__, 'locked_notice' ] );
		$opt = self::client()->option_name();
		foreach ( [ 'added_option', 'updated_option', 'deleted_option' ] as $hook ) {
			add_action(
				$hook,
				static function ( $name ) use ( $opt ) {
					if ( $name === $opt ) {
						MLPP_License::forget();
					}
				}
			);
		}
	}

	/** Primeira visita ao painel: começa o teste grátis sozinho (uma tentativa por site). */
	public static function maybe_auto_trial(): void {
		if ( wp_doing_ajax() || ! current_user_can( 'manage_options' ) || get_option( self::AUTO_TRIAL ) ) {
			return;
		}
		update_option( self::AUTO_TRIAL, time(), false );
		$state = self::client()->state();
		if ( empty( $state['active'] ) && 'none' === $state['mode'] && empty( $state['trial_used'] ) ) {
			self::client()->start_trial();
		}
	}

	/**
	 * Chamado no começo das ações de gravação do painel (admin-post e AJAX).
	 * Sem licença: não grava nada e volta para a aba Licença com o aviso.
	 */
	public static function guard_write( bool $ajax = false ): void {
		if ( ! self::is_locked() ) {
			return;
		}
		$msg = self::locked_message();
		if ( $ajax ) {
			wp_send_json_error( [ 'message' => $msg ], 403 );
		}
		self::redirect_with_toast( $msg, 'error' );
	}

	public static function locked_message(): string {
		return __( 'Ative a licença (ou comece o teste grátis) para alterar o ML Popup Pro. Os popups publicados continuam aparecendo no site.', 'ml-popup-pro' );
	}

	public static function locked_notice(): void {
		if ( ! current_user_can( 'manage_options' ) || ! self::is_plugin_screen() || ! self::is_locked() ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p><strong>ML Popup Pro:</strong> %s <a href="%s">%s</a></p></div>',
			esc_html__( 'sem licença ativa — o painel está só para leitura. Os popups publicados continuam aparecendo no site.', 'ml-popup-pro' ),
			esc_url( admin_url( self::TAB_URL ) ),
			esc_html__( 'Ativar licença', 'ml-popup-pro' )
		);
	}

	private static function is_plugin_screen(): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		return 'ml-popup-pro' === $page || 0 === strpos( $page, 'mlpp-' );
	}

	/* ------------------------------------------------------------------ tela */

	/** Estado pronto para a aba Licença. */
	public static function summary(): array {
		$state  = self::client()->state();
		$active = ! empty( $state['active'] );
		$mode   = (string) ( $state['mode'] ?? 'none' );
		$plan   = strtolower( (string) ( $state['plan'] ?? '' ) );
		$days   = $state['days_left'] ?? null;

		$plans = [
			'trial'    => 'Teste grátis',
			'monthly'  => 'Mensal',
			'annual'   => 'Anual',
			'yearly'   => 'Anual',
			'lifetime' => 'Vitalícia',
			'manual'   => 'Cortesia',
			'courtesy' => 'Cortesia',
		];
		$plan_label = $plans[ $plan ] ?? ( '' !== $plan ? ucfirst( $plan ) : '—' );

		if ( $active && 'trial' === $mode ) {
			$label   = 'Teste';
			$tone    = 'trial';
			$message = null === $days
				? 'Teste grátis ativo neste site.'
				: sprintf( 1 === (int) $days ? 'Teste grátis ativo: falta %d dia.' : 'Teste grátis ativo: faltam %d dias.', (int) $days );
		} elseif ( $active ) {
			$label   = 'Ativa';
			$tone    = 'full';
			$message = 'Licença ativa neste site.';
		} else {
			$label = 'Sem licença';
			$tone  = 'free';
			if ( 'trial_expired' === ( $state['status'] ?? '' ) ) {
				$message = 'O teste grátis terminou. Ative uma licença para voltar a criar e editar popups. Os popups publicados continuam aparecendo no site.';
			} elseif ( '' !== (string) ( $state['message'] ?? '' ) ) {
				$message = (string) $state['message'];
			} else {
				$message = 'Comece o teste grátis de 30 dias ou ative a sua licença.';
			}
		}

		$expires_label = '';
		if ( ! empty( $state['expires_at'] ) ) {
			$ts = strtotime( (string) $state['expires_at'] );
			if ( $ts ) {
				$expires_label = wp_date( 'd/m/Y', $ts );
			}
		}
		if ( ! $active ) {
			$valid_until = '—';
		} elseif ( 'lifetime' === $plan || '' === $expires_label ) {
			$valid_until = 'Sem vencimento';
		} else {
			$valid_until = $expires_label;
		}

		return [
			'active'          => $active,
			'mode'            => $mode,
			'is_trial'        => $active && 'trial' === $mode,
			'label'           => $label,
			'tone'            => $tone,
			'message'         => $message,
			'plan_label'      => $plan_label,
			'valid_until'     => $valid_until,
			'days_left'       => ( $active && null !== $days && '' !== $expires_label ) ? (string) (int) $days : '—',
			'key_masked'      => (string) ( $state['key_masked'] ?? '' ),
			'can_start_trial' => ! $active && empty( $state['trial_used'] ) && 'license' !== $mode,
			'domain'          => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
		];
	}

	/** Rótulo curto para o selo da aba. */
	public static function status_label(): string {
		return self::summary()['label'];
	}

	/* ------------------------------------------------------------------ ações */

	public static function handle_activate(): void {
		self::guard_admin_request( 'mlpp_activate_license' );
		$key    = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['license_key'] ) ) : '';
		$result = self::client()->activate( $key );
		if ( is_wp_error( $result ) ) {
			self::redirect_with_toast( $result->get_error_message(), 'error' );
		}
		self::redirect_with_toast( __( 'Licença ativada neste site.', 'ml-popup-pro' ), 'success' );
	}

	/** Começa o teste grátis ou confere a licença agora. */
	public static function handle_sync(): void {
		self::guard_admin_request( 'mlpp_sync_license' );
		$op     = isset( $_POST['mlpp_op'] ) ? sanitize_key( wp_unslash( (string) $_POST['mlpp_op'] ) ) : 'check';
		$result = 'trial' === $op ? self::client()->start_trial() : self::client()->refresh();
		if ( is_wp_error( $result ) ) {
			self::redirect_with_toast( $result->get_error_message(), 'error' );
		}
		self::redirect_with_toast(
			'trial' === $op ? __( 'Teste grátis de 30 dias ativo neste site.', 'ml-popup-pro' ) : __( 'Licença conferida.', 'ml-popup-pro' ),
			'success'
		);
	}

	public static function handle_remove(): void {
		self::guard_admin_request( 'mlpp_remove_license' );
		self::client()->deactivate();
		self::redirect_with_toast( __( 'Licença removida deste site. A chave pode ser usada em outro site.', 'ml-popup-pro' ), 'success' );
	}

	private static function guard_admin_request( string $nonce_action ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permissão insuficiente.', 'ml-popup-pro' ) );
		}
		check_admin_referer( $nonce_action );
	}

	private static function redirect_with_toast( string $message, string $type ): void {
		wp_safe_redirect(
			add_query_arg(
				[
					'mlpp_toast'      => rawurlencode( $message ),
					'mlpp_toast_type' => $type,
				],
				admin_url( self::TAB_URL )
			)
		);
		exit;
	}
}

/**
 * Atalho: true quando há teste grátis ou licença válidos neste site.
 */
function mlpp_is_premium(): bool {
	return MLPP_License::is_premium();
}
