<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
/**
 * @var array       $popup
 * @var bool        $is_new
 * @var array       $templates
 * @var array       $toast
 * @var array       $popup_stats
 */
$id            = (int) ( $popup['id'] ?? 0 );
$design        = is_array( $popup['design'] ?? null )        ? $popup['design']        : [];
$triggers      = is_array( $popup['triggers'] ?? null )      ? $popup['triggers']      : [];
$rules         = is_array( $popup['rules'] ?? null )         ? $popup['rules']         : [];
$storage_cfg   = is_array( $popup['storage_cfg'] ?? null )   ? $popup['storage_cfg']   : [];
$goal_selectors = is_array( $popup['goal_selectors'] ?? null ) ? $popup['goal_selectors'] : [];
$variant_group_id = (int) ( $popup['variant_group_id'] ?? 0 );
$variant_label    = (string) ( $popup['variant_label'] ?? '' );
$variant_split    = (int) ( $popup['variant_split'] ?? 100 );

$type_labels = [ 'center_modal'=>'Modal Central','bottom_bar'=>'Barra Inferior','slide_in'=>'Slide-in','fullscreen_overlay'=>'Fullscreen','floating_box'=>'Caixa Flutuante' ];

function mlppe_v( array $arr, string $key, $default = '' ) {
	return $arr[ $key ] ?? $default;
}
function mlppe_sel( $cur, $val ) { return $cur == $val ? 'selected' : ''; }
function mlppe_chk( $val ) { return ! empty( $val ) && $val !== '0' ? 'checked' : ''; }
function mlppe_datetime_local_value( $value ): string {
	$value = trim( (string) $value );
	if ( '' === $value ) return '';
	$value = str_replace( ' ', 'T', $value );
	return preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $value ) ? substr( $value, 0, 16 ) : '';
}

$image_url = esc_url( mlppe_v( $popup, 'image_url' ) );
$image_id  = (int) mlppe_v( $popup, 'image_attachment_id', 0 );
?>
<div class="mlpp-wrap mlpp-admin-wrap" data-toast-message="<?php echo esc_attr( $toast['message'] ); ?>" data-toast-type="<?php echo esc_attr( $toast['type'] ); ?>">
<div id="mlpp-toast-area" aria-live="polite" aria-atomic="true"></div>
	<?php $this->render_admin_nav(); ?>

<!-- HERO -->
<section class="mlpp-hero">
	<div class="mlpp-hero-brand">
		<div class="mlpp-hero-mark"><img src="<?php echo esc_url( MLPP_PLUGIN_URL . 'admin/assets/logo.png' ); ?>" alt="ML Lopes Design" width="72" height="72" style="width:72px!important;height:72px!important;max-width:72px!important;max-height:72px!important;object-fit:contain!important;display:block!important;"></div>
		<div class="mlpp-hero-copy">
			<span class="mlpp-hero-eyebrow">ML Popup Pro › <?php echo $is_new ? 'Novo Popup' : 'Editar Popup'; ?></span>
			<h1><?php echo $is_new ? 'Criar Novo Popup' : esc_html( mlppe_v( $popup, 'name', 'Editar Popup' ) ); ?></h1>
			<p class="mlpp-hero-intro">Configure conteúdo, design, imagem, gatilhos, regras e armazenamento.</p>
		</div>
	</div>
	<div class="mlpp-hero-meta">
		<?php if ( $id ) : ?>
			<span class="mlpp-badge">ID #<?php echo esc_html( $id ); ?></span>
		<?php endif; ?>
		<div class="mlpp-hero-tags">
			<?php
			$status = mlppe_v( $popup, 'status', 'draft' );
			$sl = [ 'active'=>'Ativo','paused'=>'Pausado','draft'=>'Rascunho' ];
			echo '<span class="mlpp-chip">' . esc_html( $sl[ $status ] ?? $status ) . '</span>';
			if ( ! empty( $popup['popup_type'] ) ) {
				echo '<span class="mlpp-chip">' . esc_html( $type_labels[ $popup['popup_type'] ] ?? $popup['popup_type'] ) . '</span>';
			}
			?>
		</div>
	</div>
</section>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
<?php wp_nonce_field( 'mlpp_save_popup' ); ?>
<input type="hidden" name="action" value="mlpp_save_popup">
<input type="hidden" name="mlpp_popup[id]" value="<?php echo esc_attr( $id ); ?>">

<div class="mlpp-layout-edit">

<!-- MAIN COLUMN -->
<div class="mlpp-main-column">

	<!-- TABS NAV -->
	<div class="mlpp-tabs-container">
		<nav class="mlpp-tabs" aria-label="Seções">
			<button type="button" class="mlpp-tab-btn is-active" data-tab="mlpp-tab-content">📝 Conteúdo</button>
			<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-image">🖼 Imagem</button>
			<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-design">🎨 Design</button>
			<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-triggers">⚡ Gatilhos</button>
			<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-rules">📍 Regras</button>
			<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-cookies">🍪 Cookies / Frequência</button>
			<button type="button" class="mlpp-tab-btn" data-tab="mlpp-tab-analytics">📊 Analytics</button>
		</nav>

		<!-- TAB: CONTEÚDO -->
		<section id="mlpp-tab-content" class="mlpp-tab-panel is-active mlpp-card">
			<div class="mlpp-card-head"><div><h2>Conteúdo</h2><p>Texto, botões e HTML do popup.</p></div></div>

			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label>Nome interno <span style="color:#d92d20">*</span></label>
					<input type="text" name="mlpp_popup[name]" value="<?php echo esc_attr( mlppe_v( $popup, 'name' ) ); ?>" required placeholder="Ex: Popup Newsletter Junho">
					<p class="description">Usado apenas no admin, não aparece no site.</p>
				</div>
				<div class="mlpp-field">
					<label>Tipo de popup</label>
					<select name="mlpp_popup[popup_type]">
						<?php foreach ( $type_labels as $val => $lbl ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>" <?php echo mlppe_sel( mlppe_v( $popup, 'popup_type', 'center_modal' ), $val ); ?>><?php echo esc_html( $lbl ); ?></option>
						<?php endforeach; ?>
					</select>
					<p class="description">Para um banner pequeno nos cantos da tela, escolha Caixa Flutuante e ajuste largura e posição na aba Design.</p>
				</div>
			</div>

			<div class="mlpp-field" style="margin-top:14px">
				<label>Título público</label>
				<input type="text" id="mlpp_title" name="mlpp_popup[title]" value="<?php echo esc_attr( mlppe_v( $popup, 'title' ) ); ?>" placeholder="Título que o visitante verá">
			</div>
			<div class="mlpp-field">
				<label>Subtítulo</label>
				<input type="text" id="mlpp_subtitle" name="mlpp_popup[subtitle]" value="<?php echo esc_attr( mlppe_v( $popup, 'subtitle' ) ); ?>" placeholder="Linha complementar ao título">
			</div>

			<div class="mlpp-field">
				<label>Corpo (HTML permitido)</label>
				<?php
				wp_editor(
					wp_kses_post( mlppe_v( $popup, 'body' ) ),
					'mlpp_popup_body',
					[
						'textarea_name' => 'mlpp_popup[body]',
						'textarea_rows' => 8,
						'media_buttons' => true,
						'teeny'         => false,
					]
				);
				?>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Botões</p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label>Texto — Botão Primário</label>
					<input type="text" id="mlpp_btn_primary_text" name="mlpp_popup[btn_primary_text]" value="<?php echo esc_attr( mlppe_v( $popup, 'btn_primary_text' ) ); ?>" placeholder="Ex: Quero assinar">
				</div>
				<div class="mlpp-field">
					<label>URL — Botão Primário</label>
					<input type="url" name="mlpp_popup[btn_primary_url]" value="<?php echo esc_attr( mlppe_v( $popup, 'btn_primary_url' ) ); ?>" placeholder="https://">
				</div>
				<div class="mlpp-field">
					<label>Texto — Botão Secundário</label>
					<input type="text" name="mlpp_popup[btn_secondary_text]" value="<?php echo esc_attr( mlppe_v( $popup, 'btn_secondary_text' ) ); ?>" placeholder="Ex: Não, obrigado">
				</div>
				<div class="mlpp-field">
					<label>URL — Botão Secundário</label>
					<input type="url" name="mlpp_popup[btn_secondary_url]" value="<?php echo esc_attr( mlppe_v( $popup, 'btn_secondary_url' ) ); ?>" placeholder="https:// (opcional)">
				</div>
			</div>

			<?php if ( $id ) : ?>
			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Shortcodes</p>
			<div class="mlpp-shortcode-box">
				<code>[ml_popup id="<?php echo esc_html( $id ); ?>"]</code>
				<button type="button" class="mlpp-btn-secondary mlpp-btn-sm" data-copy='[ml_popup id="<?php echo esc_attr( $id ); ?>"]'>Copiar</button>
			</div>
			<div class="mlpp-shortcode-box" style="margin-top:8px">
				<code>[ml_popup_button id="<?php echo esc_html( $id ); ?>" text="Abrir"]</code>
				<button type="button" class="mlpp-btn-secondary mlpp-btn-sm" data-copy='[ml_popup_button id="<?php echo esc_attr( $id ); ?>" text="Abrir"]'>Copiar</button>
			</div>
			<?php endif; ?>
		</section>

		<!-- TAB: IMAGEM -->
		<section id="mlpp-tab-image" class="mlpp-tab-panel mlpp-card" hidden>
			<div class="mlpp-card-head"><div><h2>Imagem</h2><p>Selecione da Biblioteca de Mídia do WordPress.</p></div></div>

			<div class="mlpp-media-zone-wrap" id="mlpp-image-zone">
				<div class="mlpp-media-preview <?php echo $image_url ? 'has-image' : ''; ?>">
					<img src="<?php echo $image_url ?: ''; ?>" alt="">
				</div>
				<div class="mlpp-media-placeholder" <?php echo $image_url ? 'style="display:none"' : ''; ?>>
					<svg width="48" height="48" fill="none" stroke="#155e6f" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
					<p>Nenhuma imagem selecionada</p>
				</div>
				<div class="mlpp-media-actions" style="margin-top:14px">
					<button type="button" class="mlpp-btn mlpp-media-select-btn" data-zone="mlpp-image-zone">📁 Selecionar imagem</button>
					<button type="button" class="mlpp-btn-secondary mlpp-media-remove-btn" <?php echo $image_url ? '' : 'style="display:none"'; ?>>Remover</button>
				</div>
				<input type="hidden" name="mlpp_popup[image_attachment_id]" data-field="attachment_id" value="<?php echo esc_attr( $image_id ); ?>">
				<input type="hidden" name="mlpp_popup[image_url]" data-field="image_url" value="<?php echo esc_attr( mlppe_v( $popup, 'image_url' ) ); ?>">
			</div>

			<div class="mlpp-divider"></div>
			<div class="mlpp-grid-2" style="margin-top:14px">
				<div class="mlpp-field">
					<label>Alt text</label>
					<input type="text" name="mlpp_popup[image_alt]" data-field="image_alt" value="<?php echo esc_attr( mlppe_v( $popup, 'image_alt' ) ); ?>" placeholder="Texto alternativo para acessibilidade">
				</div>
				<div class="mlpp-field">
					<label>Link da imagem</label>
					<input type="url" name="mlpp_popup[image_link_url]" value="<?php echo esc_attr( mlppe_v( $popup, 'image_link_url' ) ); ?>" placeholder="https://">
					<p class="description">Ao informar uma URL, a imagem inteira fica clicável. O botão não é obrigatório.</p>
				</div>
				<div class="mlpp-field">
					<label>Abrir link da imagem</label>
					<select name="mlpp_popup[image_link_target]">
						<option value="_self" <?php echo mlppe_sel( mlppe_v( $popup, 'image_link_target', '_self' ), '_self' ); ?>>Na mesma aba</option>
						<option value="_blank" <?php echo mlppe_sel( mlppe_v( $popup, 'image_link_target' ), '_blank' ); ?>>Em nova aba</option>
					</select>
				</div>
				<div class="mlpp-field">
					<label>Posição da imagem</label>
					<select name="mlpp_popup[image_position]">
						<option value="top" <?php echo mlppe_sel( mlppe_v( $popup, 'image_position', 'top' ), 'top' ); ?>>Topo</option>
						<option value="left" <?php echo mlppe_sel( mlppe_v( $popup, 'image_position' ), 'left' ); ?>>Esquerda</option>
						<option value="right" <?php echo mlppe_sel( mlppe_v( $popup, 'image_position' ), 'right' ); ?>>Direita</option>
						<option value="background" <?php echo mlppe_sel( mlppe_v( $popup, 'image_position' ), 'background' ); ?>>Fundo (background)</option>
						<option value="only" <?php echo mlppe_sel( mlppe_v( $popup, 'image_position' ), 'only' ); ?>>Apenas imagem</option>
					</select>
				</div>
				<div class="mlpp-field">
					<label>Ajuste (fit)</label>
					<select name="mlpp_popup[image_fit]">
						<option value="cover" <?php echo mlppe_sel( mlppe_v( $popup, 'image_fit', 'cover' ), 'cover' ); ?>>Cover (preencher)</option>
						<option value="contain" <?php echo mlppe_sel( mlppe_v( $popup, 'image_fit' ), 'contain' ); ?>>Contain (proporcional)</option>
						<option value="original" <?php echo mlppe_sel( mlppe_v( $popup, 'image_fit' ), 'original' ); ?>>Tamanho original</option>
					</select>
				</div>
				<div class="mlpp-field">
					<label>Arredondamento</label>
					<input type="text" name="mlpp_popup[image_radius]" value="<?php echo esc_attr( mlppe_v( $popup, 'image_radius', '8px' ) ); ?>" placeholder="0px, 8px, 50%">
				</div>
			</div>
		</section>

		<!-- TAB: DESIGN -->
		<section id="mlpp-tab-design" class="mlpp-tab-panel mlpp-card" hidden>
			<div class="mlpp-card-head"><div><h2>Design</h2><p>Cores, dimensões, animações e layout.</p></div></div>

			<!-- LIVE PREVIEW -->
			<div id="mlpp-live-preview" style="max-width:420px;margin:0 auto 20px;padding:24px;border-radius:16px;background:#fff;box-shadow:0 12px 40px rgba(0,0,0,.15);text-align:left;border:1px solid #e2e8f0;transition:all .2s">
				<p class="mlpp-pv-title" style="font-size:18px;font-weight:800;margin:0 0 6px">Título do popup</p>
				<p class="mlpp-pv-sub" style="font-size:13px;margin:0 0 14px;opacity:.7"></p>
				<button class="mlpp-pv-btn" type="button" style="padding:10px 20px;border:none;border-radius:999px;color:#fff;font-weight:700;font-size:13px;cursor:default">Botão</button>
			</div>

			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label>Largura</label>
					<input type="text" id="mlpp_design_width" name="mlpp_popup[design][width]" value="<?php echo esc_attr( mlppe_v( $design, 'width', '600px' ) ); ?>" placeholder="600px">
					<p class="description">Aceita px, %, vw ou auto. Para caixa pequena lateral, use entre 280px e 360px.</p>
				</div>
				<div class="mlpp-field">
					<label>Largura máxima</label>
					<input type="text" id="mlpp_design_max_width" name="mlpp_popup[design][max_width]" value="<?php echo esc_attr( mlppe_v( $design, 'max_width', '95vw' ) ); ?>" placeholder="95vw">
					<p class="description">Mantém o pop-up responsivo em telas menores.</p>
				</div>
				<div class="mlpp-field">
					<label>Altura</label>
					<input type="text" id="mlpp_design_height" name="mlpp_popup[design][height]" value="<?php echo esc_attr( mlppe_v( $design, 'height', 'auto' ) ); ?>" placeholder="auto ou 700px">
					<p class="description">Use auto para acompanhar o conteúdo ou defina px, %, vh.</p>
				</div>
				<div class="mlpp-field">
					<label>Altura máxima</label>
					<input type="text" id="mlpp_design_max_height" name="mlpp_popup[design][max_height]" value="<?php echo esc_attr( mlppe_v( $design, 'max_height', '90vh' ) ); ?>" placeholder="90vh">
					<p class="description">Limita a altura na tela. Quando o conteúdo exceder o espaço, ele continua acessível sem exibir barra de rolagem.</p>
				</div>
				<div class="mlpp-field">
					<label>Posição da caixa na tela</label>
					<select name="mlpp_popup[design][screen_position]">
						<option value="bottom_right" <?php echo mlppe_sel( mlppe_v( $design, 'screen_position', 'bottom_right' ), 'bottom_right' ); ?>>Inferior direito</option>
						<option value="bottom_left" <?php echo mlppe_sel( mlppe_v( $design, 'screen_position' ), 'bottom_left' ); ?>>Inferior esquerdo</option>
						<option value="top_right" <?php echo mlppe_sel( mlppe_v( $design, 'screen_position' ), 'top_right' ); ?>>Superior direito</option>
						<option value="top_left" <?php echo mlppe_sel( mlppe_v( $design, 'screen_position' ), 'top_left' ); ?>>Superior esquerdo</option>
					</select>
					<p class="description">Aplica-se aos tipos Caixa Flutuante e Slide-in.</p>
				</div>
				<div class="mlpp-field"><label>Border radius</label><input type="text" id="mlpp_design_border_radius" name="mlpp_popup[design][border_radius]" value="<?php echo esc_attr( mlppe_v( $design, 'border_radius', '16px' ) ); ?>"></div>
				<div class="mlpp-field"><label>Padding</label><input type="text" name="mlpp_popup[design][padding]" value="<?php echo esc_attr( mlppe_v( $design, 'padding', '36px 32px 28px' ) ); ?>"></div>
				<div class="mlpp-field"><label>Alinhamento do texto</label>
					<select name="mlpp_popup[design][text_align]">
						<option value="left" <?php echo mlppe_sel( mlppe_v( $design, 'text_align', 'left' ), 'left' ); ?>>Esquerda</option>
						<option value="center" <?php echo mlppe_sel( mlppe_v( $design, 'text_align' ), 'center' ); ?>>Centro</option>
						<option value="right" <?php echo mlppe_sel( mlppe_v( $design, 'text_align' ), 'right' ); ?>>Direita</option>
					</select>
				</div>
				<div class="mlpp-field"><label>Estilo botão fechar</label>
					<select name="mlpp_popup[design][close_style]">
						<option value="x" <?php echo mlppe_sel( mlppe_v( $design, 'close_style', 'x' ), 'x' ); ?>>× (X)</option>
						<option value="circle" <?php echo mlppe_sel( mlppe_v( $design, 'close_style' ), 'circle' ); ?>>Círculo</option>
						<option value="text" <?php echo mlppe_sel( mlppe_v( $design, 'close_style' ), 'text' ); ?>>Texto "Fechar"</option>
					</select>
				</div>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Cores</p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field"><label>Cor de fundo</label><input type="text" id="mlpp_design_bg_color" class="mlpp-color-picker" name="mlpp_popup[design][bg_color]" value="<?php echo esc_attr( mlppe_v( $design, 'bg_color', '#ffffff' ) ); ?>"></div>
				<div class="mlpp-field">
					<label for="mlpp_design_bg_opacity">Transparência do fundo (%)</label>
					<input type="number" id="mlpp_design_bg_opacity" name="mlpp_popup[design][bg_opacity]" value="<?php echo esc_attr( mlppe_v( $design, 'bg_opacity', 100 ) ); ?>" min="0" max="100" step="1">
					<p class="description">100% deixa o fundo sólido; 0% deixa o fundo totalmente transparente. Texto e imagem não perdem opacidade.</p>
				</div>
				<div class="mlpp-field"><label>Cor do texto</label><input type="text" id="mlpp_design_text_color" class="mlpp-color-picker" name="mlpp_popup[design][text_color]" value="<?php echo esc_attr( mlppe_v( $design, 'text_color', '#102a43' ) ); ?>"></div>
				<div class="mlpp-field"><label>Cor overlay</label><input type="text" name="mlpp_popup[design][overlay_color]" value="<?php echo esc_attr( mlppe_v( $design, 'overlay_color', 'rgba(0,0,0,0.55)' ) ); ?>" placeholder="rgba(0,0,0,0.55)"></div>
				<div class="mlpp-field"><label>Cor — Botão Primário</label><input type="text" id="mlpp_design_btn_color" class="mlpp-color-picker" name="mlpp_popup[design][btn_color]" value="<?php echo esc_attr( mlppe_v( $design, 'btn_color', '#155e6f' ) ); ?>"></div>
				<div class="mlpp-field"><label>Cor texto — Botão Primário</label><input type="text" class="mlpp-color-picker" name="mlpp_popup[design][btn_text_color]" value="<?php echo esc_attr( mlppe_v( $design, 'btn_text_color', '#ffffff' ) ); ?>"></div>
				<div class="mlpp-field"><label>Cor — Botão Secundário</label><input type="text" class="mlpp-color-picker" name="mlpp_popup[design][btn2_color]" value="<?php echo esc_attr( mlppe_v( $design, 'btn2_color', '#64748b' ) ); ?>"></div>
				<div class="mlpp-field"><label>Cor texto — Botão Secundário</label><input type="text" class="mlpp-color-picker" name="mlpp_popup[design][btn2_text_color]" value="<?php echo esc_attr( mlppe_v( $design, 'btn2_text_color', '#ffffff' ) ); ?>"></div>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Animação</p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label>Tipo de animação</label>
					<select name="mlpp_popup[design][animation_type]">
						<option value="fade" <?php echo mlppe_sel( mlppe_v( $design, 'animation_type', 'fade' ), 'fade' ); ?>>Fade</option>
						<option value="slide_down" <?php echo mlppe_sel( mlppe_v( $design, 'animation_type' ), 'slide_down' ); ?>>Slide Down</option>
						<option value="slide_up" <?php echo mlppe_sel( mlppe_v( $design, 'animation_type' ), 'slide_up' ); ?>>Slide Up</option>
						<option value="zoom" <?php echo mlppe_sel( mlppe_v( $design, 'animation_type' ), 'zoom' ); ?>>Zoom</option>
						<option value="none" <?php echo mlppe_sel( mlppe_v( $design, 'animation_type' ), 'none' ); ?>>Nenhuma</option>
					</select>
				</div>
				<div class="mlpp-field">
					<label>Layout mobile</label>
					<select name="mlpp_popup[design][mobile_layout]">
						<option value="responsive" <?php echo mlppe_sel( mlppe_v( $design, 'mobile_layout', 'responsive' ), 'responsive' ); ?>>Responsivo</option>
						<option value="fullscreen" <?php echo mlppe_sel( mlppe_v( $design, 'mobile_layout' ), 'fullscreen' ); ?>>Tela cheia</option>
						<option value="hidden" <?php echo mlppe_sel( mlppe_v( $design, 'mobile_layout' ), 'hidden' ); ?>>Ocultar no mobile</option>
					</select>
				</div>
			</div>
			<div class="mlpp-check-row"><input type="checkbox" id="mlpp_design_animation" name="mlpp_popup[design][animation]" value="1" <?php echo mlppe_chk( mlppe_v( $design, 'animation', '1' ) ); ?>><label for="mlpp_design_animation">Ativar animação de entrada</label></div>
		</section>

		<!-- TAB: GATILHOS -->
		<section id="mlpp-tab-triggers" class="mlpp-tab-panel mlpp-card" hidden>
			<div class="mlpp-card-head"><div><h2>Gatilhos</h2><p>Quando e como o popup é acionado.</p></div></div>
			<div class="mlpp-field">
				<label>Tipo de gatilho</label>
				<select id="mlpp_trigger_type" name="mlpp_popup[triggers][trigger_type]">
					<option value="immediate" <?php echo mlppe_sel( mlppe_v( $triggers, 'trigger_type', 'immediate' ), 'immediate' ); ?>>Exibir imediatamente</option>
					<option value="delay" <?php echo mlppe_sel( mlppe_v( $triggers, 'trigger_type' ), 'delay' ); ?>>Após delay (segundos)</option>
					<option value="scroll" <?php echo mlppe_sel( mlppe_v( $triggers, 'trigger_type' ), 'scroll' ); ?>>Após % de scroll</option>
					<option value="exit_intent" <?php echo mlppe_sel( mlppe_v( $triggers, 'trigger_type' ), 'exit_intent' ); ?>>Exit intent (desktop)</option>
					<option value="pageviews" <?php echo mlppe_sel( mlppe_v( $triggers, 'trigger_type' ), 'pageviews' ); ?>>Após X pageviews</option>
					<option value="selector" <?php echo mlppe_sel( mlppe_v( $triggers, 'trigger_type' ), 'selector' ); ?>>Clique em seletor CSS</option>
					<option value="shortcode" <?php echo mlppe_sel( mlppe_v( $triggers, 'trigger_type' ), 'shortcode' ); ?>>Apenas via shortcode</option>
				</select>
			</div>
			<div id="mlpp-trigger-delay" class="mlpp-trigger-field mlpp-field" style="display:none">
				<label>Delay em segundos</label>
				<input type="number" name="mlpp_popup[triggers][delay_seconds]" value="<?php echo esc_attr( mlppe_v( $triggers, 'delay_seconds', 3 ) ); ?>" min="0" style="max-width:120px">
			</div>
			<div id="mlpp-trigger-scroll" class="mlpp-trigger-field mlpp-field" style="display:none">
				<label>Porcentagem de scroll (%)</label>
				<input type="number" name="mlpp_popup[triggers][scroll_percent]" value="<?php echo esc_attr( mlppe_v( $triggers, 'scroll_percent', 50 ) ); ?>" min="0" max="100" style="max-width:120px">
			</div>
			<div id="mlpp-trigger-selector" class="mlpp-trigger-field mlpp-field" style="display:none">
				<label>Seletor CSS</label>
				<input type="text" name="mlpp_popup[triggers][selector]" value="<?php echo esc_attr( mlppe_v( $triggers, 'selector' ) ); ?>" placeholder=".minha-classe, #meu-id">
			</div>
			<div id="mlpp-trigger-pageviews" class="mlpp-trigger-field mlpp-field" style="display:none">
				<label>Mínimo de pageviews</label>
				<input type="number" name="mlpp_popup[triggers][pageviews]" value="<?php echo esc_attr( mlppe_v( $triggers, 'pageviews', 1 ) ); ?>" min="1" style="max-width:120px">
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Frequência</p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label>Regra de frequência</label>
					<select name="mlpp_popup[triggers][frequency]">
						<option value="once_session" <?php echo mlppe_sel( mlppe_v( $triggers, 'frequency', 'once_session' ), 'once_session' ); ?>>Uma vez por sessão</option>
						<option value="once_visitor" <?php echo mlppe_sel( mlppe_v( $triggers, 'frequency' ), 'once_visitor' ); ?>>Uma vez por visitante</option>
						<option value="every_x_days" <?php echo mlppe_sel( mlppe_v( $triggers, 'frequency' ), 'every_x_days' ); ?>>A cada X dias</option>
						<option value="until_closed" <?php echo mlppe_sel( mlppe_v( $triggers, 'frequency' ), 'until_closed' ); ?>>Até fechar manualmente</option>
						<option value="always" <?php echo mlppe_sel( mlppe_v( $triggers, 'frequency' ), 'always' ); ?>>Sempre (sem restrição)</option>
					</select>
				</div>
				<div class="mlpp-field">
					<label>X dias (para regras baseadas em dias)</label>
					<input type="number" name="mlpp_popup[triggers][frequency_days]" value="<?php echo esc_attr( mlppe_v( $triggers, 'frequency_days', 7 ) ); ?>" min="1" style="max-width:120px">
				</div>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Conversão automática (Goal Tracking)</p>
			<div class="mlpp-field">
				<label>Marcar como conversão ao clicar em (CSS selectors, um por linha)</label>
				<textarea name="mlpp_popup[goal_selectors]" rows="3" placeholder=".single_add_to_cart_button&#10;form.checkout .submit&#10;#comprar-agora"><?php echo esc_textarea( implode( "\n", (array) $goal_selectors ) ); ?></textarea>
				<p class="description">Quando o visitante clicar em qualquer elemento do popup que case um desses seletores CSS, o evento <code>conversion</code> é registrado automaticamente (uma vez por popup). Útil para medir cliques em botões WooCommerce, formulários de captura, links de checkout etc.</p>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">🆔 A/B Testing <span class="mlpp-chip" style="background:#fef3c7;color:#92400e;font-size:10px;margin-left:4px">Pro</span></p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label>Grupo de variante (mesmo número = mesmo teste)</label>
					<input type="number" min="0" step="1" name="mlpp_popup[variant_group_id]" value="<?php echo esc_attr( $variant_group_id ); ?>" placeholder="ex: 1" <?php echo mlpp_is_premium() ? '' : 'disabled style="opacity:.5"'; ?>>
					<p class="description">Crie 2 (ou mais) popups com o mesmo grupo, defina pesos diferentes, e o plugin escolhe UMA variante por visitante via cookie determinístico.</p>
				</div>
				<div class="mlpp-field">
					<label>Rótulo desta variante</label>
					<input type="text" maxlength="50" name="mlpp_popup[variant_label]" value="<?php echo esc_attr( $variant_label ); ?>" placeholder="ex: controle, tratamento-azul" <?php echo mlpp_is_premium() ? '' : 'disabled style="opacity:.5"'; ?>>
				</div>
				<div class="mlpp-field">
					<label>Peso desta variante (0–100, padrão 50)</label>
					<input type="number" min="0" max="100" step="1" name="mlpp_popup[variant_split]" value="<?php echo esc_attr( $variant_split ); ?>" <?php echo mlpp_is_premium() ? '' : 'disabled style="opacity:.5"'; ?>>
					<p class="description">Em um grupo com 2 variantes, <code>50/50</code> divide igualmente. <code>0</code> desativa esta variante.</p>
				</div>
			</div>
		</section>

		<!-- TAB: REGRAS -->
		<section id="mlpp-tab-rules" class="mlpp-tab-panel mlpp-card" hidden>
			<div class="mlpp-card-head"><div><h2>Regras de Exibição</h2><p>Onde, para quem e quando o popup aparece.</p></div></div>

			<div class="mlpp-field">
				<label>Escopo de exibição</label>
				<select id="mlpp_rules_scope" name="mlpp_popup[rules][scope]">
					<option value="entire_site" <?php echo mlppe_sel( mlppe_v( $rules, 'scope', 'entire_site' ), 'entire_site' ); ?>>Site inteiro</option>
					<option value="homepage" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'homepage' ); ?>>Apenas homepage</option>
					<option value="posts_only" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'posts_only' ); ?>>Apenas posts</option>
					<option value="pages_only" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'pages_only' ); ?>>Apenas páginas</option>
					<option value="specific_posts" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'specific_posts' ); ?>>Posts/páginas por ID</option>
					<option value="categories" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'categories' ); ?>>Categorias</option>
					<option value="tags" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'tags' ); ?>>Tags</option>
					<option value="include_urls" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'include_urls' ); ?>>URLs específicas</option>
					<?php if ( function_exists( 'is_product' ) ) : ?>
					<option value="woo_products" <?php echo mlppe_sel( mlppe_v( $rules, 'scope' ), 'woo_products' ); ?>>Produtos WooCommerce</option>
					<?php endif; ?>
				</select>
			</div>

			<div id="mlpp-scope-specific-posts" class="mlpp-scope-field mlpp-field" style="display:none">
				<label>IDs (separados por vírgula)</label>
				<input type="text" name="mlpp_popup[rules][post_ids]" value="<?php echo esc_attr( implode( ',', (array) mlppe_v( $rules, 'post_ids', [] ) ) ); ?>" placeholder="123, 456, 789">
			</div>
			<div id="mlpp-scope-categories" class="mlpp-scope-field mlpp-field" style="display:none">
				<label>IDs de categorias</label>
				<input type="text" name="mlpp_popup[rules][categories]" value="<?php echo esc_attr( implode( ',', (array) mlppe_v( $rules, 'categories', [] ) ) ); ?>">
			</div>
			<div id="mlpp-scope-tags" class="mlpp-scope-field mlpp-field" style="display:none">
				<label>IDs de tags</label>
				<input type="text" name="mlpp_popup[rules][tags]" value="<?php echo esc_attr( implode( ',', (array) mlppe_v( $rules, 'tags', [] ) ) ); ?>">
			</div>
			<div id="mlpp-scope-include-urls" class="mlpp-scope-field mlpp-field" style="display:none">
				<label>URLs a incluir (uma por linha, aceita *)</label>
				<textarea name="mlpp_popup[rules][include_urls]"><?php echo esc_textarea( mlppe_v( $rules, 'include_urls' ) ); ?></textarea>
			</div>

			<div class="mlpp-field">
				<label>URLs a excluir (uma por linha, aceita *)</label>
				<textarea name="mlpp_popup[rules][exclude_urls]"><?php echo esc_textarea( mlppe_v( $rules, 'exclude_urls' ) ); ?></textarea>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Dispositivos</p>
			<div style="display:flex;gap:16px;flex-wrap:wrap">
				<?php foreach ( [ 'desktop'=>'🖥 Desktop','tablet'=>'📱 Tablet','mobile'=>'📲 Mobile' ] as $dv=>$dl ) :
					$checked = empty( $rules['devices'] ) || in_array( $dv, (array) $rules['devices'], true );
				?>
					<div class="mlpp-check-row"><input type="checkbox" name="mlpp_popup[rules][devices][]" value="<?php echo esc_attr( $dv ); ?>" id="mlpp_dev_<?php echo esc_attr( $dv ); ?>" <?php echo $checked ? 'checked' : ''; ?>><label for="mlpp_dev_<?php echo esc_attr( $dv ); ?>"><?php echo esc_html( $dl ); ?></label></div>
				<?php endforeach; ?>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Usuários</p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label>Tipo de usuário</label>
					<select name="mlpp_popup[rules][user_targeting]">
						<option value="all" <?php echo mlppe_sel( mlppe_v( $rules, 'user_targeting', 'all' ), 'all' ); ?>>Todos</option>
						<option value="guests" <?php echo mlppe_sel( mlppe_v( $rules, 'user_targeting' ), 'guests' ); ?>>Apenas visitantes</option>
						<option value="logged_in" <?php echo mlppe_sel( mlppe_v( $rules, 'user_targeting' ), 'logged_in' ); ?>>Apenas logados</option>
						<option value="roles" <?php echo mlppe_sel( mlppe_v( $rules, 'user_targeting' ), 'roles' ); ?>>Por cargo</option>
					</select>
				</div>
				<div class="mlpp-field">
					<label>Cargos (vírgula)</label>
					<input type="text" name="mlpp_popup[rules][user_roles]" value="<?php echo esc_attr( implode( ',', (array) mlppe_v( $rules, 'user_roles', [] ) ) ); ?>" placeholder="editor, author">
				</div>
			</div>

			<div class="mlpp-divider"></div>
			<p class="mlpp-section-title">Agendamento</p>
			<div class="mlpp-grid-2">
				<div class="mlpp-field">
					<label for="mlpp_rules_start_date">Início do agendamento</label>
					<input type="datetime-local" id="mlpp_rules_start_date" name="mlpp_popup[rules][start_date]" value="<?php echo esc_attr( mlppe_datetime_local_value( mlppe_v( $rules, 'start_date' ) ) ); ?>" lang="pt-BR" step="60">
					<p class="description">Selecione a data e a hora no calendário.</p>
				</div>
				<div class="mlpp-field">
					<label for="mlpp_rules_end_date">Fim do agendamento</label>
					<input type="datetime-local" id="mlpp_rules_end_date" name="mlpp_popup[rules][end_date]" value="<?php echo esc_attr( mlppe_datetime_local_value( mlppe_v( $rules, 'end_date' ) ) ); ?>" lang="pt-BR" step="60">
					<p class="description">Selecione a data e a hora no calendário.</p>
				</div>
				<div class="mlpp-field">
					<label for="mlpp_rules_time_start">Horário diário de início</label>
					<input type="time" id="mlpp_rules_time_start" name="mlpp_popup[rules][time_start]" value="<?php echo esc_attr( mlppe_v( $rules, 'time_start' ) ); ?>" lang="pt-BR" step="60">
				</div>
				<div class="mlpp-field">
					<label for="mlpp_rules_time_end">Horário diário de fim</label>
					<input type="time" id="mlpp_rules_time_end" name="mlpp_popup[rules][time_end]" value="<?php echo esc_attr( mlppe_v( $rules, 'time_end' ) ); ?>" lang="pt-BR" step="60">
				</div>
			</div>
			<div class="mlpp-field">
				<label>Dias da semana</label>
				<div style="display:flex;gap:10px;flex-wrap:wrap">
					<?php foreach ( [ 0=>'Dom',1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb' ] as $dn=>$dl ) :
						$ck = empty( $rules['days_of_week'] ) || in_array( $dn, array_map( 'intval', (array) $rules['days_of_week'] ), true );
					?>
						<div class="mlpp-check-row"><input type="checkbox" name="mlpp_popup[rules][days_of_week][]" value="<?php echo esc_attr( $dn ); ?>" <?php echo $ck ? 'checked' : ''; ?>><label><?php echo esc_html( $dl ); ?></label></div>
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<!-- TAB: COOKIES / FREQUÊNCIA -->
		<section id="mlpp-tab-cookies" class="mlpp-tab-panel mlpp-card" hidden>
			<div class="mlpp-card-head"><div><h2>Cookies / Frequência</h2><p>Controle completo de repetição por visitante.</p></div></div>

			<div class="mlpp-field">
				<label>Método de armazenamento (este popup)</label>
				<select id="mlpp_storage_method" name="mlpp_popup[storage_cfg][storage_method]">
					<option value="" <?php echo mlppe_sel( mlppe_v( $storage_cfg, 'storage_method' ), '' ); ?>>Herdar das configurações globais</option>
					<option value="cookie" <?php echo mlppe_sel( mlppe_v( $storage_cfg, 'storage_method' ), 'cookie' ); ?>>Cookie</option>
					<option value="localStorage" <?php echo mlppe_sel( mlppe_v( $storage_cfg, 'storage_method' ), 'localStorage' ); ?>>localStorage</option>
					<option value="sessionStorage" <?php echo mlppe_sel( mlppe_v( $storage_cfg, 'storage_method' ), 'sessionStorage' ); ?>>sessionStorage</option>
					<option value="none" <?php echo mlppe_sel( mlppe_v( $storage_cfg, 'storage_method' ), 'none' ); ?>>Sem persistência</option>
				</select>
			</div>

			<div class="mlpp-freq-matrix mlpp-storage-dependent">
				<div class="mlpp-freq-row">
					<div class="mlpp-freq-row-label">🚫 Bloquear após visualizar</div>
					<div class="mlpp-freq-row-input">
						<input type="checkbox" name="mlpp_popup[storage_cfg][block_on_seen]" value="1" <?php echo mlppe_chk( mlppe_v( $storage_cfg, 'block_on_seen', '1' ) ); ?>>
						<span>por</span>
						<input type="number" name="mlpp_popup[storage_cfg][seen_expire_days]" value="<?php echo esc_attr( mlppe_v( $storage_cfg, 'seen_expire_days', 30 ) ); ?>" min="0">
						<span>dias</span>
					</div>
					<small style="color:var(--ml-muted);font-size:11px">Chave: mlpp_seen_<?php echo esc_html( $id ?: '{id}' ); ?></small>
				</div>
				<div class="mlpp-freq-row">
					<div class="mlpp-freq-row-label">❌ Bloquear após fechar</div>
					<div class="mlpp-freq-row-input">
						<input type="checkbox" name="mlpp_popup[storage_cfg][block_on_closed]" value="1" <?php echo mlppe_chk( mlppe_v( $storage_cfg, 'block_on_closed', '1' ) ); ?>>
						<span>por</span>
						<input type="number" name="mlpp_popup[storage_cfg][closed_expire_days]" value="<?php echo esc_attr( mlppe_v( $storage_cfg, 'closed_expire_days', 30 ) ); ?>" min="0">
						<span>dias</span>
					</div>
					<small style="color:var(--ml-muted);font-size:11px">Chave: mlpp_closed_<?php echo esc_html( $id ?: '{id}' ); ?></small>
				</div>
				<div class="mlpp-freq-row">
					<div class="mlpp-freq-row-label">👆 Bloquear após clicar (primário)</div>
					<div class="mlpp-freq-row-input">
						<input type="checkbox" name="mlpp_popup[storage_cfg][block_on_primary_click]" value="1" <?php echo mlppe_chk( mlppe_v( $storage_cfg, 'block_on_primary_click', '1' ) ); ?>>
						<span>por</span>
						<input type="number" name="mlpp_popup[storage_cfg][click_expire_days]" value="<?php echo esc_attr( mlppe_v( $storage_cfg, 'click_expire_days', 30 ) ); ?>" min="0">
						<span>dias</span>
					</div>
					<small style="color:var(--ml-muted);font-size:11px">Chave: mlpp_primary_clicked_<?php echo esc_html( $id ?: '{id}' ); ?></small>
				</div>
				<div class="mlpp-freq-row">
					<div class="mlpp-freq-row-label">👆 Bloquear após clicar (secundário)</div>
					<div class="mlpp-freq-row-input">
						<input type="checkbox" name="mlpp_popup[storage_cfg][block_on_secondary_click]" value="1" <?php echo mlppe_chk( mlppe_v( $storage_cfg, 'block_on_secondary_click' ) ); ?>>
					</div>
					<small style="color:var(--ml-muted);font-size:11px">Chave: mlpp_secondary_clicked_<?php echo esc_html( $id ?: '{id}' ); ?></small>
				</div>
				<div class="mlpp-freq-row">
					<div class="mlpp-freq-row-label">✅ Bloquear após conversão</div>
					<div class="mlpp-freq-row-input">
						<input type="checkbox" name="mlpp_popup[storage_cfg][block_on_converted]" value="1" <?php echo mlppe_chk( mlppe_v( $storage_cfg, 'block_on_converted' ) ); ?>>
					</div>
					<small style="color:var(--ml-muted);font-size:11px">Chave: mlpp_converted_<?php echo esc_html( $id ?: '{id}' ); ?></small>
				</div>
				<div class="mlpp-freq-row">
					<div class="mlpp-freq-row-label">👁 Limite de impressões por visitante</div>
					<div class="mlpp-freq-row-input">
						<input type="number" name="mlpp_popup[storage_cfg][max_impressions]" value="<?php echo esc_attr( mlppe_v( $storage_cfg, 'max_impressions', 0 ) ); ?>" min="0">
						<span>impressões (0 = ilimitado)</span>
					</div>
					<small style="color:var(--ml-muted);font-size:11px">Chave: mlpp_views_<?php echo esc_html( $id ?: '{id}' ); ?></small>
				</div>
			</div>

			<div class="mlpp-note" style="margin-top:16px">
				Nenhum dado pessoal é armazenado. As chaves identificam apenas o popup, não o usuário. Nenhum IP, fingerprint ou dado de identificação é usado.
			</div>
		</section>

		<!-- TAB: ANALYTICS -->
		<section id="mlpp-tab-analytics" class="mlpp-tab-panel mlpp-card" hidden>
			<div class="mlpp-card-head">
				<div><h2>Analytics</h2><p>Estatísticas locais deste popup.</p></div>
				<?php if ( $id ) : ?>
				<button type="button" class="mlpp-btn-danger mlpp-btn-sm" id="mlpp-clear-stats-btn" data-confirm="Apagar todas as estatísticas deste popup?" data-popup-id="<?php echo esc_attr( $id ); ?>">🗑 Limpar stats</button>
				<?php endif; ?>
			</div>
			<?php if ( ! $id ) : ?>
				<div class="mlpp-empty"><div class="mlpp-empty-icon">📊</div><p>Salve o popup primeiro para ver as estatísticas.</p></div>
			<?php else :
				$event_labels = [ 'impression'=>'Impressões','open'=>'Aberturas','close'=>'Fechamentos','primary_click'=>'Cliques primários','secondary_click'=>'Cliques secundários','image_click'=>'Cliques na imagem','conversion'=>'Conversões' ];
				$total_impressions = (int)( $popup_stats['impression'] ?? 0 );
				$total_clicks = (int)( ( $popup_stats['primary_click'] ?? 0 ) + ( $popup_stats['secondary_click'] ?? 0 ) + ( $popup_stats['image_click'] ?? 0 ) );
				$ctr = $total_impressions > 0 ? round( $total_clicks / $total_impressions * 100, 1 ) : 0;
			?>
			<div class="mlpp-stat-grid" style="grid-template-columns:repeat(3,1fr)">
				<div class="mlpp-stat-card">
					<span class="mlpp-stat-label">Impressões</span>
					<span class="mlpp-stat-value"><?php echo esc_html( number_format_i18n( $total_impressions ) ); ?></span>
				</div>
				<div class="mlpp-stat-card">
					<span class="mlpp-stat-label">Cliques totais</span>
					<span class="mlpp-stat-value"><?php echo esc_html( number_format_i18n( $total_clicks ) ); ?></span>
				</div>
				<div class="mlpp-stat-card">
					<span class="mlpp-stat-label">Taxa de clique</span>
					<span class="mlpp-stat-value"><?php echo esc_html( $ctr ); ?>%</span>
				</div>
			</div>
			<table class="mlpp-table" style="margin-top:8px">
				<thead><tr><th>Evento</th><th>Quantidade</th></tr></thead>
				<tbody>
					<?php foreach ( $event_labels as $ev=>$lbl ) : ?>
					<tr><td><?php echo esc_html( $lbl ); ?></td><td><strong><?php echo esc_html( number_format_i18n( $popup_stats[$ev] ?? 0 ) ); ?></strong></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif; ?>
		</section>
	</div><!-- /tabs-container -->

</div><!-- /main column -->

<!-- SIDEBAR -->
<div class="mlpp-sidebar">

	<!-- Publicação -->
	<div class="mlpp-sidebar-panel">
		<div class="mlpp-sidebar-panel-head">📤 Publicação</div>
		<div class="mlpp-sidebar-panel-body">
			<div class="mlpp-field">
				<label>Status</label>
				<select name="mlpp_popup[status]">
					<option value="active" <?php echo mlppe_sel( mlppe_v( $popup, 'status', 'draft' ), 'active' ); ?>>✅ Ativo</option>
					<option value="paused" <?php echo mlppe_sel( mlppe_v( $popup, 'status' ), 'paused' ); ?>>⏸ Pausado</option>
					<option value="draft" <?php echo mlppe_sel( mlppe_v( $popup, 'status' ), 'draft' ); ?>>📝 Rascunho</option>
				</select>
			</div>
			<div class="mlpp-field">
				<label>Prioridade</label>
				<input type="number" name="mlpp_popup[priority]" value="<?php echo esc_attr( mlppe_v( $popup, 'priority', 10 ) ); ?>" min="0" max="999">
				<p class="description">Maior número = maior prioridade quando há conflito.</p>
			</div>
			<div class="mlpp-actions" style="margin-top:14px">
				<button type="submit" class="mlpp-btn mlpp-btn-lg" style="width:100%;justify-content:center">💾 Salvar popup</button>
			</div>
			<div class="mlpp-actions" style="margin-top:8px">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-popups' ) ); ?>" class="mlpp-btn-ghost" style="width:100%;justify-content:center">← Voltar à lista</a>
			</div>
		</div>
	</div>

	<!-- Informações -->
	<?php if ( $id ) : ?>
	<div class="mlpp-sidebar-panel">
		<div class="mlpp-sidebar-panel-head">ℹ️ Informações</div>
		<div class="mlpp-sidebar-panel-body">
			<div class="mlpp-publish-row"><strong>ID</strong><span>#<?php echo esc_html( $id ); ?></span></div>
			<div class="mlpp-publish-row"><strong>Tipo</strong><span><?php echo esc_html( $type_labels[ mlppe_v( $popup, 'popup_type', 'center_modal' ) ] ?? '—' ); ?></span></div>
			<div class="mlpp-publish-row"><strong>Prioridade</strong><span><?php echo esc_html( mlppe_v( $popup, 'priority', 10 ) ); ?></span></div>
			<div class="mlpp-publish-row"><strong>Impressões</strong><span><?php echo esc_html( number_format_i18n( $popup_stats['impression'] ?? 0 ) ); ?></span></div>
			<div class="mlpp-publish-row"><strong>Criado</strong><span style="font-size:11px"><?php echo esc_html( mlppe_v( $popup, 'created_at', '—' ) ); ?></span></div>
		</div>
	</div>
	<?php endif; ?>

	<!-- Template -->
	<div class="mlpp-sidebar-panel">
		<div class="mlpp-sidebar-panel-head">🎨 Template base</div>
		<div class="mlpp-sidebar-panel-body">
			<div class="mlpp-field">
				<select name="mlpp_popup[template_id]" id="mlpp-template-select">
					<option value="">— Nenhum —</option>
					<?php foreach ( $templates as $tpl ) : ?>
						<option value="<?php echo esc_attr( $tpl['id'] ); ?>" <?php echo mlppe_sel( mlppe_v( $popup, 'template_id' ), $tpl['id'] ); ?>>
							<?php echo esc_html( ( $tpl['icon'] ?? '' ) . ' ' . $tpl['label'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mlpp-templates' ) ); ?>" class="mlpp-btn-ghost mlpp-btn-sm" style="display:block;text-align:center;margin-top:8px">Ver todos os templates →</a>
		</div>
	</div>

</div><!-- /sidebar -->
</div><!-- /layout-edit -->
</form>

<?php if ( $id ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var clearBtn = document.getElementById('mlpp-clear-stats-btn');
  if (!clearBtn) return;
  clearBtn.addEventListener('click', function(){
    if (!confirm('Apagar todas as estatísticas deste popup?')) return;
    var fd = new FormData();
    fd.append('action', 'mlpp_clear_stats');
    fd.append('nonce', mlppAdmin.nonce);
    fd.append('popup_id', '<?php echo esc_js( $id ); ?>');
    fetch(mlppAdmin.ajaxUrl, { method:'POST', body:fd })
      .then(function(r){ return r.json(); })
      .then(function(){ window.mlppToast('Estatísticas apagadas.', 'success'); setTimeout(function(){ window.location.reload(); }, 1200); })
      .catch(function(){ window.mlppToast('Erro ao apagar.', 'error'); });
  });
});
</script>
<?php endif; ?>
</div><!-- /wrap -->
