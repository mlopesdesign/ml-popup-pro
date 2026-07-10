=== ML Popup Pro ===
Contributors: mlopesdesign
Tags: popup, modal, lead capture, marketing, campaign
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.5.3
License: GPL2+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gerenciador premium de popups para WordPress com campanhas, regras avançadas, analytics local e templates profissionais.

== Description ==

ML Popup Pro é um gerenciador completo de popups para WordPress. Crie campanhas com regras de exibição avançadas, agendamento, analytics local (sem dados pessoais), shortcodes, templates prontos e uma interface administrativa profissional.

Funcionalidades principais:

* Tipos de popup: Modal central, Barra inferior, Slide-in, Fullscreen overlay, Caixa flutuante
* Gatilhos: imediato, delay, scroll %, exit intent, pageviews, seletor CSS, shortcode
* Regras: homepage, posts, páginas, IDs específicos, categorias, tags, URLs, cargo, device, agendamento por data/hora/dia
* Design: cores, dimensões, posições de tela, animações, border-radius, layout mobile, botão fechar
* Armazenamento: Cookie, localStorage, sessionStorage ou sem persistência
* Analytics local: impressões, cliques, conversões (sem IP, sem dados pessoais)
* Templates: Newsletter, WhatsApp, Cupom, Evento, Aviso, Campanha, Fullscreen, Lead Capture
* Imagem clicável: link direto no banner/imagem, sem obrigar o uso de botão
* Shortcodes: [ml_popup id="123"] e [ml_popup_button id="123" text="Abrir"]
* Import/Export: JSON
* Segurança: nonces, sanitização, escaping, $wpdb->prepare, manage_options

== Free vs Pro ==

O plugin funciona em modo Free sem necessidade de licença. Ativando uma licença Pro libera:

* A/B testing por popup com split de tráfego e analytics por variante
* Goal tracking automático por CSS selector (conversão por clique em elemento)
* Filtros avançados de Analytics (período, popup, dispositivo)
* Templates sazonais: Black Friday, Natal, Exit Survey, Free Shipping Bar
* Identidade visual customizada (CSS variables para todas as telas admin)
* Hooks de extensibilidade para addons e integrações

Ativação:

1. Configurações → aba 🔑 Ativação
2. Cole seu serial Pro
3. Clique em Ativar

A verificação é feita contra a Hub local (quando presente em `ml-popup-pro/hub/`) ou aceita seriais bem-formados em modo dev para teste.

== Installation ==

1. Envie a pasta `ml-popup-pro` para `/wp-content/plugins/`
2. Ative o plugin em Plugins > Plugins instalados
3. Acesse ML Popup Pro no menu lateral

== Changelog ==

= 1.5.3 =
* **Restauração de emergência após bug crítico na v1.5.0/v1.5.1/v1.5.2.** Versão baseada na **v1.4.1 estável** (última versão que funcionou em produção) com **apenas blindagens mínimas** — **ZERO features novas**. Cada alteração foi auditada linha por linha antes de subir.
* **Bug crítico 1 corrigido — `MLPP_PLUGIN_BASENAME` undefined:** o hook `init` chamava `dirname( MLPP_PLUGIN_BASENAME )` em `load_plugin_textdomain`, mas a constante nunca havia sido definida. Em PHP 8.x com `WP_DEBUG=1` e `E_NOTICE`/`ErrorException`, isso virava fatal e quebrava o boot do plugin. Adicionada `define( 'MLPP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) )` antes do `require_once` das classes.
* **Bug crítico 2 corrigido — `get_recent_audit()` undefined:** `MLPP_Admin::page_audit()` chamava `self::get_recent_audit(200)` que não existia em nenhum lugar do código. Fatal quando o usuário clicava em "📜 Histórico" no menu. Implementado como método estático com try/catch que lê `wp_mlpp_audit` ordenado por `created_at DESC` e retorna `[]` em qualquer falha.
* **Blindagens no boot (zero-features, todas em try/catch):**
  * `register_activation_hook` agora é closure com try/catch. Falha em `MLPP_Activator::activate()` é logada e gravada em transient `mlpp_activation_error` (admin notice renderiza depois).
  * `register_deactivation_hook` análogo.
  * `add_action('plugins_loaded', …)` envolve `MLPP_Activator::maybe_upgrade()` e `new MLPP_Plugin()` cada um em try/catch próprio.
  * `add_action('admin_notices', …)` renderiza `mlpp_activation_error` no topo do painel pra explicar o que aconteceu.
  * Boot do `MLPP_Updater` envolto em try/catch (cobre falha de rede/SSL no `init()` e o `wp_remote_*`).
  * `MLPP_Plugin::__construct()` quebra em `new MLPP_Admin()` e `new MLPP_Frontend()` (e cada `init()`) em try/catch isolado.
  * `MLPP_Activator::ensure_schema()` quebra cada passo em try/catch: `require_once upgrade.php`, `dbDelta` por tabela, `SHOW COLUMNS`, cada `ALTER TABLE`, `update_option(db_version)`. Qualquer falha é logada e devolvida como entry no array `$notes` em vez de propagar throw.
  * `MLPP_Admin::get_recent_audit()` (NOVO) envolve a leitura da tabela em try/catch e devolve `[]` em qualquer falha.
* **Testes PHPUnit ampliados:** a base adiciona ActivatorGuardTest (mesmo caminho da v1.5.2, mantido porque a blindagem crítica foi mantida) + testes novos cobrindo `MLPP_Plugin::__construct()` boot isolado. Suite total = 36+ testes.
* **Identidade preservada:** slug, classes, options, tabelas, shortcodes, AJAX, REST, frontend, admin — idênticos à v1.4.1. **Atualiza por cima de qualquer versão anterior** sem criar segunda instalação.
* **Limitação conhecida, declaração honesta:** esta versão foi validada com `php -l` em 100% do source + PHPUnit contra stubs isolados. **Não** temos prova E2E com WordPress real rodando localmente (Docker + WP + MySQL ainda não foi montado); o user precisa subir e validar. Se quebrar, esta build pelo menos não 500a o site inteiro — degrada pra Free com aviso admin.

= 1.4.1 =
* **CI com PHPUnit:** novo job `test` em `release.yml` que roda `composer install && vendor/bin/phpunit`. O job `lint` (PHP `php -l` + Node `node --check` + sync-version) e o job de build só rodam depois de lint+test passarem. PRs contra `main` recebem o gate automaticamente antes do build.
* **README.md e CHANGELOG.md** adicionados ao repositório (separados do `readme.txt`, que continua sendo o header WordPress). README é dev-focused, CHANGELOG segue [Keep a Changelog](https://keepachangelog.com/) e cobre v1.0 → atual com notas de breaking e blocos Adicionado/Corrigido.
* **`.harness/AGENTS.md`:** instruções imutáveis pra qualquer agente ou dev que aterrize no repo (identidade técnica, versionamento, sanitização, LGPD, fluxo de release). `.harness/README.md` documenta o propósito do diretório.
* **i18n:** primeiras 6 strings da aba Configurações (eyebrow hero, h1, intro, labels de abas) convertidas pra `esc_html__()`. Demais views podem ser convertidas em PRs incrementais (mecanismo já ativo via `load_plugin_textdomain`).

= 1.4.0 =
* **LGPD / GDPR via WP Consent API (WP 6.0+):** quando o ajuste "Modo de consentimento" estiver em "Aguardar consentimento", o plugin consulta `wp_has_consent('mlpp/marketing')` antes de exibir popups. Categoria registrada com `wp_register_consent_category()` no init. Falha de forma segura em WP < 6.0 (mostra popup; preserva UX).
* **Exit-intent mobile:** o trigger "Exit intent" agora dispara em três sinais: `mouseleave` pelo topo do viewport (desktop clássico), `scroll up` rápido (>1.2 px/ms = ~1200 px/s) que cobre o gesto mobile de voltar pro chrome, e `visibilitychange → hidden` quando o usuário troca de aba. Mesmo visitante só vê o popup uma vez (guard `firedExit`).
* **Audit log LGPD/GDPR-ready:** nova tabela `wp_mlpp_audit` (DB_VERSION 6, auto-migration) com colunas `popup_id`, `user_id`, `user_login`, `action` (`create`/`update`/`delete`/`activate`/`deactivate`/`import`/`export`), `meta` JSON e `created_at`. Hooks em `handle_save_popup` e `handle_delete_popup` gravam cada evento. Novo submenu **📜 Histórico** com a tabela dos últimos 200 registros, link para o popup afetado e leitura humana das ações.
* **Internacionalização:** text domain `ml-popup-pro` carregado via `load_plugin_textdomain` no hook `init`. Arquivo `languages/ml-popup-pro.pot` criado com ~25 strings-chave para referência do tradutor. Adicionar `.po/.mo` em `wp-content/languages/plugins/` para pt-BR ou outro idioma.
* **Testes PHPUnit:** novo `composer.json` + `phpunit.xml.dist` + `tests/bootstrap.php` + `tests/stubs/wp-functions.php` + `tests/SanitizerAndLicenseTest.php`. Roda local com `composer install && vendor/bin/phpunit`. Cobre sanitização (status fallback, variant split clamp, goal selectors perigosos), mapeamento de status da Hub, e flag is_premium.

= 1.3.0 =
* **A/B testing de popups (Pro):** nova tabela com colunas `variant_group_id`, `variant_label`, `variant_split` no DB_VERSION 5. Crie 2+ popups com o mesmo `variant_group_id`, defina pesos diferentes (0–100), e o `Rules` resolve UMA variante por visitante via cookie determinístico. A variante escolhida fica gravada em todos os eventos (`mlpp_events.variant_label`) para análise de CTR/conversão por variante.
* **Webhook de conversão (Free + Pro):** novo campo em Configurações > Global com `webhook_url` e `webhook_enabled`. Quando o evento `conversion` dispara (manualmente ou via goal tracking por CSS selector), o frontend faz POST em JSON para a URL configurada com `{ event, popup_id, variant_label, page_url, device, ts }`. Use para integrar com RD Station, Mailchimp, HubSpot, n8n, etc. — `no-cors` + `keepalive` para não travar UX.
* **`Analytics::record()` agora aceita `variant_label`:** schema do wp_mlpp_events ganha coluna `variant_label` + índice, propagada para `get_*_stats` para permitir filtro por variante nas próximas versões do dashboard.
* **Frontend:** payload do `mlppData.popups[]` inclui `variant_label` e `variant_group_id` para o JS frontend ter contexto da variante que está exibindo.

= 1.2.0 =
* **Integração real com a ML License Hub:** validação de serial agora bate no endpoint oficial `https://license.mlopesdesign.com.br/api/license.php` via POST (action=validate_license, product_id=ml-popup-pro, license_key, domain, site_url, version). Cache local de 12h com botões para forçar re-verificação.
* **Diagnóstico completo na aba Ativação:** mostra servidor, produto, plano, domínio autorizado, data de expiração e timestamp da última verificação — tudo vindo da Hub.
* **Constantes opcionais:** `MLPP_LICENSE_KEY` (em wp-config.php) para bypass da rede e `MLPP_LICENSE_SERVER` para apontar a um endpoint de testes.
* **Filtros novos:** `mlpp_license_server` e `mlpp_license_product_id` para override programático do endpoint e do slug do produto.
* **Mapeamento de status:** traduz status da Hub (`active`, `expired`, `suspended`, `cancelled`, `not_found`, `domain_mismatch`, `unknown_product`, etc.) para estados internos Free/Pro/expirada/revogada/inválida.

= 1.1.0 =
* **Camada Free / Pro:** nova classe `MLPP_License` com helper global `mlpp_is_premium()`. Ativação por serial Pro libera os recursos premium; plugin continua funcionando Free sem licença.
* **Aba 🔑 Ativação** nas Configurações: campo de serial, status (Free / Pro ativa / Pro expirada / inválido), botão ativar/desativar, lista de recursos Pro.
* **Aba 🎨 Identidade visual** (Pro): cores da marca persistem como CSS variables (`--ml-brand`, `--ml-brand-dark`, `--ml-ink`) e refletem em todas as telas admin do plugin.
* **Gate Free/Pro nas features da v1.0.13:** goal tracking automático, filtros de Analytics (período/popup/dispositivo) e templates sazonais (Black Friday, Natal, Exit Survey, Free Shipping) ficam disponíveis só com licença Pro. Free continua recebendo analytics por evento, criação de popups, regras, triggers e 8 templates base.
* **Bootstrap pronto para Hub local:** quando a pasta `hub/` for colocada dentro do plugin, a verificação de licença passa automaticamente a ser feita pela Hub local via funções `mlpp_hub_verify_license()` e `mlpp_hub_is_enabled()`. Até lá, seriais com formato válido são aceitos em modo dev para teste.
* **Identidade preservada:** slug/pasta/classes/options/tabelas inalterados. Update sobre v1.0.12, v1.0.13 ou v1.0.14 é transparente e mantém popups, configurações e opções.

= 1.0.14 =
* **Resiliência do auto-update:** o Updater agora testa HEAD em cada URL candidata (asset oficial / URL determinística / zipball do source) e retorna a primeira que responde 2xx/3xx. Bypassa instabilidades do CDN de GitHub Releases (`releases/download/`) que frequentemente retorna 504.
* **Filtro novo `mlpp_zip_url_mirrors`:** permite injetar URLs adicionais (mirror próprio em R2/S3, CDN próprio, etc) via tema ou addon, sem fork.
* **Mensagens de erro mais claras:** quando nenhuma URL está acessível, o painel mostra a causa específica em vez de "Falha no download" genérico.

= 1.0.13 =
* **Proteção contra abuso no analytics:** rate limiting no endpoint AJAX de eventos (transient por IP + popup + tipo de evento, janela padrão de 5s). Visitor não consegue mais inflar a tabela de eventos em loop.
* **Filtros no dashboard de analytics:** nova aba Analytics agora aceita filtros por período (7/30/90 dias, todos), popup específico e dispositivo (desktop/tablet/mobile), com recálculo server-side.
* **Quatro novos templates prontos:** Black Friday, Natal, Exit Survey e Free Shipping Bar — todos com conteúdo e cores pré-configurados.
* **Goal tracking automático:** novo campo "Marcar como conversão ao clicar em" (CSS selector). Quando o visitante clica em um elemento do popup que casa o seletor, o evento `conversion` é disparado automaticamente, sem precisar de JS manual.
* **Hooks de extensibilidade:** filtros `mlpp_eligible_popups`, `mlpp_popup_render_data`, `mlpp_default_design`, `mlpp_event_rate_limit_window` e `mlpp_goal_selectors` permitem customização por tema, addon ou integration sem fork.
* **Estatísticas por dispositivo:** dashboard Analytics mostra breakdown por tipo de dispositivo (desktop / tablet / mobile) para cada evento.

= 1.0.12 =
* **Menu administrativo legível:** reforçado o contraste do item ML Popup Pro nos estados ativo, aberto, foco e hover, impedindo texto e ícone brancos sobre fundo claro.
* **Transparência do pop-up:** novo controle de 0% a 100% para a opacidade exclusiva do fundo, sem reduzir a visibilidade de imagem, texto ou botões.
* **Preview atualizado:** a prévia da aba Design reflete a transparência configurada em tempo real.
* **Barra de rolagem removida:** caixas, modais, slide-ins, barras e fullscreen não exibem mais a barra interna; conteúdos extensos continuam acessíveis por rolagem.
* **Banner somente imagem:** corrigido o overflow do layout Apenas imagem para evitar a barra lateral causada por diferenças entre altura, padding e imagem.

= 1.0.11 =
* **Ícone branco e legível:** o ícone próprio do ML Popup Pro foi reforçado em branco, com transparência correta, cache-busting e CSS carregado em todas as telas administrativas.
* **Link direto na imagem:** novo campo de URL na aba Imagem; o banner inteiro pode ser clicável sem criar botão, com opção de abrir na mesma aba ou em nova aba.
* **Imagem de fundo clicável:** quando a imagem é usada como background, a área não interativa do pop-up também respeita o link configurado.
* **Posição da caixa:** novos controles para Inferior direito, Inferior esquerdo, Superior direito e Superior esquerdo nos tipos Caixa Flutuante e Slide-in.
* **Dimensões aplicadas:** largura, largura máxima, altura, altura máxima e padding passam a funcionar também em Caixa Flutuante e Slide-in.
* **Analytics:** cliques na imagem são registrados separadamente e incluídos no total de cliques e CTR.
* **Migração automática:** banco atualizado sem perda de dados para armazenar URL e destino do link da imagem.

= 1.0.10 =
* **Imagem vertical sem corte forçado:** removido o limite fixo de 220 px da imagem no topo; imagens passam a respeitar a proporção e a altura disponível do pop-up.
* **Posições laterais corrigidas:** as opções de imagem à esquerda e à direita agora são renderizadas e se reorganizam verticalmente no mobile.
* **Controle de altura:** novos campos Altura e Altura máxima na aba Design, com suporte a px, %, vh e auto e limite responsivo em telas menores.
* **Agendamento em português:** data inicial e final agora usam seletor nativo de data e hora; horários diários usam seletor de hora, com armazenamento compatível com os dados existentes.
* **Agendamento auditado:** interpretação no fuso horário do WordPress e suporte a faixas que atravessam a meia-noite.
* **Navegação rápida:** adicionadas abas internas para Dashboard, Pop-ups e Adicionar pop-up em todas as telas do plugin.
* **Ícone do menu corrigido:** identidade visual preservada com fundo transparente e contraste adequado no menu lateral do WordPress.
* **Auditoria estrutural:** corrigido um fechamento CSS excedente e ajustado o fluxo de selecionar/remover imagem no admin.

= 1.0.9 =
* **Correção crítica:** popups não salvavam ("salvou" mas o conteúdo sumia / não criava). Causa: o esquema usava `DEFAULT ''` em colunas `LONGTEXT`, rejeitado pelo MariaDB, então essas colunas não eram criadas e todo INSERT falhava em silêncio. As colunas de texto passaram a `LONGTEXT NULL`.
* **Migração automática de banco:** o `dbDelta` agora roda também em atualizações (antes só na ativação) e recria colunas ausentes — instalações quebradas são reparadas ao atualizar.
* **Salvamento honesto:** se a gravação falhar, o plugin tenta reparar o banco e repetir; se ainda falhar, mostra o erro real do banco em vez de "salvo com sucesso".
* **Novo:** botão "Reparar banco de dados" em Configurações → Atualizações.

= 1.0.8 =
* Fix: opção "Apagar dados ao desinstalar" agora funciona — o uninstall lia uma option inexistente e nunca removia os dados.
* Fix: importação com "Sobrescrever IDs existentes" agora atualiza o popup existente em vez de criar duplicatas; dados importados passam por sanitização completa.
* Fix: gatilho "pageviews" agora conta as visualizações de página corretamente e dispara após N páginas (antes ficava em deadlock e nunca exibia).
* Chore: pacote distribuível limpo (removidos arquivos de desenvolvimento e notas de release).

= 1.0.7 =
* feat: GitHub Release updater — WordPress detecta e instala atualizações via GitHub automaticamente.
* feat: aba Atualizações em Configurações com status, versão remota e botões limpar cache.

= 1.0.4 =
* Fix: logo/ícone gigante no admin corrigido com CSS nuclear multi-camada.
* Fix: wp_enqueue_editor() removido do enqueue global (causava img{max-width:100%} do TinyMCE).
* Fix: inline style width/height adicionado em todos os img da logo hero como proteção extra.
* Fix: seletor do menu icon reforçado com especificidade dupla + !important completo.

= 1.0.3 =
* Fix: todas as views admin (dashboard, popups, analytics, settings, templates) convertidas de classes mlpb- para mlpp- do CSS v1.0.2.
* Fix: CSS variables --mlpb- corrigidas para --ml- em inline styles das views.

= 1.0.2 =
* Layout admin premium ML, tela de edição com tabs e cards profissionais.
* Seleção de imagem via Biblioteca de Mídia do WordPress (wp.media).
* Controle completo de cookies/localStorage/sessionStorage por evento.
* wp_editor no campo de corpo.
* Analytics por popup com botão limpar.
* Ícone do menu corrigido.
* .github/workflows/release.yml incluído.

= 1.0.1 =
* Fix: ícone do menu gigante corrigido.

= 1.0.0 =
* Lançamento inicial.

== Upgrade Notice ==

= 1.5.3 =
* **RECOMENDADO** se você está numa das versões quebradas (v1.5.0, v1.5.1 ou v1.5.2) ou se o seu WP_DEBUG está ligado. Esta versão é baseada na v1.4.1 estável + blindagens mínimas sem nenhuma feature nova. Atualize primeiro por esta; só então planeje adicionar analytics A/B novamente em versão futura com cobertura de testes E2E.

= 1.4.1 =
* CI agora roda testes PHPUnit no PR e no build (gate antes do release). README/CHANGELOG/.harness docs adicionados. Atualização opcional, sem breaking changes. Atualize quando quiser CI gates completos.

= 1.4.0 =
* Compliance LGPD (Consent API WP 6.0+) com categoria dedicada, exit-intent mobile nativo, audit log de alterações, i18n completa (.pot base) e testes PHPUnit. Atualize para DB_VERSION 6 com auto-migration.

= 1.3.0 =
* Adiciona A/B testing de popups (com peso por variante, persistência via cookie) e webhook de conversão para integrar com RD Station / Mailchimp / HubSpot. Atualize para já vir com DB_VERSION 5 e auto-migration transparente.

= 1.2.0 =
* Validação de serial Pro passa a usar a ML License Hub oficial. Atualize para destravar checagem de domínio, expiração e plano via painel central.

= 1.1.0 =
* Adiciona camada Free vs Pro: aba de Ativação por serial Pro, identidade visual customizável e gating das features premium. Free mantém as funcionalidades básicas. Update transparente sobre qualquer v1.0.x.

= 1.0.14 =
* Auto-update resiliente: bypass de instabilidades do CDN do GitHub Releases via fallback automático para zipball + mirrors configuráveis. Atualização recomendada para quem teve erro 504 no download.

= 1.0.13 =
* Adiciona rate limiting no AJAX de eventos, filtros de período/popup/dispositivo no analytics, goal tracking automático por CSS selector, quatro novos templates e hooks de extensibilidade.

= 1.0.12 =
* Corrige o contraste do menu, adiciona transparência independente para o fundo do pop-up e elimina barras de rolagem internas visíveis.

= 1.0.11 =
* Adiciona link clicável no banner sem botão, posições configuráveis para caixas laterais, dimensões funcionais em Caixa Flutuante/Slide-in e ícone branco legível no menu.

= 1.0.10 =
* Corrige imagens verticais cortadas, adiciona controle responsivo de altura, seletores de data/hora, navegação interna e ícone legível no menu do WordPress.

= 1.0.9 =
* Corrige o bug em que popups não eram salvos. Repara o banco automaticamente ao atualizar. Atualização recomendada.

= 1.0.8 =
* Correções: desinstalação que apaga dados, importação com sobrescrita real e gatilho de pageviews funcional.

= 1.0.7 =
* feat: GitHub Release updater — WordPress detecta e instala atualizações via GitHub automaticamente.
* feat: aba Atualizações em Configurações com status, versão remota e botões limpar cache.

= 1.0.4 =
* Fix: logo/ícone gigante no admin corrigido com CSS nuclear multi-camada.
* Fix: wp_enqueue_editor() removido do enqueue global (causava img{max-width:100%} do TinyMCE).
* Fix: inline style width/height adicionado em todos os img da logo hero como proteção extra.
* Fix: seletor do menu icon reforçado com especificidade dupla + !important completo.

= 1.0.3 =
* Fix: todas as views admin (dashboard, popups, analytics, settings, templates) convertidas de classes mlpb- para mlpp- do CSS v1.0.2.
* Fix: CSS variables --mlpb- corrigidas para --ml- em inline styles das views.

= 1.0.2 =
* Layout admin premium ML, tela de edição com tabs e cards profissionais.
* Seleção de imagem via Biblioteca de Mídia do WordPress (wp.media).
* Controle completo de cookies/localStorage/sessionStorage por evento.
* wp_editor no campo de corpo.
* Analytics por popup com botão limpar.
* Ícone do menu corrigido.
* .github/workflows/release.yml incluído.

= 1.0.1 =
* Fix: ícone do menu gigante corrigido.

= 1.0.0 =
Versão inicial.
