# Pessoas

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/clients`
- Permite: view, create, update, delete, visibility
- Cadastro e edição disponíveis na própria tela.

## Busca

A busca pesquisa: `name`, `email`, `document`, `phone`. Sem `searchField`, assume `name`.
Ordenação: `id`, `created_at`, `updated_at`.

## Filtros e valores válidos

- clientStatus: `active`, `pending`, `inactive`, `overdued`, `locked`
- loyaltyLevels: `6`, `3`, `2`, `7`, `1`, `4`, `5`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/clients?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `name`
- `document`
- `status`
- `phone`
- `loyalty_streak_months`
- `created_at`
- `updated_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
