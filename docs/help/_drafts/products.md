# Produtos

> Rascunho gerado por `php artisan help:skeleton`. Os fatos abaixo saíram do código;
> os blocos TODO precisam ser escritos por alguém que usa a tela.

## Onde fica

- Tela: `/products`
- Permite: view, create, update, delete, visibility
- Cadastro e edição disponíveis na própria tela.

## Busca

A busca pesquisa: `name`. Sem `searchField`, assume `name`.
Ordenação: `id`, `name`, `sale_price`, `created_at`.

## Filtros e valores válidos

- productTypes: `merchandise`, `service`

A busca compara o texto literalmente, então um link precisa do valor bruto:
`/products?searchField=<campo>&search=<valor>`.

## Campos da lista

- `id`
- `name`
- `sale_price`
- `quantity`
- `product_type`
- `product_unity_label`
- `created_at`

## Como usar

TODO: passo a passo na linguagem de quem usa a tela, sem nome de código.

## Perguntas frequentes

TODO: as dúvidas que o pessoal realmente faz sobre esta tela.
