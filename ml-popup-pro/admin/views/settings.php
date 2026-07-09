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
		<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-cfg-io">Import / Export</button>
		<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-cfg-updater">🔄 Atualizações</button>
	</nav>

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
</div>
