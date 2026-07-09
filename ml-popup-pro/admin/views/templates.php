<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $templates @var array $toast */
?>
<div class="mlpp-wrap mlpp-admin-wrap" data-toast-message="<?php echo esc_attr( $toast['message'] ); ?>" data-toast-type="<?php echo esc_attr( $toast['type'] ); ?>">
	<div id="mlpp-toast-area" aria-live="polite" aria-atomic="true"></div>
	<?php $this->render_admin_nav(); ?>

	<section class="mlpp-hero">
		<div class="mlpp-hero-brand">
			<div class="mlpp-hero-mark"><img src="<?php echo esc_url( MLPP_PLUGIN_URL . 'admin/assets/logo.png' ); ?>" alt="ML Lopes Design" width="72" height="72" style="width:72px!important;height:72px!important;max-width:72px!important;max-height:72px!important;object-fit:contain!important;display:block!important;"></div>
			<div class="mlpp-hero-copy">
				<span class="mlpp-hero-eyebrow">ML Popup Pro · Templates</span>
				<h1>Templates de Popup</h1>
				<p class="mlpp-hero-intro">Selecione um template como base para criar um novo popup rapidamente.</p>
			</div>
		</div>
	</section>

	<div class="mlpp-stat-grid">
		<?php foreach ( $templates as $tpl ) : ?>
			<article class="mlpp-card" style="display:flex;flex-direction:column;gap:12px;">
				<div style="font-size:36px;text-align:center;"><?php echo esc_html( $tpl['icon'] ); ?></div>
				<h2 style="margin:0;font-size:16px;text-align:center;"><?php echo esc_html( $tpl['label'] ); ?></h2>
				<p class="mlpp-hero-intro" style="font-size:13px;text-align:center;margin:0;"><?php echo esc_html( $tpl['popup_type'] ?? '' ); ?></p>
				<div style="margin-top:auto;text-align:center;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popup-new&tpl=' . urlencode( $tpl['id'] ) ) ); ?>" class="button button-primary mlpp-btn" style="width:100%;justify-content:center;">Usar este template</a>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
</div>
