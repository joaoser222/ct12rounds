# Clientes

Como cadastrar, editar e localizar clientes.

## Cadastrar cliente

1. Vá em **Clientes** e clique em **Novo**.
2. Preencha **nome** e pelo menos um contato: e-mail, telefone ou documento.
3. Escolha as **graduações** que o cliente já possui. Isso alimenta a fidelidade e
   o perfil público dele.
4. Salve.

O cliente pode existir sem contrato. O contrato é o que dá acesso à área do cliente.

## Localizar cliente

A busca em **Clientes** pesquisa `name`, `email`, `document` e `phone`. Digite
qualquer um deles e a lista filtra sozinha.

Para buscar por outro campo, use a URL: `clients?searchField=name&search=Maria`.
Os campos aceitos são `name`, `email`, `document` e `phone`.

## Editar dados

Clique no cliente na lista. A tela de detalhe permite alterar contatos, graduações,
endereço e observações.

Observações (`annotations`) são internas da equipe e **não** aparecem para o cliente na
área dele.

## Foto e consentimento de imagem

A tela de detalhe tem a aba **Direitos de imagem**, separada dos dados cadastrais.
Isso é independente do cadastro e do contrato.

## O que o cliente vê

Na área do cliente ele vê: documento, endereço, nascimento, telefone, e-mail,
graduações e fidelidade. Tudo sem máscara.

Ele **não** vê: anotações da equipe, centro de custo, referência do gateway e
controle de visibilidade. Esses campos são internos.