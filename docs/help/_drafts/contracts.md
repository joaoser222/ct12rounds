# Contratos

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/contracts`
- Permite: view, create, update, delete, visibility, cancel
- Cadastro e edição disponíveis na própria tela.

## Busca

A busca pesquisa: `plan_name`, `client_name`. Sem `searchField`, assume `plan_name`.
Ordenação: `id`, `plan_name`, `first_due_date`, `created_at`, `accepted_terms`.

## Filtros e valores válidos

- billableStatus: `open`, `completed`, `canceled`, `returned`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/contracts?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `plan_name`
- `total`
- `first_due_date`
- `installments`
- `status`
- `accepted_terms`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
