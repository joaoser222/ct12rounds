# Transferências Gateway

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/gateway-transfers`
- Permite: view, create
- Somente leitura: não há botão de cadastrar, editar ou excluir.

## Busca

A busca pesquisa: `gateway_reference_key`, `status`. Sem `searchField`, assume `gateway_reference_key`.
Ordenação: `id`, `gross_value`, `fee_value`, `total`, `status`, `created_at`.

## Filtros e valores válidos

- transactionStatus: `pending`, `paid`, `failed`, `refunded`, `canceled`, `overdue`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/gateway-transfers?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `gateway_reference_key`
- `gross_value`
- `fee_value`
- `total`
- `status`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
