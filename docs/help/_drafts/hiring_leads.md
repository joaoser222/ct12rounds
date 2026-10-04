# Pré-cadastro de Clientes

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/hiring-leads`
- Permite: view, update, delete, visibility
- Cadastro e edição disponíveis na própria tela.

## Busca

A busca pesquisa: `name`, `email`, `document`, `phone`. Sem `searchField`, assume `name`.
Ordenação: `id`, `source`, `status`, `created_at`.

## Filtros e valores válidos

- hiringLeadSource: `site`, `contract`
- hiringLeadStatus: `new`, `contacted`, `converted`, `discarded`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/hiring-leads?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `name`
- `document`
- `phone`
- `email`
- `source`
- `status`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
