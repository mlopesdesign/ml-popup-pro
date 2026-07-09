# AGENTS.md — Instruções para Agentes / Devs no ML Popup Pro

> **Contexto:** Este repositório empacota o plugin WordPress **ML Popup Pro** (slug `ml-popup-pro`). O ZIP final é construído por GitHub Actions em `release.yml` quando uma tag `v*.*.*` é pushed.

## Regras imutáveis (não violar)

1. **Identidade técnica é imutável.** NÃO mude:
   - `slug do plugin` (= `ml-popup-pro`)
   - `pasta raiz` (= `ml-popup-pro/`)
   - `arquivo principal` (= `ml-popup-pro/ml-popup-pro.php`)
   - `nome das classes` (= `MLPP_*` em `includes/class-mlpp-*.php`)
   - `nome das options` (= `mlpp_settings`, `mlpp_db_version`, `mlpp_brand`, `mlpp_license_*`)
   - `nome das tabelas` (= `wp_mlpp_popups`, `wp_mlpp_events`, `wp_mlpp_meta`, `wp_mlpp_audit`)
   - `Update URI` no header (= `https://github.com/mlopesdesign/ml-popup-pro`)
   - `Text Domain` (= `ml-popup-pro`)
   - `PLUGIN_BASENAME` (= `ml-popup-pro/ml-popup-pro.php`)

2. **Versionamento sincronizado em 3 lugares.** Sempre altere juntos:
   - `Version:` no header de `ml-popup-pro.php`
   - `define('MLPP_VERSION', 'X.Y.Z')` na linha 20 de `ml-popup-pro.php`
   - `Stable tag:` no `readme.txt`
   - E adicione a entrada correspondente em **Changelog + Upgrade Notice** do `readme.txt`.
   - Valide com `bash scripts/sync-version.sh` antes de commit.

3. **Schema versioning.** Se tocar em estrutura de tabela:
   - Bump `MLPP_Activator::DB_VERSION`
   - Crie migração no `ensure_schema()` (idempotente)
   - Manifest em `commit`: "DB_VERSION X -> Y"

4. **Auto-update é obrigatório.** Qualquer mudança tem que passar pelo Updater (`includes/class-mlpp-updater.php`) sem quebrar detecção de versão. Teste offline bumpando entre minor/patch.

5. **ZIP é gerado pelo CI.** Não commite um `*.zip` no source. O CI gera `dist/ml-popup-pro-vX.Y.Z.zip` e publica como asset na release.

6. **Sanitize tudo na entrada, escape toda a saída.** Padrão WordPress:
   - Sanitizers: `sanitize_text_field`, `sanitize_key`, `sanitize_hex_color`, `absint`, `esc_url_raw`, `wp_kses_post`
   - Escaping: `esc_html`, `esc_attr`, `esc_url`, `esc_textarea`, `esc_js`, `wp_kses_post`
   - SQL: `$wpdb->prepare()` para queries dinâmicas
   - `current_user_can('manage_options')` em todas as páginas admin
   - `wp_nonce_field()` + `check_admin_referer()` em todos os forms

7. **LGPD/GDPR é não-negociável.** O trigger `consent_mode = 'wait'` no `MLPP_Rules::check_consent()` usa `wp_has_consent('mlpp/marketing')` registrado via `wp_register_consent_category()`. Nunca pular o hook.

## Regras de processo

8. **Sempre deixe o workspace local sincronizado com os assets do GH.** Para cada `git push --tags`, baixe o ZIP final em `B:\PLUGINS MINIMAX CODE\ml-popup-pro\` e valide SHA-256 vs GH. Mantenha `v1.0.3` → mais recente todos presentes.

9. **Não misture com outros plugins.** Cada plugin tem sua própria estrutura isolada no repo dele. Não copie scripts do `ml-link-auditor` sem adaptar 100% (paths, constants, namespaces).

10. **Antes de cada release:**
    - `bash scripts/sync-version.sh` deve passar
    - `cd ml-popup-pro && composer install && vendor/bin/phpunit` deve passar
    - `git status` deve estar limpo
    - Tag NUNCA reutilizada (cada release = tag única)

## Fluxo de release

```
1. Editar header Version + MLPP_VERSION + Stable tag + readme.txt changelog
2. Rodar sync-version.sh (falhará se não bate)
3. composer install && vendor/bin/phpunit (CI também roda)
4. git add -A && git commit -m "..."
5. git tag vX.Y.Z && git push origin main --tags
6. GitHub Actions roda lint → test → build → release
7. Baixar dist/ml-popup-pro-vX.Y.Z.zip no workspace local
8. Validar SHA-256 (deve bater com o asset oficial GH)
```

## Comandos úteis

```bash
# Sync version check
bash scripts/sync-version.sh

# Run PHPUnit
cd ml-popup-pro && composer install && vendor/bin/phpunit

# Syntax check rápido
find ml-popup-pro -name "*.php" -print0 | xargs -0 -n1 php -l
for f in $(find ml-popup-pro -name "*.js"); do node --check "$f"; done

# Download do release após tag
gh release view vX.Y.Z --repo mlopesdesign/ml-popup-pro
gh release download vX.Y.Z --repo mlopesdesign/ml-popup-pro --pattern 'ml-popup-pro-vX.Y.Z.zip' --dir B:/PLUGINS_MINIMAX_CODE/ml-popup-pro/

# Limpar cache do Updater no WP
wp transient delete mlpp_github_update_cache
wp transient delete update_plugins
```

## Ownership

- **Owner:** ML Lopes Design (`mlopesdesign` no GitHub)
- **License Hub central:** `https://license.mlopesdesign.com.br/` — produto registrado: `ml-popup-pro`
