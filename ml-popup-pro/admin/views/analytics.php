<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $totals @var array $recent @var array|null $best @var array $popups @var array $toast */
$popup_names = [];
foreach ( $popups as $p ) {
	$popup_names[ (int) $p['id'] ] = $p['name'];
}
$event_labels = [
	'impression'      => 'Impressão',
	'open'            => 'Abertura',
	'close'           => 'Fechamento',
	'primary_click'   => 'Clique Primário',
	'secondary_click' => 'Clique Secundário',
	'image_click'     => 'Clique na Imagem',
	'conversion'      => 'Conversão',
];
?>
<div class="mlpp-wrap mlpp-admin-wrap" data-toast-message="<?php echo esc_attr( $toast['message'] ); ?>" data-toast-type="<?php echo esc_attr( $toast['type'] ); ?>">
	<div id="mlpp-toast-area" aria-live="polite" aria-atomic="true"></div>
	<?php $this->render_admin_nav(); ?>

	<section class="mlpp-hero">
		<div class="mlpp-hero-brand">
			<div class="mlpp-hero-mark"><img src="<?php echo esc_url( MLPP_PLUGIN_URL . 'admin/assets/logo.png' ); ?>" alt="ML Lopes Design" width="72" height="72" style="width:72px!important;height:72px!important;max-width:72px!important;max-height:72px!important;object-fit:contain!important;display:block!important;"></div>
			<div class="mlpp-hero-copy">
				<span class="mlpp-hero-eyebrow">ML Popup Pro · Analytics</span>
				<h1>Analytics</h1>
				<p class="mlpp-hero-intro">Métricas locais de performance dos popups sem coleta de dados pessoais.</p>
			</div>
		</div>
	</section>

	<div class="mlpp-stat-grid">
		<?php foreach ( $event_labels as $ev_key => $ev_label ) : ?>
			<article class="mlpp-stat-card">
				<span class="mlpp-stat-label"><?php echo esc_html( $ev_label ); ?></span>
				<strong><?php echo esc_html( number_format_i18n( $totals[ $ev_key ] ?? 0 ) ); ?></strong>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="mlpp-grid-2">
		<?php if ( $best ) : ?>
		<article class="mlpp-card">
			<div class="mlpp-card-header"><div><h2>Melhor Popup</h2><p class="mlpp-hero-intro">Maior número de cliques</p></div></div>
			<div class="mlpp-note">
				<strong>#<?php echo esc_html( $best['popup_id'] ); ?> — <?php echo esc_html( $popup_names[ (int) $best['popup_id'] ] ?? '—' ); ?></strong><br>
				<?php echo esc_html( $best['clicks'] ); ?> cliques registrados
			</div>
		</article>
		<?php endif; ?>

		<article class="mlpp-card">
			<div class="mlpp-card-header"><div><h2>Atividade Recente</h2><p class="mlpp-hero-intro">Últimos 25 eventos</p></div></div>
			<?php if ( empty( $recent ) ) : ?>
				<p class="mlpp-hero-intro">Nenhum evento ainda.</p>
			<?php else : ?>
				<table class="widefat fixed striped" style="border-radius:14px;overflow:hidden;font-size:13px;">
					<thead>
						<tr>
							<th>Popup</th>
							<th>Evento</th>
							<th>Dispositivo</th>
							<th>Data</th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $recent as $ev ) : ?>
						<tr>
							<td>#<?php echo esc_html( $ev['popup_id'] ); ?> <?php echo esc_html( $popup_names[ (int) $ev['popup_id'] ] ?? '' ); ?></td>
							<td><span class="mlpp-chip"><?php echo esc_html( $event_labels[ $ev['event_type'] ] ?? $ev['event_type'] ); ?></span></td>
							<td><?php echo esc_html( $ev['device_type'] ); ?></td>
							<td><?php echo esc_html( $ev['created_at'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</article>
	</div>
</div>
