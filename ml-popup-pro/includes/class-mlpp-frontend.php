<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLPP_Frontend {

	private MLPP_Storage   $storage;
	private MLPP_Rules     $rules;
	private MLPP_Analytics $analytics;

	public function __construct() {
		$this->storage   = new MLPP_Storage();
		$this->rules     = new MLPP_Rules();
		$this->analytics = new MLPP_Analytics();
	}

	public function init(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_enqueue' ] );
		add_action( 'wp_footer',          [ $this, 'render_container' ] );
		add_action( 'wp_ajax_mlpp_event',        [ $this->analytics, 'handle_ajax_event' ] );
		add_action( 'wp_ajax_nopriv_mlpp_event', [ $this->analytics, 'handle_ajax_event' ] );
		add_shortcode( 'ml_popup',        [ $this, 'shortcode_popup' ] );
		add_shortcode( 'ml_popup_button', [ $this, 'shortcode_button' ] );
	}

	public function maybe_enqueue(): void {
		$active   = $this->storage->get_active_popups();
		$eligible = $this->rules->get_eligible_popups( $active );
		if ( empty( $eligible ) ) return;

		wp_enqueue_style( 'mlpp-front', MLPP_PLUGIN_URL . 'public/assets/css/mlpp-front.css', [], MLPP_VERSION );
		wp_enqueue_script( 'mlpp-core',  MLPP_PLUGIN_URL . 'public/assets/js/mlpp-core.js',   [], MLPP_VERSION, true );
		wp_localize_script( 'mlpp-core', 'mlppData', [
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'mlpp_frontend_nonce' ),
			'popups'   => $this->prepare_frontend_popups( $eligible ),
			'settings' => $this->get_frontend_settings(),
		] );
	}

	public function render_container(): void {
		$active   = $this->storage->get_active_popups();
		$eligible = $this->rules->get_eligible_popups( $active );
		if ( ! empty( $eligible ) ) {
			echo '<div id="mlpp-container" aria-live="polite"></div>' . "\n";
		}
	}

	private function prepare_frontend_popups( array $popups ): array {
		$out = [];
		foreach ( $popups as $p ) {
			$design      = is_string( $p['design']      ?? null ) ? json_decode( (string) $p['design'],      true ) : (array)( $p['design']      ?? [] );
			$triggers    = is_string( $p['triggers']    ?? null ) ? json_decode( (string) $p['triggers'],    true ) : (array)( $p['triggers']    ?? [] );
			$storage_cfg = is_string( $p['storage_cfg'] ?? null ) ? json_decode( (string) $p['storage_cfg'], true ) : (array)( $p['storage_cfg'] ?? [] );

			$out[] = [
				'id'               => (int) $p['id'],
				'popup_type'       => esc_attr( $p['popup_type']  ?? 'center_modal' ),
				'title'            => esc_html( $p['title']       ?? '' ),
				'subtitle'         => esc_html( $p['subtitle']    ?? '' ),
				'body'             => wp_kses_post( $p['body']    ?? '' ),
				'image_url'        => esc_url( $p['image_url']    ?? '' ),
				'image_alt'        => esc_attr( $p['image_alt']   ?? '' ),
				'image_link_url'   => esc_url( $p['image_link_url'] ?? '' ),
				'image_link_target'=> ( $p['image_link_target'] ?? '_self' ) === '_blank' ? '_blank' : '_self',
				'image_position'   => esc_attr( $p['image_position'] ?? 'top' ),
				'image_fit'        => esc_attr( $p['image_fit']   ?? 'cover' ),
				'image_radius'     => esc_attr( $p['image_radius']?? '8px' ),
				'btn_primary_text' => esc_html( $p['btn_primary_text'] ?? '' ),
				'btn_primary_url'  => esc_url( $p['btn_primary_url']   ?? '' ),
				'btn_secondary_text'=> esc_html( $p['btn_secondary_text'] ?? '' ),
				'btn_secondary_url' => esc_url( $p['btn_secondary_url']  ?? '' ),
				'custom_html'      => wp_kses_post( $p['custom_html']   ?? '' ),
				'design'           => is_array( $design )      ? $design      : [],
				'triggers'         => is_array( $triggers )    ? $triggers    : [],
				'storage_cfg'      => is_array( $storage_cfg ) ? $storage_cfg : [],
			];
		}
		return $out;
	}

	private function get_frontend_settings(): array {
		$s = get_option( 'mlpp_settings', [] );
		return [
			'storage_method'  => esc_attr( $s['storage_method']           ?? 'cookie' ),
			'expiration_days' => absint( $s['default_expiration_days']     ?? 30 ),
			'consent_mode'    => esc_attr( $s['consent_mode']              ?? 'off' ),
		];
	}

	public function shortcode_popup( array $atts ): string {
		$atts  = shortcode_atts( [ 'id' => 0 ], $atts );
		$id    = absint( $atts['id'] );
		if ( ! $id ) return '';
		$popup = $this->storage->get_popup( $id );
		if ( ! $popup || $popup['status'] !== 'active' ) return '';
		return sprintf( '<button class="mlpp-shortcode-trigger" data-mlpp-id="%d">%s</button>', esc_attr( $id ), esc_html( $popup['title'] ) );
	}

	public function shortcode_button( array $atts ): string {
		$atts = shortcode_atts( [ 'id' => 0, 'text' => 'Abrir Popup' ], $atts );
		$id   = absint( $atts['id'] );
		if ( ! $id ) return '';
		return sprintf( '<button class="mlpp-shortcode-trigger" data-mlpp-id="%d">%s</button>', esc_attr( $id ), esc_html( $atts['text'] ) );
	}
}
