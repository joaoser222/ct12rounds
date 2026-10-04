# Acessos e Leads

Site público: pedidos de matrícula e cadastro de cliente.

## Lead de contratação

O formulário público cria um **lead**, não um cliente. Ele registra nome, contato e a
modalidade de interesse, e entra na tela **Leads**.

Lead é só intenção. Cliente é cadastro completo, com contrato.

## Do lead ao cliente

1. Abra o lead em **Leads**.
2. Clique em converter.
3. Preencha os dados que faltam.
4. Escolha se já existe contrato ativo.

A conversão gera o cliente e, se pedido, o contrato.

## Validação

Formulário público não aceita dado inválido: documento é conferido por dígito
verificador, cartão por algoritmo de Luhn, e nome precisa ter pelo menos duas partes.
Isso trava erro de digitação antes de chegar ao banco.

## Limite de requisições

O formulário é público, com limite de requisições por origem. Se uma origem é
bloqueada por spam, o IP entra na lista e as submissions param de chegar.