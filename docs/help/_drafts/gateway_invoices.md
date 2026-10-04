# Notas Fiscais

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/gateway-invoices`
- Permite: view
- Somente leitura: não há botão de cadastrar, editar ou excluir.

## Busca

A busca pesquisa: `gateway_reference_key`, `status`, `invoice_number`, `external_reference`. Sem `searchField`, assume `gateway_reference_key`.
Ordenação: `id`, `status`, `value`, `effective_date`, `created_at`.

## Filtros e valores válidos

- invoiceStatus: `pending`, `scheduled`, `processing`, `synchronized`, `authorized`, `processing_cancellation`, `cancellation_denied`, `canceled`, `error`, `unknown`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/gateway-invoices?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `gateway_reference_key`
- `status`
- `status_description`
- `gateway_account_id`
- `gateway_payment_id`
- `invoice_id`
- `invoice_number`
- `validation_code`
- `service_description`
- `observations`
- `value`
- `deductions`
- `effective_date`
- `pdf_url`
- `xml_url`
- `municipal_service_id`
- `municipal_service_code`
- `municipal_service_description`
- `external_reference`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
