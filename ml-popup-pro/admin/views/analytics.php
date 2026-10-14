<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * @var array  $totals
 * @var array  $recent
 * @var array|null $best
 * @var array  $popups
 * @var array  $toast
 * @var array  $device_breakdown
 * @var array  $filters
 */
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
$device_labels = [
	'desktop' => '🖥 Desktop',
	'tablet'  => '📱 Tablet',
	'mobile'  => '📲 Mobile',
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

	<!-- FILTROS -->
	<form method="get" id="mlpp-analytics-filters" class="mlpp-card" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;margin-bottom:16px;padding:14px 18px">
		<input type="hidden" name="page" value="mlpp-analytics">
		<div class="mlpp-field" style="margin:0;flex:1 1 160px">
			<label for="mlpp-filter-period">Período <?php if ( ! mlpp_is_premium() ) : ?><span class="mlpp-chip" style="background:#fef3c7;color:#92400e;font-size:10px;margin-left:4px">Pro</span><?php endif; ?></label>
			<select id="mlpp-filter-period" name="period" <?php echo mlpp_is_premium() ? '' : 'disabled style="opacity:.5"'; ?>>
				<option value="all"  <?php selected( $filters['period'] ?? 'all', 'all' );  ?>>Todos</option>
				<option value="7d"   <?php selected( $filters['period'], '7d' );   ?>>Últimos 7 dias</option>
				<option value="30d"  <?php selected( $filters['period'], '30d' );  ?>>Últimos 30 dias</option>
				<option value="90d"  <?php selected( $filters['period'], '90d' );  ?>>Últimos 90 dias</option>
			</select>
		</div>
		<div class="mlpp-field" style="margin:0;flex:1 1 200px">
			<label for="mlpp-filter-popup">Popup <?php if ( ! mlpp_is_premium() ) : ?><span class="mlpp-chip" style="background:#fef3c7;color:#92400e;font-size:10px;margin-left:4px">Pro</span><?php endif; ?></label>
			<select id="mlpp-filter-popup" name="popup_id" <?php echo mlpp_is_premium() ? '' : 'disabled style="opacity:.5"'; ?>>
				<option value="0">Todos os popups</option>
				<?php foreach ( $popups as $p ) : ?>
					<option value="<?php echo esc_attr( $p['id'] ); ?>" <?php selected( (int) ( $filters['popup_id'] ?? 0 ), (int) $p['id'] ); ?>>
						#<?php echo esc_html( $p['id'] ); ?> — <?php echo esc_html( $p['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="mlpp-field" style="margin:0;flex:1 1 160px">
			<label for="mlpp-filter-device">Dispositivo <?php if ( ! mlpp_is_premium() ) : ?><span class="mlpp-chip" style="background:#fef3c7;color:#92400e;font-size:10px;margin-left:4px">Pro</span><?php endif; ?></label>
			<select id="mlpp-filter-device" name="device" <?php echo mlpp_is_premium() ? '' : 'disabled style="opacity:.5"'; ?>>
				<option value="">Todos</option>
				<option value="desktop" <?php selected( $filters['device'], 'desktop' ); ?>>Desktop</option>
				<option value="tablet"  <?php selected( $filters['device'], 'tablet' );  ?>>Tablet</option>
				<option value="mobile"  <?php selected( $filters['device'], 'mobile' );  ?>>Mobile</option>
			</select>
		</div>
		<div class="mlpp-actions" style="margin:0">
			<button type="submit" class="button button-primary mlpp-btn" <?php echo mlpp_is_premium() ? '' : 'disabled style="opacity:.5"'; ?>>Aplicar</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-analytics' ) ); ?>" class="button">Limpar</a>
		</div>
	</form>

	<?php if ( ! mlpp_is_premium() ) : ?>
		<div class="mlpp-note" style="margin-bottom:16px;background:#fef3c7;color:#92400e">
			🔑 <strong>Modo Free.</strong> Filtros por período, popup e dispositivo estão disponíveis na versão Pro. Ative sua licença em <a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-settings&tab=cfg-activation' ) ); ?>">Configurações → Ativação</a>.
		</div>
	<?php endif; ?>

	<!-- TOTAIS POR EVENTO -->
	<div class="mlpp-stat-grid">
		<?php foreach ( $event_labels as $ev_key => $ev_label ) : ?>
			<article class="mlpp-stat-card">
				<span class="mlpp-stat-label"><?php echo esc_html( $ev_label ); ?></span>
				<strong><?php echo esc_html( number_format_i18n( $totals[ $ev_key ] ?? 0 ) ); ?></strong>
			</article>
		<?php endforeach; ?>
	</div>

	<!-- BREAKDOWN POR DISPOSITIVO -->
	<section class="mlpp-card" style="margin-top:16px">
		<div class="mlpp-card-header"><div><h2>Por dispositivo</h2><p class="mlpp-hero-intro">Distribuição de eventos no recorte atual.</p></div></div>
		<?php if ( function_exists( 'mlpp_capability' ) && mlpp_capability( 'analytics_advanced' ) ) : ?>
			<div class="mlpp-stat-grid" style="grid-template-columns:repeat(3,1fr)">
				<?php
				$device_total = max( 1, array_sum( $device_breakdown ) );
				foreach ( $device_labels as $dv_key => $dv_label ) :
					$count = (int) ( $device_breakdown[ $dv_key ] ?? 0 );
					$pct   = round( $count / $device_total * 100, 1 );
				?>
					<article class="mlpp-stat-card">
						<span class="mlpp-stat-label"><?php echo esc_html( $dv_label ); ?></span>
						<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
						<small><?php echo esc_html( $pct ); ?>% do recorte</small>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php
			if ( function_exists( 'mlpp_render_upgrade_card' ) ) {
				mlpp_render_upgrade_card( 'analytics_advanced', 'Filtros por período/popup/dispositivo e a distribuição de eventos por dispositivo exigem o plano Pro. Ative seu serial em Configurações > 🔑 Ativação para liberar.' );
			} else {
				echo '<p>Requer plano Pro.</p>';
			}
			?>
		<?php endif; ?>
	</section>

	<!-- MELHOR POPUP + ATIVIDADE RECENTE -->
	<div class="mlpp-grid-2" style="margin-top:16px">
		<?php if ( $best ) : ?>
		<article class="mlpp-card">
			<div class="mlpp-card-header"><div><h2>Melhor Popup</h2><p class="mlpp-hero-intro">Maior número de cliques no recorte</p></div></div>
			<div class="mlpp-note">
				<strong>#<?php echo esc_html( $best['popup_id'] ); ?> — <?php echo esc_html( $popup_names[ (int) $best['popup_id'] ] ?? '—' ); ?></strong><br>
				<?php echo esc_html( $best['clicks'] ); ?> cliques registrados
			</div>
		</article>
		<?php endif; ?>

		<article class="mlpp-card">
			<div class="mlpp-card-header"><div><h2>Atividade Recente</h2><p class="mlpp-hero-intro">Últimos 25 eventos no recorte</p></div></div>
			<?php if ( empty( $recent ) ) : ?>
				<p class="mlpp-hero-intro">Nenhum evento no recorte.</p>
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