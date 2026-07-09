# Harness

Diretório de meta-configuração do repositório. Não é empacotado dentro do ZIP do plugin (`package.sh` ignora tudo abaixo de `.git`, `.github`, vendor, *.zip) mas aparece no source do GitHub pra alinhar com `ml-link-auditor`.

## Conteúdo

- `AGENTS.md` — instruções pra agentes de IA / PRs reviewers / devs que aterrizam no repo
- `policies.md` — regras de versionamento + branching + release (em breve)
- `preflight.sh` — script de pre-commit que roda `php -l` + `node --check` + `scripts/sync-version.sh` antes de aceitar commit (em breve)

## Como adicionar coisas aqui

Tudo que é "meta" (regras de processo, scripts de qualidade, docs de arquitetura) vai aqui, e fica fora do ZIP final. O plugin em si vive só em `ml-popup-pro/`.
