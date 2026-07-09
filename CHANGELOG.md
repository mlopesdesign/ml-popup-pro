# Changelog

Todas as mudanças notáveis neste projeto estão documentadas aqui. O formato segue o [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), e o projeto tenta seguir [Semantic Versioning](https://semver.org/).

## [1.4.0] — LGPD + Exit-Intent Mobile + Audit Log + i18n + Tests

### Adicionado
- **LGPD via WP Consent API (WP 6.0+):** quando `consent_mode = 'wait'`, popup só exibe após `wp_has_consent('mlpp/marketing')`. Categoria registrada com `wp_register_consent_category()` no `init`. Fallback seguro em WP < 6.0.
- **Exit-intent mobile:** três sinais de saída agora disparam o popup: `mouseleave` (desktop), `scroll up` > 1.2 px/ms (mobile), `visibilitychange → hidden` (troca de aba). Guard `firedExit` garante 1 única exibição.
- **Audit log:** nova tabela `wp_mlpp_audit` (DB_VERSION 6) registra quem criou/editou/excluiu/atualizou cada popup. Submenu **📜 Histórico** lista últimos 200 registros com link pro popup.
- **Internacionalização:** `load_plugin_textdomain('ml-popup-pro')` no `init`. `languages/ml-popup-pro.pot` com ~25 strings-chave como base para tradutores.
- **PHPUnit skeleton:** `composer.json` + `phpunit.xml.dist` + `tests/bootstrap.php` + `tests/stubs/wp-functions.php` + `tests/SanitizerAndLicenseTest.php` (11 testes cobrindo sanitização e mapeamento de status da hub).

### Compatibilidade
- Compatible update sobre 1.3.0 e qualquer v1.0.x. Auto-migration transparente (DB_VERSION 5 → 6).

## [1.3.0] — A/B Testing + Webhook de Conversão

### Adicionado
- **A/B testing de popups (Pro):** DB_VERSION 5 com colunas `variant_group_id`, `variant_label`, `variant_split`. `MLPP_Rules::select_variants()` sorteia UMA variante por visitante via cookie determinístico (`crc32('mlpp_visitor_<gid>')`). Ponderação por `variant_split` (0-100).
- **Webhook de conversão:** campo `webhook_url` + `webhook_enabled` em Configurações → Global. Frontend faz POST JSON em `no-cors+keepalive` quando o evento `conversion` dispara. Payload: `{ event, popup_id, variant_label, page_url, device, ts }`.
- **`Analytics::record()`** agora aceita `variant_label` (propagado para `wp_mlpp_events`).

## [1.2.0] — Integração Real com a ML License Hub

### Adicionado
- Validação de serial via POST para `https://license.mlopesdesign.com.br/api/license.php`. Cache 12h + back-off 10min em falha de rede. Mapeamento de 9 status da Hub para 6 status internos.
- Constantes `MLPP_LICENSE_KEY` e `MLPP_LICENSE_SERVER` opcionais no `wp-config.php`.
- Filtros novos: `mlpp_license_server`, `mlpp_license_product_id`.
- Diagnóstico completo na aba Ativação mostra plano, domínio, expiração, timestamp da última checagem.

## [1.1.0] — Camada Free/Pro + Identidade Visual

### Adicionado
- `MLPP_License` + helper global `mlpp_is_premium()`. Plugin funciona Free por padrão.
- Aba **🔑 Ativação** nas Configurações com input de serial, status chip, botões Ativar/Desativar, lista de recursos Pro.
- Aba **🎨 Identidade Visual** (Pro) com CSS variables (`--ml-brand`, `--ml-brand-dark`, `--ml-ink`) inline no admin header.
- Gate Free/Pro nas features premium: templates sazonais, goal tracking, filtros Analytics avançados.
- Auto-bypass via `MLPP_LICENSE_KEY` no wp-config ou Hub local em `ml-popup-pro/hub/`.

## [1.0.14] — Auto-Update Resiliente (Bypass 504 do CDN)

### Corrigido
- Updater agora testa HEAD em cada URL candidata (asset oficial / URL determinística / zipball do source) e retorna a primeira que responde 2xx/3xx. Bypassa instabilidade do CDN de GitHub Releases que frequentemente retorna 504.

## [1.0.13] — Filtros Analytics + Goal Tracking + 4 Templates

### Adicionado
- Rate limiting no AJAX de eventos (transient por IP+popup+tipo, janela padrão 5s).
- Filtros no dashboard Analytics (período, popup, dispositivo) + breakdown por dispositivo.
- 4 templates novos: Black Friday, Natal, Exit Survey, Free Shipping Bar.
- Goal tracking automático (CSS selector → evento `conversion`).
- Hooks de extensibilidade: `mlpp_eligible_popups`, `mlpp_popup_render_data`, `mlpp_templates`.

## [1.0.12]

- Menu administrativo legível (contraste corrigido).
- Controle de Transparência do fundo (0-100%) sem afetar textos/botões.
- Preview da aba Design reflete a transparência em tempo real.
- Barra de rolagem removida nos containers internos.
- Banner somente imagem: overflow corrigido.

## [1.0.0] a [1.0.11]

Consulte `readme.txt` para o changelog completo dessas versões.
