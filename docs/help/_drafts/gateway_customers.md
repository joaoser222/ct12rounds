# Clientes Gateway

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/gateway-customers`
- Permite: view
- Somente leitura: não há botão de cadastrar, editar ou excluir.

## Busca

A busca pesquisa: `gateway_reference_key`, `holder_type`. Sem `searchField`, assume `gateway_reference_key`.
Ordenação: `id`, `holder_type`, `holder_id`, `created_at`.

## Campos da lista

- `id`
- `gateway_reference_key`
- `holder_type`
- `holder_id`
- `gateway_account_id`
- `gateway_postback_id`
- `created_at`
- `updated_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
