<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $popups @var array $impressions @var array $toast */
$type_labels = [
	'center_modal'       => 'Modal Central',
	'bottom_bar'         => 'Barra Inferior',
	'slide_in'           => 'Slide-in',
	'fullscreen_overlay' => 'Fullscreen',
	'floating_box'       => 'Caixa Flutuante',
];
?>
<div class="mlpp-wrap mlpp-admin-wrap" data-toast-message="<?php echo esc_attr( $toast['message'] ); ?>" data-toast-type="<?php echo esc_attr( $toast['type'] ); ?>">
	<div id="mlpp-toast-area" aria-live="polite" aria-atomic="true"></div>
	<?php $this->render_admin_nav(); ?>

	<section class="mlpp-hero">
		<div class="mlpp-hero-brand">
			<div class="mlpp-hero-mark"><img src="<?php echo esc_url( MLPP_PLUGIN_URL . 'admin/assets/logo.png' ); ?>" alt="ML Lopes Design" width="72" height="72" style="width:72px!important;height:72px!important;max-width:72px!important;max-height:72px!important;object-fit:contain!important;display:block!important;"></div>
			<div class="mlpp-hero-copy">
				<span class="mlpp-hero-eyebrow">ML Popup Pro · Popups</span>
				<h1>Todos os Popups</h1>
				<p class="mlpp-hero-intro">Gerencie, edite e controle todos os seus popups cadastrados.</p>
			</div>
		</div>
		<div class="mlpp-hero-meta">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popup-new' ) ); ?>" class="button button-primary mlpp-btn">+ Novo Popup</a>
		</div>
	</section>

	<article class="mlpp-card">
		<?php if ( empty( $popups ) ) : ?>
			<div class="mlpp-note">Nenhum popup cadastrado ainda. <a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popup-new' ) ); ?>">Criar o primeiro popup</a>.</div>
		<?php else : ?>
			<table class="mlpp-table" style="border-radius:14px;overflow:hidden;">
				<thead>
					<tr>
						<th style="width:40px;">ID</th>
						<th>Nome</th>
						<th>Tipo</th>
						<th>Status</th>
						<th>Prioridade</th>
						<th>Impressões</th>
						<th>Ações</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $popups as $p ) :
					$status_label = [
						'active' => '<span class="mlpp-chip" style="background:#d1fae5;color:#065f46;">Ativo</span>',
						'paused' => '<span class="mlpp-chip" style="background:#fef9c3;color:#854d0e;">Pausado</span>',
						'draft'  => '<span class="mlpp-chip" style="background:#f1f5f9;color:#475569;">Rascunho</span>',
					][ $p['status'] ] ?? esc_html( $p['status'] );
				?>
					<tr>
						<td><?php echo esc_html( $p['id'] ); ?></td>
						<td><strong><?php echo esc_html( $p['name'] ); ?></strong></td>
						<td><?php echo esc_html( $type_labels[ $p['popup_type'] ] ?? $p['popup_type'] ); ?></td>
						<td><?php echo wp_kses_post( $status_label ); ?></td>
						<td><?php echo esc_html( $p['priority'] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( $impressions[ (int) $p['id'] ] ?? 0 ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popup-edit&popup_id=' . absint( $p['id'] ) ) ); ?>" class="button">Editar</a>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;" onsubmit="return confirm('Excluir este popup?');">
								<?php wp_nonce_field( 'mlpp_delete_popup' ); ?>
								<input type="hidden" name="action" value="mlpp_delete_popup">
								<input type="hidden" name="popup_id" value="<?php echo esc_attr( $p['id'] ); ?>">
								<button type="submit" class="button" style="color:#d92d20;border-color:#d92d20;">Excluir</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</article>
</div>
