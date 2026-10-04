# Vendas

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/sales`
- Permite: view, create, update, delete, visibility, mark_paid, mark_unpaid
- Cadastro e edição disponíveis na própria tela.

## Busca

A busca pesquisa: `client_name`. Sem `searchField`, assume `client_name`.
Ordenação: `id`, `total`, `created_at`.

## Filtros e valores válidos

- billableStatus: `open`, `completed`, `canceled`, `returned`
- paymentMethods: `boleto`, `pix`, `credit_card`, `cash`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/sales?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `total`
- `status`
- `client_name`
- `payment_method`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
