# Fluxo de Caixa

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/movements`
- Permite: view
- Somente leitura: não há botão de cadastrar, editar ou excluir.

## Busca

A busca pesquisa: `id`. Sem `searchField`, assume `id`.
Ordenação: `id`, `created_at`.

## Filtros e valores válidos

- movementTypes: `internal`, `external`
- operationTypes: `payable`, `receivable`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/movements?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `operation_type`
- `movement_type`
- `value`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
