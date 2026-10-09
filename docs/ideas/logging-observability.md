# Logging Observability (Laravel → Loki → Grafana)

## Problem Statement
Como garantir que, em incidente de produção, o operador encontre erros do Laravel (e filas) em minutos no Grafana — com nível, contexto e ambiente (develop vs production) corretos — publicando o Grafana em `tools.ct12rounds.com.br/grafana`?

## Estado atual (diagnóstico)
- Laravel loga no canal `stderr` sem formatter dedicado → Monolog `LineFormatter` (`[ts] local.ERROR: msg`), não logfmt nem JSON.
- Promtail (`docker/promtail/config.yml`) espera `^time level message` e não casa com o LineFormatter.
- Dashboard `docker/grafana/dashboards/laravel-logs.json` usa `| logfmt | __error__=""` e **descarta** logs que falham no parse.
- Loki/Promtail/Grafana existem no `compose.yaml` com profile `monitoring`, mas CI (`develop.yml` / `master.yml`) não os sobe.
- Produção e develop rodam no mesmo host Docker (`ct12rounds`): `ct12rounds_app` / `_queue` / `_scheduler` vs `ct12rounds_app_develop`.

## Recommended Direction
**Direção A:** um único Loki no host de produção; Promtail com stage `json` + label `env` derivada do nome do container; Laravel em `JsonFormatter` no stderr; Grafana atrás do Caddy em `tools.ct12rounds.com.br/grafana` (`serve_from_sub_path`); **um** dashboard provisionado com template variable **Ambiente** (`develop` | `production`) filtrando `env`.

Justificativa: produção e develop já compartilham o mesmo Docker project; separar por label evita 2 stacks e 2 dashboards; sub-path em tools deixa o host livre para outros painéis de admin depois.

## Key Assumptions to Validate
- [ ] Deploy de produção/develop passa a subir monitoring (`--profile monitoring` ou equivalentes) — CI atual não sobe Loki/Promtail/Grafana.
- [ ] Container names estáveis: `ct12rounds_app` / `_queue` / `_scheduler` → production; `ct12rounds_app_develop` → develop.
- [ ] `JsonFormatter` no stderr + stage `json` do Promtail produz labels `level` e mensagem pesquisável no LogQL.
- [ ] Caddy consegue reverse_proxy para `grafana:3000` com path prefix e root URL correta.
- [ ] `tools.ct12rounds.com.br` resolve/tem cert para o host de produção.

## MVP Scope
**In:**
- `LOG_STDERR_FORMATTER` (JsonFormatter) no env de app/queue/scheduler (e develop).
- Promtail: `json` stage, label `level`, label `env` por regex no nome do container.
- Caddy: site/block `tools.ct12rounds.com.br` → `/grafana*` reverse_proxy Grafana.
- Grafana: `GF_SERVER_ROOT_URL`, `serve_from_sub_path`, datasource Loki, dashboard com dropdown Ambiente + painéis: erros recentes, contagem por level, logs do app.
- Ativação do profile monitoring no host de produção (compose/CI).

**Out:** alertas, Caddy access logs, metrics (Prometheus), multi-tenant Loki, retenção custom agressiva.

## Not Doing (and Why)
- **Dois Loki / dois Grafana** — mesmo host; custo e confusão sem ganho.
- **Dois dashboards idênticos** — manutenção duplicada; seletor resolve.
- **Alertas no MVP** — validar uso primeiro; adicionar quando houver canal e dor real.
- **Access logs do Caddy** — fora do escopo “só Laravel” desta rodada.
- **Mudar nomes de container / CI de deploy de app** — só o mínimo para monitoring subir.

## Open Questions
- Quem autentica no Grafana em produção? (hoje `admin`/`admin` provisionado — inadequado se tools for exposto.)
- Retenção do Loki e disco aceitável no host?
- Queue/scheduler de develop existem no host prod ou só `app_develop`? (afeta quais containers viram `env=develop`.)
