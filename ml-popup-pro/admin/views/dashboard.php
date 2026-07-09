<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $totals @var array $popups @var array $active @var array|null $best @var array $recent @var array $toast */
$impressions     = (int) ( $totals['impression'] ?? 0 );
$total_clicks    = (int) ( ( $totals['primary_click'] ?? 0 ) + ( $totals['secondary_click'] ?? 0 ) + ( $totals['image_click'] ?? 0 ) );
$active_count    = count( $active );
$event_labels = [
	'impression'      => 'Impressão',
	'open'            => 'Abertura',
	'close'           => 'Fechamento',
	'primary_click'   => 'Clique primário',
	'secondary_click' => 'Clique secundário',
	'image_click'     => 'Clique na imagem',
	'conversion'      => 'Conversão',
];
$best_popup_name = '';
if ( $best ) {
	foreach ( $popups as $p ) {
		if ( (int) $p['id'] === (int) $best['popup_id'] ) {
			$best_popup_name = $p['name'];
			break;
		}
	}
}
?>
<div class="mlpp-wrap mlpp-admin-wrap" data-toast-message="<?php echo esc_attr( $toast['message'] ); ?>" data-toast-type="<?php echo esc_attr( $toast['type'] ); ?>">
	<div id="mlpp-toast-area" aria-live="polite" aria-atomic="true"></div>
	<?php $this->render_admin_nav(); ?>

	<section class="mlpp-hero">
		<div class="mlpp-hero-brand">
			<div class="mlpp-hero-mark"><img src="<?php echo esc_url( MLPP_PLUGIN_URL . 'admin/assets/logo.png' ); ?>" alt="ML Lopes Design" width="72" height="72" style="width:72px!important;height:72px!important;max-width:72px!important;max-height:72px!important;object-fit:contain!important;display:block!important;"></div>
			<div class="mlpp-hero-copy">
				<span class="mlpp-hero-eyebrow">ML Lopes Design · Popup Manager</span>
				<h1>ML Popup Pro</h1>
				<p class="mlpp-hero-intro">Gerencie campanhas de popup com regras avançadas, agendamento, analytics e templates profissionais.</p>
			</div>
		</div>
		<div class="mlpp-hero-meta">
			<span class="mlpp-badge">v<?php echo esc_html( MLPP_VERSION ); ?></span>
			<div class="mlpp-hero-tags">
				<span class="mlpp-chip"><?php echo esc_html( $active_count ); ?> popup(s) ativo(s)</span>
				<span class="mlpp-chip"><?php echo esc_html( count( $popups ) ); ?> total</span>
			</div>
		</div>
	</section>

	<div class="mlpp-stat-grid">
		<article class="mlpp-stat-card">
			<span class="mlpp-stat-label">Total de Impressões</span>
			<strong><?php echo esc_html( number_format_i18n( $impressions ) ); ?></strong>
			<small>Aberturas registradas</small>
		</article>
		<article class="mlpp-stat-card">
			<span class="mlpp-stat-label">Total de Cliques</span>
			<strong><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></strong>
			<small>Primários + secundários</small>
		</article>
		<article class="mlpp-stat-card">
			<span class="mlpp-stat-label">Popups Ativos</span>
			<strong><?php echo esc_html( $active_count ); ?></strong>
			<small>De <?php echo esc_html( count( $popups ) ); ?> cadastrados</small>
		</article>
		<article class="mlpp-stat-card">
			<span class="mlpp-stat-label">Melhor Popup</span>
			<strong><?php echo $best_popup_name ? esc_html( $best_popup_name ) : '—'; ?></strong>
			<small><?php echo $best ? esc_html( $best['clicks'] . ' cliques' ) : 'Sem dados ainda'; ?></small>
		</article>
	</div>

	<div class="mlpp-grid-2">
		<article class="mlpp-card">
			<div class="mlpp-card-header">
				<div><h2>Ações Rápidas</h2><p class="mlpp-hero-intro">Crie ou gerencie seus popups.</p></div>
			</div>
			<div class="mlpp-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popup-new' ) ); ?>" class="button button-primary mlpp-btn">+ Novo Popup</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popups' ) ); ?>" class="button mlpp-btn">Ver todos os popups</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-templates' ) ); ?>" class="button mlpp-btn">Templates</a>
			</div>
		</article>

		<article class="mlpp-card">
			<div class="mlpp-card-header">
				<div><h2>Atividade Recente</h2><p class="mlpp-hero-intro">Últimos eventos registrados.</p></div>
			</div>
			<?php if ( empty( $recent ) ) : ?>
				<p class="mlpp-hero-intro">Nenhum evento registrado ainda.</p>
			<?php else : ?>
				<table class="mlpp-table" style="width:100%;border-collapse:collapse;font-size:13px;">
					<thead><tr><th style="text-align:left;padding:6px 8px;border-bottom:1px solid var(--ml-line);">Popup ID</th><th style="text-align:left;padding:6px 8px;border-bottom:1px solid var(--ml-line);">Evento</th><th style="text-align:left;padding:6px 8px;border-bottom:1px solid var(--ml-line);">Data</th></tr></thead>
					<tbody>
					<?php foreach ( $recent as $ev ) : ?>
						<tr>
							<td style="padding:6px 8px;border-bottom:1px solid var(--ml-line);">#<?php echo esc_html( $ev['popup_id'] ); ?></td>
							<td style="padding:6px 8px;border-bottom:1px solid var(--ml-line);"><span class="mlpp-chip mlpp-chip-event"><?php echo esc_html( $event_labels[ $ev['event_type'] ] ?? $ev['event_type'] ); ?></span></td>
							<td style="padding:6px 8px;border-bottom:1px solid var(--ml-line);"><?php echo esc_html( $ev['created_at'] ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</article>
	</div>
</div>
