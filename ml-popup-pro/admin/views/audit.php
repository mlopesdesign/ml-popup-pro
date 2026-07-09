<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $entries */
$action_labels = [
	'create'     => '➕ Criou',
	'update'     => '✏️ Editou',
	'delete'     => '🗑 Excluiu',
	'activate'   => '🔑 Ativou',
	'deactivate' => '🔓 Desativou',
	'import'     => '⬆ Importou',
	'export'     => '⬇ Exportou',
];
?>
<div class="mlpp-wrap mlpp-admin-wrap">
	<section class="mlpp-hero">
		<div class="mlpp-hero-brand">
			<div class="mlpp-hero-mark"><img src="<?php echo esc_url( MLPP_PLUGIN_URL . 'admin/assets/logo.png' ); ?>" alt="ML Lopes Design" width="72" height="72" style="width:72px!important;height:72px!important;max-width:72px!important;max-height:72px!important;object-fit:contain!important;display:block!important;"></div>
			<div class="mlpp-hero-copy">
				<span class="mlpp-hero-eyebrow">ML Popup Pro · Auditoria</span>
				<h1>📜 Histórico de mudanças</h1>
				<p class="mlpp-hero-intro">Quem editou/excluiu/atualizou cada popup. Útil pra compliance LGPD e investigação de bugs.</p>
			</div>
		</div>
	</section>

	<article class="mlpp-card">
		<?php if ( empty( $entries ) ) : ?>
			<p class="mlpp-hero-intro">Nenhuma alteração registrada ainda. As ações de criar/editar/excluir popup aparecem aqui automaticamente.</p>
		<?php else : ?>
			<table class="widefat fixed striped" style="border-radius:14px;overflow:hidden;font-size:13px;">
				<thead>
					<tr>
						<th>Quando</th>
						<th>Quem</th>
						<th>Ação</th>
						<th>Popup</th>
						<th>Detalhe</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $entries as $e ) :
					$action = (string) ( $e['action'] ?? '' );
					$meta   = json_decode( (string) ( $e['meta'] ?? '' ), true );
					if ( ! is_array( $meta ) ) { $meta = []; }
					?>
					<tr>
						<td><?php echo esc_html( wp_date( 'd/m/Y H:i', strtotime( (string) ( $e['created_at'] ?? 'now' ) ) ) ); ?></td>
						<td>
							<strong><?php echo esc_html( $e['user_login'] ?? '#' . (int) ( $e['user_id'] ?? 0 ) ); ?></strong>
							<small style="display:block;color:var(--ml-muted)">#<?php echo (int) ( $e['user_id'] ?? 0 ); ?></small>
						</td>
						<td><?php echo esc_html( $action_labels[ $action ] ?? $action ); ?></td>
						<td>
							<?php if ( ! empty( $e['popup_id'] ) ) : ?>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popup-edit&popup_id=' . (int) $e['popup_id'] ) ); ?>">#<?php echo (int) $e['popup_id']; ?></a>
								<?php if ( ! empty( $meta['name'] ) ) : ?>
									<small style="display:block;color:var(--ml-muted)"><?php echo esc_html( $meta['name'] ); ?></small>
								<?php endif; ?>
							<?php else : ?>
								—
							<?php endif; ?>
						</td>
						<td style="font-family:monospace;font-size:11px;color:var(--ml-muted)">
							<?php
							$bits = [];
							if ( isset( $meta['type'] ) )   { $bits[] = 'type=' . $meta['type']; }
							if ( isset( $meta['status'] ) ) { $bits[] = 'status=' . $meta['status']; }
							echo esc_html( implode( ' · ', $bits ) );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</article>
</div>