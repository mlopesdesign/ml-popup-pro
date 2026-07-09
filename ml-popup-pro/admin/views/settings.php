<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $settings @var array $toast */
function mlpp_sval( array $settings, string $key, string $default = '' ): string {
	return esc_attr( $settings[ $key ] ?? $default );
}
function mlpp_ssel( array $settings, string $key, string $value ): string {
	return ( $settings[ $key ] ?? '' ) === $value ? 'selected' : '';
}
function mlpp_schk( array $settings, string $key ): string {
	return ! empty( $settings[ $key ] ) && $settings[ $key ] === '1' ? 'checked' : '';
}
?>
<div class="mlpp-wrap mlpp-admin-wrap" data-toast-message="<?php echo esc_attr( $toast['message'] ); ?>" data-toast-type="<?php echo esc_attr( $toast['type'] ); ?>">
	<div id="mlpp-toast-area" aria-live="polite" aria-atomic="true"></div>
	<?php $this->render_admin_nav(); ?>

	<section class="mlpp-hero">
		<div class="mlpp-hero-brand">
			<div class="mlpp-hero-mark"><img src="<?php echo esc_url( MLPP_PLUGIN_URL . 'admin/assets/logo.png' ); ?>" alt="ML Lopes Design" width="72" height="72" style="width:72px!important;height:72px!important;max-width:72px!important;max-height:72px!important;object-fit:contain!important;display:block!important;"></div>
			<div class="mlpp-hero-copy">
				<span class="mlpp-hero-eyebrow">ML Popup Pro · Configurações</span>
				<h1>Configurações</h1>
				<p class="mlpp-hero-intro">Configurações globais do plugin, armazenamento, consentimento, import e export.</p>
			</div>
		</div>
	</section>

	<nav class="mlpp-tabs">
		<button type="button" class="mlpp-tab-btn is-active" data-tab="mlpp-tab-cfg-global">Global</button>
		<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-cfg-brand">🎨 Identidade</button>
		<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-cfg-io">Import / Export</button>
		<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-cfg-updater">🔄 Atualizações</button>
		<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-cfg-activation">🔑 Ativação <span class="mlpp-chip" style="background:#fef3c7;color:#92400e;font-size:10px;margin-left:4px"><?php echo esc_html( $license_status ?? 'Free' ); ?></span></button>
	</nav>

	<style>
	:root{
		--ml-brand: <?php echo esc_html( $brand['ml_brand'] ?? '#155e6f' ); ?>;
		--ml-brand-dark: <?php echo esc_html( $brand['ml_brand_dark'] ?? '#114b5a' ); ?>;
		--ml-ink: <?php echo esc_html( $brand['ml_ink'] ?? '#102a43' ); ?>;
	}
	</style>

	<!-- TAB GLOBAL -->
	<section id="mlpp-tab-cfg-global" class="mlpp-tab-panel is-active">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'mlpp_save_settings' ); ?>
			<input type="hidden" name="action" value="mlpp_save_settings">
			<article class="mlpp-card">
				<div class="mlpp-card-header"><div><h2>Armazenamento &amp; Consentimento</h2></div></div>
				<div class="mlpp-grid-2">
					<div class="mlpp-field"><label>Método de armazenamento padrão</label>
						<select name="mlpp_settings[storage_method]">
							<option value="cookie" <?php echo mlpp_ssel( $settings, 'storage_method', 'cookie' ); ?>>Cookie</option>
							<option value="localStorage" <?php echo mlpp_ssel( $settings, 'storage_method', 'localStorage' ); ?>>localStorage</option>
							<option value="sessionStorage" <?php echo mlpp_ssel( $settings, 'storage_method', 'sessionStorage' ); ?>>sessionStorage</option>
							<option value="none" <?php echo mlpp_ssel( $settings, 'storage_method', 'none' ); ?>>Sem persistência</option>
						</select>
					</div>
					<div class="mlpp-field"><label>Expiração padrão (dias)</label><input type="number" name="mlpp_settings[default_expiration_days]" value="<?php echo mlpp_sval( $settings, 'default_expiration_days', '30' ); ?>" min="0"></div>
					<div class="mlpp-field"><label>Modo de consentimento</label>
						<select name="mlpp_settings[consent_mode]">
							<option value="off" <?php echo mlpp_ssel( $settings, 'consent_mode', 'off' ); ?>>Desativado</option>
							<option value="wait" <?php echo mlpp_ssel( $settings, 'consent_mode', 'wait' ); ?>>Aguardar consentimento</option>
							<option value="functional" <?php echo mlpp_ssel( $settings, 'consent_mode', 'functional' ); ?>>Apenas funcional</option>
						</select>
					</div>
<div class="mlpp-field"><label><input type="checkbox" name="mlpp_settings[allow_multiple_popups]" value="1" <?php echo mlpp_schk( $settings, 'allow_multiple_popups' ); ?>> Permitir múltiplos popups por página</label></div>
				<div class="mlpp-field"><label><input type="checkbox" name="mlpp_settings[disable_analytics]" value="1" <?php echo mlpp_schk( $settings, 'disable_analytics' ); ?>> Desativar analytics local</label></div>
				<div class="mlpp-field"><label><input type="checkbox" name="mlpp_settings[delete_data_on_uninstall]" value="1" <?php echo mlpp_schk( $settings, 'delete_data_on_uninstall' ); ?>> Apagar dados ao desinstalar</label></div>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">🪝 Webhook de conversão</p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field" style="grid-column:1/-1">
					<label>URL do webhook (POST em JSON quando o evento <code>conversion</code> dispara)</label>
					<input type="url" name="mlpp_settings[webhook_url]" value="<?php echo esc_attr( $settings['webhook_url'] ?? '' ); ?>" placeholder="https://seudominio.com/webhooks/ml-popup-pro">
					<p class="description">Recebe um JSON com <code>event</code>, <code>popup_id</code>, <code>variant_label</code>, <code>page_url</code>, <code>device</code> e <code>ts</code>. Use para integrar com RD Station, Mailchimp, HubSpot, etc.</p>
				</div>
				<div class="mlpp-field"><label><input type="checkbox" name="mlpp_settings[webhook_enabled]" value="1" <?php echo mlpp_schk( $settings, 'webhook_enabled' ); ?>> Ativar webhook de conversão</label></div>
			</div>
				<div class="mlpp-actions" style="margin-top:18px;">
					<button type="submit" class="button button-primary mlpp-btn">Salvar configurações</button>
				</div>
			</article>
		</form>
	</section>

	<!-- TAB IMPORT/EXPORT -->
	<section id="mlpp-tab-cfg-io" class="mlpp-tab-panel" hidden>
		<div class="mlpp-grid-2">
			<article class="mlpp-card">
				<div class="mlpp-card-header"><div><h2>Exportar Popups</h2><p class="mlpp-hero-intro">Baixe todos os popups em JSON.</p></div></div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'mlpp_export_popups' ); ?>
					<input type="hidden" name="action" value="mlpp_export_popups">
					<div class="mlpp-actions">
						<button type="submit" class="button button-primary mlpp-btn">⬇ Exportar JSON</button>
					</div>
				</form>
			</article>
			<article class="mlpp-card">
				<div class="mlpp-card-header"><div><h2>Importar Popups</h2><p class="mlpp-hero-intro">Importe popups de um arquivo JSON exportado.</p></div></div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
					<?php wp_nonce_field( 'mlpp_import_popups' ); ?>
					<input type="hidden" name="action" value="mlpp_import_popups">
					<div class="mlpp-grid-2">
						<div class="mlpp-field" style="grid-column:1/-1;"><label>Arquivo JSON</label><input type="file" name="mlpp_import_file" accept=".json" required></div>
						<div class="mlpp-field" style="grid-column:1/-1;"><label><input type="checkbox" name="mlpp_overwrite" value="1"> Sobrescrever IDs existentes</label></div>
					</div>
					<div class="mlpp-actions" style="margin-top:14px;">
						<button type="submit" class="button button-primary mlpp-btn">⬆ Importar JSON</button>
					</div>
				</form>
			</article>
		</div>
	</section>

	<!-- TAB UPDATER -->
	<section id="mlpp-tab-cfg-updater" class="mlpp-tab-panel" hidden>
		<?php
		$st       = $updater_status ?? [];
		$installed    = esc_html( $st['installed']      ?? MLPP_VERSION );
		$remote       = esc_html( $st['remote_version'] ?? '' );
		$status_key   = $st['status'] ?? 'error_api';
		$release_url  = esc_url( $st['release_url']    ?? '' );
		$status_map   = [
			'up_to_date'       => [ '✅', 'Plugin atualizado.',                    'mlpp-alert-success' ],
			'update_available' => [ '🆕', 'Nova versão disponível: ' . $remote,    'mlpp-alert-warning' ],
			'no_asset'         => [ '⚠️', 'Release encontrado mas ZIP não localizado.', 'mlpp-alert-warning' ],
			'error_api'        => [ '❌', 'Erro ao consultar a API do GitHub.', 'mlpp-alert-warning' ],
		];
		$s = $status_map[ $status_key ] ?? [ '❓', 'Status desconhecido.', 'mlpp-alert-warning' ];
		?>
		<div class="mlpp-grid-2">
			<article class="mlpp-card">
				<div class="mlpp-card-head"><div><h2>Status do Updater</h2><p>Verificação via GitHub Release API.</p></div></div>

				<div class="mlpp-grid-2" style="margin-bottom:16px">
					<div class="mlpp-field">
						<label>Versão instalada</label>
						<div style="padding:10px 12px;border:1px solid var(--ml-line);border-radius:10px;font-size:14px;font-weight:700;color:var(--ml-ink);background:#fafcfd"><?php echo $installed; ?></div>
					</div>
					<div class="mlpp-field">
						<label>Última versão GitHub</label>
						<div style="padding:10px 12px;border:1px solid var(--ml-line);border-radius:10px;font-size:14px;font-weight:700;color:var(--ml-ink);background:#fafcfd">
							<?php echo $remote ?: '—'; ?>
							<?php if ( $release_url ) : ?><a href="<?php echo $release_url; ?>" target="_blank" rel="noopener" style="margin-left:8px;font-size:12px">Ver release →</a><?php endif; ?>
						</div>
					</div>
				</div>

				<div class="mlpp-alert <?php echo esc_attr( $s[2] ); ?>" style="margin-bottom:16px">
					<?php echo esc_html( $s[0] . ' ' . $s[1] ); ?>
				</div>

				<div class="mlpp-note" style="margin-bottom:16px">
					Repositório: <a href="https://github.com/mlopesdesign/ml-popup-pro/releases" target="_blank" rel="noopener">github.com/mlopesdesign/ml-popup-pro/releases</a><br>
					Cache: 6 horas — transient <code>mlpp_github_update_cache</code>
				</div>

				<div class="mlpp-actions">
					<button type="button" class="mlpp-btn" id="mlpp-check-update-btn">🔄 Verificar atualização agora</button>
					<button type="button" class="mlpp-btn-secondary" id="mlpp-clear-cache-btn">🗑 Limpar cache</button>
				</div>
				<div id="mlpp-update-result" style="margin-top:14px;font-size:13px"></div>
			</article>

			<article class="mlpp-card">
				<div class="mlpp-card-head"><div><h2>Manutenção do banco</h2><p>Recria colunas ausentes da tabela de popups. Use se um popup não salvar.</p></div></div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:12px">
					<?php wp_nonce_field( 'mlpp_repair_db' ); ?>
					<input type="hidden" name="action" value="mlpp_repair_db">
					<button type="submit" class="mlpp-btn-secondary">🛠 Reparar banco de dados</button>
				</form>
			</article>

			<article class="mlpp-card">
				<div class="mlpp-card-head"><div><h2>Como funciona</h2></div></div>
				<ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.8;color:var(--ml-ink)">
					<li>Consulta <code>api.github.com/repos/mlopesdesign/ml-popup-pro/releases/latest</code></li>
					<li>Compara a tag do release com a versão instalada</li>
					<li>Se houver versão nova, injeta no transient <code>update_plugins</code> do WordPress</li>
					<li>O WordPress exibe a atualização em Painel → Atualizações</li>
					<li>O botão "Atualizar agora" baixa o asset ZIP do release</li>
					<li>Instalação automática sem criar plugin paralelo</li>
					<li>Funciona com repositório público, sem token</li>
				</ul>
			</article>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function(){
			function runUpdate(force) {
				var resultEl = document.getElementById('mlpp-update-result');
				resultEl.textContent = 'Consultando GitHub...';
				var fd = new FormData();
				fd.append('action', 'mlpp_clear_update_cache');
				fd.append('nonce', mlppAdmin.nonce);
				fetch(mlppAdmin.ajaxUrl, {method:'POST',body:fd})
					.then(function(r){return r.json();})
					.then(function(d){
						if (!d.success) { resultEl.textContent = 'Erro na consulta.'; return; }
						var s = d.data;
						var labels = {up_to_date:'✅ Atualizado ('+s.remote_version+')', update_available:'🆕 Atualização disponível: '+s.remote_version, no_asset:'⚠️ ZIP não encontrado no release.', error_api:'❌ Erro ao consultar GitHub.'};
						resultEl.textContent = (labels[s.status] || s.status) + ' — instalado: ' + s.installed;
						window.mlppToast && window.mlppToast('Cache limpo e verificação concluída.', 'success');
					}).catch(function(){ resultEl.textContent = 'Erro de rede.'; });
			}
			var checkBtn = document.getElementById('mlpp-check-update-btn');
			var clearBtn = document.getElementById('mlpp-clear-cache-btn');
			if (checkBtn) checkBtn.addEventListener('click', function(){ runUpdate(true); });
			if (clearBtn) clearBtn.addEventListener('click', function(){ runUpdate(false); });
		});
		</script>
	</section>

	<!-- TAB IDENTIDADE VISUAL -->
	<section id="mlpp-tab-cfg-brand" class="mlpp-tab-panel" hidden>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'mlpp_save_brand' ); ?>
			<input type="hidden" name="action" value="mlpp_save_brand">
			<article class="mlpp-card">
				<div class="mlpp-card-header"><div><h2>🎨 Identidade visual</h2><p class="mlpp-hero-intro">Cores que aparecem em todas as telas administrativas do plugin. <strong>Funcionalidade Pro</strong>.</p></div></div>
				<div class="mlpp-grid-3">
					<div class="mlpp-field">
						<label>Cor primária (brand)</label>
						<input type="text" class="mlpp-color-picker" name="mlpp_brand[ml_brand]" value="<?php echo esc_attr( $brand['ml_brand'] ?? '#155e6f' ); ?>">
					</div>
					<div class="mlpp-field">
						<label>Cor primária (escura)</label>
						<input type="text" class="mlpp-color-picker" name="mlpp_brand[ml_brand_dark]" value="<?php echo esc_attr( $brand['ml_brand_dark'] ?? '#114b5a' ); ?>">
					</div>
					<div class="mlpp-field">
						<label>Cor de texto (ink)</label>
						<input type="text" class="mlpp-color-picker" name="mlpp_brand[ml_ink]" value="<?php echo esc_attr( $brand['ml_ink'] ?? '#102a43' ); ?>">
					</div>
				</div>
				<p class="description">Esses valores viram CSS variables (<code>:root { --ml-brand: … }</code>) no cabeçalho das telas do plugin e persistem até você trocar novamente.</p>
				<div class="mlpp-actions" style="margin-top:14px">
					<button type="submit" class="button button-primary mlpp-btn">Salvar cores</button>
				</div>
			</article>
		</form>
	</section>

	<!-- TAB ATIVAÇÃO -->
	<section id="mlpp-tab-cfg-activation" class="mlpp-tab-panel" hidden>
		<article class="mlpp-card">
			<div class="mlpp-card-header">
				<div>
					<h2>🔑 Ativação</h2>
					<p class="mlpp-hero-intro">Libera os recursos Pro do ML Popup Pro. O plugin funciona Free sem serial; Pro adiciona A/B testing, goal tracking, mais templates e filtros avançados de analytics.</p>
				</div>
				<div>
					<span class="mlpp-chip" style="background:<?php echo mlpp_is_premium() ? '#d1fae5;color:#065f46' : '#fef9c3;color:#854d0e' ?>;font-size:12px;font-weight:700">
						Status: <?php echo esc_html( $license_status ); ?>
					</span>
				</div>
			</div>

			<?php if ( mlpp_is_premium() ) : ?>
				<?php
					$details = is_array( $license_details ) ? $license_details : [];
					$last    = MLPP_License::last_verification();
				?>
				<div class="mlpp-note" style="margin-bottom:16px">
					<strong>Plano:</strong> <?php echo esc_html( $details['plan']    ?? 'Pro' ); ?><br>
					<strong>Dominio autorizado:</strong> <?php echo esc_html( $details['domain'] ?? '—' ); ?><br>
					<strong>Expira em:</strong> <?php echo esc_html( $details['expires_at'] ?? '—' ); ?><br>
					<strong>Servidor:</strong> <code><?php echo esc_html( MLPP_License::server_url() ); ?></code><br>
					<strong>Produto:</strong> <code><?php echo esc_html( MLPP_License::product_id() ); ?></code><br>
					<?php if ( ! empty( $last['checked_at'] ) ) : ?>
						<strong>Ultima verificacao:</strong> <?php echo esc_html( wp_date( 'd/m/Y H:i', (int) $last['checked_at'] ) ); ?>
					<?php endif; ?>
				</div>
				<div class="mlpp-actions" style="display:flex;gap:10px">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
						<?php wp_nonce_field( 'mlpp_activate_license' ); ?>
						<input type="hidden" name="action" value="mlpp_activate_license">
						<input type="hidden" name="mlpp_license_key" value="<?php echo esc_attr( (string) get_option( MLPP_License::OPTION_KEY, '' ) ); ?>">
						<input type="hidden" name="mlpp_force_recheck" value="1">
						<button type="submit" class="mlpp-btn-secondary">🔄 Reverificar agora</button>
					</form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('Desativar a licenca Pro? O plugin volta para Free.');">
						<?php wp_nonce_field( 'mlpp_deactivate_license' ); ?>
						<input type="hidden" name="action" value="mlpp_deactivate_license">
						<button type="submit" class="mlpp-btn-secondary">Desativar licenca</button>
					</form>
				</div>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'mlpp_activate_license' ); ?>
					<input type="hidden" name="action" value="mlpp_activate_license">
					<div class="mlpp-field">
						<label for="mlpp_license_key">Serial Pro</label>
						<input type="text" id="mlpp_license_key" name="mlpp_license_key" value="<?php echo esc_attr( $license_key ); ?>" placeholder="XXXX-XXXX-XXXX-XXXX" autocomplete="off" style="font-family:monospace;letter-spacing:1px">
						<p class="description">Recebeu o serial após a compra. Cole aqui e clique em Ativar.</p>
					</div>
					<div class="mlpp-actions" style="margin-top:14px">
						<button type="submit" class="button button-primary mlpp-btn">Ativar</button>
					</div>
				</form>

				<hr style="margin:24px 0;border:none;border-top:1px solid var(--ml-line)">

				<h3>Recursos Pro</h3>
				<div class="mlpp-grid-2" style="gap:14px">
					<article class="mlpp-stat-card"><strong>🆔 A/B testing</strong><small>Variantes por popup com split de tráfego e CTR por variante</small></article>
					<article class="mlpp-stat-card"><strong>🎯 Goal tracking automático</strong><small>Disparar conversão por CSS selector (WooCommerce, formulários, etc)</small></article>
					<article class="mlpp-stat-card"><strong>📊 Analytics avançado</strong><small>Filtros por período/popup/dispositivo e breakdown por device</small></article>
					<article class="mlpp-stat-card"><strong>🎄 Templates sazonais</strong><small>Black Friday, Natal, Exit Survey, Free Shipping e mais</small></article>
					<article class="mlpp-stat-card"><strong>🛡 Rate limiting</strong><small>Proteção da tabela de eventos contra flood/bots</small></article>
					<article class="mlpp-stat-card"><strong>🪝 Hooks extensibilidade</strong><small>Filters para addons, integrações e customização sem fork</small></article>
				</div>

				<p class="mlpp-note" style="margin-top:18px">
					<strong>Servidor de licença:</strong> <code><?php echo esc_html( MLPP_License::server_url() ); ?></code><br>
					<strong>Produto:</strong> <code><?php echo esc_html( MLPP_License::product_id() ); ?></code><br>
					O serial é validado remotamente contra a Hub central. Cadastre sua chave em
					<a href="<?php echo esc_url( 'https://license.mlopesdesign.com.br/admin/' ); ?>" target="_blank" rel="noopener">license.mlopesdesign.com.br/admin</a>
					antes de colar aqui — ela precisa estar com o produto
					<code><?php echo esc_html( MLPP_License::product_id() ); ?></code>
					cadastrado e o domínio <code><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></code>
					autorizado (ou licença sem domínio fixo).
				</p>
			<?php endif; ?>
		</article>
	</section>
</div>
