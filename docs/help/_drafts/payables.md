# Contas a Pagar

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/payables`
- Permite: view, create, update, delete, visibility, mark_paid, mark_unpaid
- Cadastro e edição disponíveis na própria tela.

## Busca

A busca pesquisa: `due_date`, `payment_date`, `status`. Sem `searchField`, assume `due_date`.
Ordenação: `id`, `due_date`, `created_at`.

## Filtros e valores válidos

- invoiceStatus: `pending`, `waiting`, `overdued`, `paid`, `canceled`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/payables?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `due_date`
- `payment_date`
- `total`
- `status`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
