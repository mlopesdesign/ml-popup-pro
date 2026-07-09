<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Admin {

	private MLPP_Storage   $storage;
	private MLPP_Analytics $analytics;
	private MLPP_Templates $templates;

	public function __construct() {
		$this->storage   = new MLPP_Storage();
		$this->analytics = new MLPP_Analytics();
		$this->templates = new MLPP_Templates();
	}

	public function init(): void {
		add_action( 'admin_menu', [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_mlpp_save_popup',    [ $this, 'handle_save_popup' ] );
		add_action( 'admin_post_mlpp_delete_popup',  [ $this, 'handle_delete_popup' ] );
		add_action( 'admin_post_mlpp_save_settings', [ $this, 'handle_save_settings' ] );
		add_action( 'admin_post_mlpp_export_popups', [ $this, 'handle_export' ] );
		add_action( 'admin_post_mlpp_import_popups', [ $this, 'handle_import' ] );
		add_action( 'admin_post_mlpp_repair_db',     [ $this, 'handle_repair_db' ] );
		add_action( 'wp_ajax_mlpp_clear_stats',      [ $this, 'ajax_clear_stats' ] );
		add_action( 'wp_ajax_mlpp_reset_frequency',  [ $this, 'ajax_reset_frequency' ] );
		add_action( 'wp_ajax_mlpp_clear_update_cache', [ $this, 'ajax_clear_update_cache' ] );
	}

	public function register_menus(): void {
		add_menu_page(
			'ML Popup Pro', 'ML Popup Pro', 'manage_options',
			'ml-popup-pro', [ $this, 'page_dashboard' ],
			MLPP_PLUGIN_URL . 'admin/assets/menu-icon.png?v=' . rawurlencode( MLPP_VERSION ), 57
		);
		add_submenu_page( 'ml-popup-pro', 'Dashboard',        'Dashboard',        'manage_options', 'ml-popup-pro',      [ $this, 'page_dashboard' ] );
		add_submenu_page( 'ml-popup-pro', 'Pop-ups',          'Pop-ups',           'manage_options', 'mlpp-popups',       [ $this, 'page_popups' ] );
		add_submenu_page( 'ml-popup-pro', 'Adicionar pop-up', 'Adicionar pop-up',  'manage_options', 'mlpp-popup-new',    [ $this, 'page_popup_edit' ] );
		add_submenu_page( 'ml-popup-pro', 'Templates',        'Templates',        'manage_options', 'mlpp-templates',    [ $this, 'page_templates' ] );
		add_submenu_page( 'ml-popup-pro', 'Analytics',        'Analytics',        'manage_options', 'mlpp-analytics',    [ $this, 'page_analytics' ] );
		add_submenu_page( 'ml-popup-pro', 'Configurações',    'Configurações',    'manage_options', 'mlpp-settings',     [ $this, 'page_settings' ] );
		add_submenu_page( null,           'Editar Popup',     'Editar Popup',     'manage_options', 'mlpp-popup-edit',   [ $this, 'page_popup_edit' ] );
	}


	public function render_admin_nav(): void {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : 'ml-popup-pro';
		$items = [
			'ml-popup-pro'   => [ 'Dashboard', admin_url( 'admin.php?page=ml-popup-pro' ) ],
			'mlpp-popups'    => [ 'Pop-ups', admin_url( 'admin.php?page=mlpp-popups' ) ],
			'mlpp-popup-new' => [ 'Adicionar pop-up', admin_url( 'admin.php?page=mlpp-popup-new' ) ],
		];

		$active = $page;
		if ( 'mlpp-popup-edit' === $page ) {
			$active = 'mlpp-popups';
		}

		echo '<nav class="mlpp-admin-nav" aria-label="Navegação principal do ML Popup Pro">';
		foreach ( $items as $slug => $item ) {
			$is_active = $active === $slug;
			printf(
				'<a class="mlpp-admin-nav-link%s" href="%s"%s>%s</a>',
				$is_active ? ' is-active' : '',
				esc_url( $item[1] ),
				$is_active ? ' aria-current="page"' : '',
				esc_html( $item[0] )
			);
		}
		echo '</nav>';
	}

	private function is_plugin_page( string $hook ): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( (string) $_GET['page'] ) ) : '';

		if ( $page === 'ml-popup-pro' || str_starts_with( $page, 'mlpp-' ) ) {
			return true;
		}

		return in_array( $hook, [
			'toplevel_page_ml-popup-pro',
			'ml-popup-pro_page_mlpp-popups',
			'ml-popup-pro_page_mlpp-popup-new',
			'ml-popup-pro_page_mlpp-popup-edit',
			'ml-popup-pro_page_mlpp-templates',
			'ml-popup-pro_page_mlpp-analytics',
			'ml-popup-pro_page_mlpp-settings',
			'admin_page_mlpp-popup-edit',
		], true );
	}

	public function enqueue_assets( string $hook ): void {
		// O ícone precisa permanecer branco e legível em todas as telas do admin,
		// não apenas nas páginas internas do plugin.
		wp_enqueue_style( 'mlpp-admin-menu', MLPP_PLUGIN_URL . 'admin/assets/menu.css', [], MLPP_VERSION );

		if ( ! $this->is_plugin_page( $hook ) ) return;

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media(); // Media Library uploader
		// wp_editor() in the view enqueues TinyMCE automatically when needed.
		// Do NOT call wp_enqueue_editor() here — it loads editor CSS globally
		// and injects img{max-width:100%} which breaks the hero logo sizing.

		wp_enqueue_style( 'mlpp-admin', MLPP_PLUGIN_URL . 'admin/assets/admin.css', [], MLPP_VERSION );
		wp_enqueue_script( 'mlpp-admin', MLPP_PLUGIN_URL . 'admin/assets/admin.js',
			[ 'jquery', 'wp-color-picker', 'media-upload', 'thickbox' ], MLPP_VERSION, true );
		wp_localize_script( 'mlpp-admin', 'mlppAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'mlpp_admin_nonce' ),
		] );
	}

	public function page_dashboard(): void {
		MLPP_Security::check_admin();
		$totals  = $this->analytics->get_totals();
		$popups  = $this->storage->get_all_popups();
		$active  = array_filter( $popups, fn( $p ) => $p['status'] === 'active' );
		$best    = $this->analytics->get_best_popup();
		$recent  = $this->analytics->get_recent_events( 10 );
		$toast   = $this->get_toast();
		require MLPP_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public function page_popups(): void {
		MLPP_Security::check_admin();
		$popups      = $this->storage->get_all_popups();
		$impressions = $this->analytics->get_popup_impressions_by_id();
		$toast       = $this->get_toast();
		require MLPP_PLUGIN_DIR . 'admin/views/popups.php';
	}

	public function page_popup_edit(): void {
		MLPP_Security::check_admin();
		$id     = absint( $_GET['popup_id'] ?? 0 );
		$raw    = $id ? $this->storage->get_popup( $id ) : null;
		$popup  = $raw ? $this->storage->decode_popup( $raw ) : [];
		// Pre-fill from template
		if ( empty( $popup ) && ! empty( $_GET['tpl'] ) ) {
			$tpl = $this->templates->get( sanitize_key( (string) $_GET['tpl'] ) );
			if ( $tpl ) {
				$popup = array_merge( $tpl, [ 'id' => 0, 'status' => 'draft' ] );
			}
		}
		$is_new    = empty( $popup ) || empty( $popup['id'] );
		$templates = $this->templates->get_all();
		$toast     = $this->get_toast();
		// Analytics for this popup
		$popup_stats = $id ? $this->analytics->get_popup_stats( $id ) : [];
		require MLPP_PLUGIN_DIR . 'admin/views/popup-edit.php';
	}

	public function page_templates(): void {
		MLPP_Security::check_admin();
		$templates = $this->templates->get_all();
		$toast     = $this->get_toast();
		require MLPP_PLUGIN_DIR . 'admin/views/templates.php';
	}

	public function page_analytics(): void {
		MLPP_Security::check_admin();
		$totals  = $this->analytics->get_totals();
		$recent  = $this->analytics->get_recent_events( 25 );
		$best    = $this->analytics->get_best_popup();
		$popups  = $this->storage->get_all_popups();
		$toast   = $this->get_toast();
		require MLPP_PLUGIN_DIR . 'admin/views/analytics.php';
	}

	public function page_settings(): void {
		MLPP_Security::check_admin();
		$settings       = get_option( 'mlpp_settings', [] );
		$updater        = new MLPP_Updater();
		$updater_status = $updater->get_status();
		$toast          = $this->get_toast();
		require MLPP_PLUGIN_DIR . 'admin/views/settings.php';
	}

	public function handle_save_popup(): void {
		MLPP_Security::check_admin();
		MLPP_Security::verify_nonce( 'mlpp_save_popup' );
		$raw      = isset( $_POST['mlpp_popup'] ) ? wp_unslash( (array) $_POST['mlpp_popup'] ) : [];
		$data     = MLPP_Security::sanitize_popup( $raw );
		$id       = absint( $raw['id'] ?? 0 );
		$data['id'] = $id;

		// Self-heal: if a save fails because of a missing/broken column, repair
		// the schema once and retry before reporting an error.
		$saved_id = $this->storage->save_popup( $data );
		if ( ! $saved_id && false !== stripos( $this->storage->last_error, 'column' ) ) {
			MLPP_Activator::ensure_schema();
			$saved_id = $this->storage->save_popup( $data );
		}

		if ( ! $saved_id ) {
			$err = $this->storage->last_error ?: 'erro desconhecido ao gravar no banco';
			$back = $id
				? admin_url( 'admin.php?page=mlpp-popup-edit&popup_id=' . $id )
				: admin_url( 'admin.php?page=mlpp-popup-new' );
			$this->redirect_with_toast( $back, 'Falha ao salvar: ' . $err, 'error' );
			return;
		}

		$this->redirect_with_toast(
			admin_url( 'admin.php?page=mlpp-popup-edit&popup_id=' . $saved_id ),
			'Popup salvo com sucesso.', 'success'
		);
	}

	public function handle_delete_popup(): void {
		MLPP_Security::check_admin();
		MLPP_Security::verify_nonce( 'mlpp_delete_popup' );
		$id = absint( $_POST['popup_id'] ?? 0 );
		if ( $id ) $this->storage->delete_popup( $id );
		$this->redirect_with_toast( admin_url( 'admin.php?page=mlpp-popups' ), 'Popup excluído.', 'success' );
	}

	public function handle_repair_db(): void {
		MLPP_Security::check_admin();
		MLPP_Security::verify_nonce( 'mlpp_repair_db' );
		$notes = MLPP_Activator::ensure_schema();
		$this->redirect_with_toast(
			admin_url( 'admin.php?page=mlpp-settings&tab=cfg-updater' ),
			'Banco verificado: ' . implode( ' · ', $notes ),
			'success'
		);
	}

	public function handle_save_settings(): void {
		MLPP_Security::check_admin();
		MLPP_Security::verify_nonce( 'mlpp_save_settings' );
		$raw = isset( $_POST['mlpp_settings'] ) ? wp_unslash( (array) $_POST['mlpp_settings'] ) : [];
		update_option( 'mlpp_settings', MLPP_Security::sanitize_settings( $raw ), false );
		$this->redirect_with_toast( admin_url( 'admin.php?page=mlpp-settings' ), 'Configurações salvas.', 'success' );
	}

	public function handle_export(): void {
		MLPP_Security::check_admin();
		MLPP_Security::verify_nonce( 'mlpp_export_popups' );
		$data = $this->storage->export_popups();
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="mlpp-export-' . date( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	public function handle_import(): void {
		MLPP_Security::check_admin();
		MLPP_Security::verify_nonce( 'mlpp_import_popups' );
		if ( empty( $_FILES['mlpp_import_file']['tmp_name'] ) ) {
			$this->redirect_with_toast( admin_url( 'admin.php?page=mlpp-settings' ), 'Nenhum arquivo enviado.', 'error' );
			return;
		}
		$content = file_get_contents( sanitize_text_field( wp_unslash( (string) $_FILES['mlpp_import_file']['tmp_name'] ) ) );
		$popups  = json_decode( (string) $content, true );
		if ( ! is_array( $popups ) ) {
			$this->redirect_with_toast( admin_url( 'admin.php?page=mlpp-settings' ), 'JSON inválido.', 'error' );
			return;
		}
		$result = $this->storage->import_popups( $popups, ! empty( $_POST['mlpp_overwrite'] ) );
		$msg = sprintf(
			'Importados: %d | Atualizados: %d | Ignorados: %d | Erros: %d',
			$result['inserted'], $result['updated'], $result['skipped'], $result['errors']
		);
		$this->redirect_with_toast( admin_url( 'admin.php?page=mlpp-settings' ), $msg, 'success' );
	}

	public function ajax_clear_stats(): void {
		MLPP_Security::check_admin();
		check_ajax_referer( 'mlpp_admin_nonce', 'nonce' );
		$popup_id = absint( $_POST['popup_id'] ?? 0 );
		if ( ! $popup_id ) wp_send_json_error( 'invalid' );
		$this->analytics->clear_popup_events( $popup_id );
		wp_send_json_success();
	}

	public function ajax_reset_frequency(): void {
		MLPP_Security::check_admin();
		check_ajax_referer( 'mlpp_admin_nonce', 'nonce' );
		// Resets are client-side; nothing to clear server-side for cookie/storage.
		wp_send_json_success( [ 'message' => 'Para resetar, limpe os cookies/localStorage no navegador do visitante ou use o modo incógnito.' ] );
	}

	public function ajax_clear_update_cache(): void {
		MLPP_Security::check_admin();
		check_ajax_referer( 'mlpp_admin_nonce', 'nonce' );
		$updater = new MLPP_Updater();
		$updater->clear_cache();
		delete_site_transient( 'update_plugins' );
		$status = $updater->get_status();
		wp_send_json_success( $status );
	}

	private function get_toast(): array {
		return [
			'message' => isset( $_GET['mlpp_toast'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['mlpp_toast'] ) ) : '',
			'type'    => isset( $_GET['mlpp_toast_type'] ) ? sanitize_key( (string) $_GET['mlpp_toast_type'] ) : 'success',
		];
	}

	private function redirect_with_toast( string $url, string $message, string $type ): void {
		wp_safe_redirect( add_query_arg( [
			'mlpp_toast'      => rawurlencode( $message ),
			'mlpp_toast_type' => $type,
		], $url ) );
		exit;
	}
}
