# Fluxo de Branch por Plano + Publicação Controlada

## Problem Statement
Como garantir que todo plano seja implementado numa branch própria oriunda de
`develop`, publicado de volta em `develop` ao final, com confirmações estritas
contra publicação acidental (inclusive develop→master)?

## Como funciona a amarração
Skills não se encadeiam automaticamente: cada uma é ativada por intenção ou
palavra-chave (ver `AGENTS.md`). Logo, o ciclo vira **passos explícitos dentro
do texto das skills**, cada um travado por uma palavra-gatilho única:

| Passo | Skill | Palavra para prosseguir |
|---|---|---|
| Criar `plan/<slug>` a partir de develop | `plan-execution` (início) | automático ao iniciar |
| Aplicar commits | `git-commit` | `commit` (única) |
| Publicar branch do plano em develop (FF + push) | `plan-execution` (final) | `publicar` |
| Merge develop→master (push = deploy produção) | `git-commit` (pós-push) | `publish_production` |

## Recommended Direction
Mudança mínima em duas skills:
- `plan-execution`: no início cria `plan/<slug>` a partir de develop; ao final,
  exige `publicar` para fazer FF merge em develop + push.
- `git-commit`: mantém o gatilho `commitar`; aplicar os commits passa a exigir
  estritamente `commit`; após o push pergunta se quer merge develop→master,
  exigindo estritamente `publish_production`.

## Key Assumptions
- Desenvolvimento sequencial → FF merge raramente conflita.
- Push em develop = CI/deploy de develop (desejado).
- Push em master = deploy de produção → tranca estrita justificada.

## MVP Scope
- `plan-execution`: passo de criação de branch; passo final de publicação com `publicar`.
- `git-commit`: confirmação estrita `commit`; prompt develop→master com `publish_production`.
- `project-planning`, `AGENTS.md` (apenas nota), restante: sem mudança.

## Not Doing (e porquê)
- PR via `gh` para develop — fricção e dependência externa.
- Merge automático sem confirmação — a tranca é o objetivo.
- Publicação automática em master — sempre perguntar.
- Tags/metadados de plano — desnecessários no MVP.

## Open Questions
- `develop` divergiu no meio do plano: **rebase manual** ou **abortar pedindo ação**?
- Colisão de `plan/<slug>` com título repetido → usar sufixo (data/índice).