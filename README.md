# ML Popup Pro

Plugin premium WordPress para gestão de popups com campanhas, regras de exibição, agendamento, analytics local e templates profissionais.

- **Slug:** `ml-popup-pro`
- **Versão atual:** 1.4.0 (consulte `readme.txt` para changelog completo)
- **Requer:** WordPress 6.0+, PHP 8.1+
- **Licença:** GPL-2.0-or-later

## Visão geral

ML Popup Pro permite criar popups (modal central, barra inferior, slide-in, fullscreen overlay e caixa flutuante) com:

- 7 gatilhos: imediato, delay, scroll %, exit intent, pageviews, seletor CSS, shortcode
- 9 escopos: site inteiro, homepage, posts, páginas, IDs específicos, categorias, tags, URLs específicas, WooCommerce
- 5 regras de agendamento com janela cruzando meia-noite
- 4 frequências de exibição (uma vez por sessão, por visitante, a cada X dias, até fechar)
- Analytics local: impressões, aberturas, fechamentos, cliques primários/secundários, cliques na imagem e conversões
- A/B testing de popups com split de tráfego determinístico
- Webhook de conversão para integração com RD Station, Mailchimp, HubSpot, n8n
- Camada **Free / Pro** validada contra a ML License Hub central

## Estrutura do repositório

```
ml-popup-pro/
├── composer.json          # dependências dev (PHPUnit) — Composer é opcional em runtime
├── phpunit.xml.dist       # config PHPUnit
├── readme.txt              # header WordPress (readme.txt) — obrigatorio pelo WP.org conventions
├── README.md               # este arquivo (dev-focused, fora do ZIP final)
├── CHANGELOG.md            # changelog completo fora do readme.txt (consumível fora do WP)
├── autoloader não usado no runtime — classes são incluídas via require_once explícito
├── ml-popup-pro/           # código do plugin (esta pasta vira a raiz do ZIP final)
│   ├── ml-popup-pro.php    # arquivo principal do plugin
│   ├── readme.txt          # header WP / changelog / upgrade notice
│   ├── uninstall.php
│   ├── includes/           # 10 classes core (MLPP_*)
│   ├── admin/views/       # admin views: dashboard, popups, popup-edit, analytics, settings, templates, audit
│   ├── admin/assets/       # CSS/JS/img do admin
│   ├── public/             # frontend assets
│   ├── languages/         # pot base para tradução
│   ├── tests/              # PHPUnit (carregado por composer install)
│   └── composer.json       # declare de dev deps
└── scripts/
    ├── sync-version.sh     # CI: header == constante == Stable tag
    └── package.sh          # CI: empacota a pasta do plugin em dist/
```

## Camada Free vs Pro

O plugin funciona **Free** sem nenhuma chave Pro. Recursos Pro (requerem licença válida via ML License Hub):

- A/B testing de popups (criar variantes com peso)
- Goal tracking automático por CSS selector (WooCommerce, formulários)
- Filtros avançados de Analytics (período, popup, dispositivo)
- Templates sazonais: Black Friday, Natal, Exit Survey, Frete Grátis
- Identidade visual customizada (CSS variables)
- Hooks de extensibilidade para addons
- Audit log completo de mudanças

Ativação em **Configurações → 🔑 Ativação** (input do serial + validação contra a hub).

## Pipeline de release

1. Edite `ml-popup-pro.php` (header `Version`), `ml-popup-pro.php` linha do `define('MLPP_VERSION', '...')` e `readme.txt` linha `Stable tag`, mantendo os 3 em sincronia. Valide com `bash scripts/sync-version.sh`.
2. `git add -A && git commit -m "..." && git tag vX.Y.Z && git push origin main --tags`.
3. O GitHub Actions (`release.yml`) valida lint, roda PHPUnit, empacota e cria a Release com SHA-256 + tamanho do ZIP publicado.

## Testes

```bash
cd ml-popup-pro
composer install
vendor/bin/phpunit
```

Roda 11 testes cobrindo sanitização (`Security`), mapeamento de status da hub e flag `is_premium` (`License`). Cobertura mais ampla (Rules, Storage, Analytics com mocks WP) cabe em PRs incrementais.

## Traduções

`languages/ml-popup-pro.pot` é o catálogo base. Para pt-BR oficial adicione um `.po/.mo` em `wp-content/languages/plugins/ml-popup-pro-pt_BR.mo`. Strings vão sendo adicionadas conforme novas entradas no admin usam `__()` / `_e()`.

## Notas de configuração do CI

- `sync-version.sh` garante que `Version:` no header, `MLPP_VERSION` no `define()` e `Stable tag:` no `readme.txt` apontem para a mesma versão. Falha rápido antes do build.
- `package.sh` cria `dist/ml-popup-pro-vX.Y.Z.zip` preservando a pasta raiz `ml-popup-pro/` e excluindo `.git/`, `.github/`, `vendor/`, `*.zip` e arquivos do repo.
- Auto-update no WP usa o GitHub Releases API direto via `includes/class-mlpp-updater.php` (com fallback automático pro zipball se o CDN de `releases/download/` der 504 — adicionado na v1.0.14).

## Auditoria rápida

Antes de qualquer release:

1. `bash scripts/sync-version.sh` deve passar.
2. `cd ml-popup-pro && composer install && vendor/bin/phpunit` deve passar (11 testes verdes).
3. Visual em staging: Configurações → 🔑 Ativação deve mostrar aba, input de serial, status Free/Pro, lista de recursos Pro.
4. Auto-update: WP Admin → Plugins → ML Popup Pro → "Há uma nova versão" deve aparecer após `git tag vX.Y.Z && git push --tags` (com `Limpar cache` no painel do plugin se aparecer atrasado).
