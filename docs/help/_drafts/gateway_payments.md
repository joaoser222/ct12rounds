# Gateway de Pagamentos

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/gateway-payments`
- Permite: view
- Somente leitura: não há botão de cadastrar, editar ou excluir.

## Busca

A busca pesquisa: `gateway_reference_key`, `payment_method`, `status`, `payment_date`. Sem `searchField`, assume `gateway_reference_key`.
Ordenação: `id`, `payment_date`, `status`, `gross_value`, `fee_value`, `total`, `created_at`.

## Filtros e valores válidos

- paymentMethods: `boleto`, `pix`, `credit_card`, `cash`
- transactionStatus: `pending`, `paid`, `failed`, `refunded`, `canceled`, `overdue`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/gateway-payments?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `gateway_reference_key`
- `payment_method`
- `payment_date`
- `status`
- `gross_value`
- `fee_value`
- `total`
- `invoice_id`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
