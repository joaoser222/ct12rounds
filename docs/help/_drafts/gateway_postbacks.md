# Postbacks Gateway

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/gateway-postbacks`
- Permite: view
- Somente leitura: não há botão de cadastrar, editar ou excluir.

## Busca

A busca pesquisa: `postback_event`, `postback_type`, `status`. Sem `searchField`, assume `postback_event`.
Ordenação: `id`, `postback_event`, `postback_type`, `status`, `created_at`.

## Filtros e valores válidos

- postbackStatus: `pending`, `failed`, `success`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/gateway-postbacks?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `postback_event`
- `postback_type`
- `status`
- `gateway_account_id`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
