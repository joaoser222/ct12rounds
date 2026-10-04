# Centros de Custo

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/cost-centers`
- Permite: view, create, update, delete, visibility
- Cadastro e edição disponíveis na própria tela.

## Busca

A busca pesquisa: `name`. Sem `searchField`, assume `name`.
Ordenação: `id`, `name`, `created_at`.

## Filtros e valores válidos

- operationTypes: `payable`, `receivable`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/cost-centers?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `name`
- `color`
- `operation_type`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
