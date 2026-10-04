# Cadastros e Configurações

Onde ficam os dados que sustentam o resto do sistema.

## Planos

**Planos** define o que o cliente contrata: valor, periodicidade, o que inclui e as
aulas diretas que o plano dá.

Plano é a origem do valor da cobrança. Sem plano correto, o contrato fatura errado.

## Categorias de plano

**Categorias de Plano** agrupam planos para efeito de relatório — por exemplo
"Mensalidades" e "Avulsos".

## Formas de pagamento

**Produtos** e **Modalidades de Pagamento** determinam como o valor entra: cartão,
PIX, boleto, dinheiro.

## Usuários e permissões

**Usuários** são os acessos da equipe. Cada um recebe um papel, e o papel define o que
a pessoa pode ver e fazer.

O controle de acesso segue o formato `{módulo}.{ação}` — por exemplo `clients.view`,
`contracts.create`, `chat.view`.

Uma pessoa sem permissão na tela recebe o mesmo bloqueio que receberia se acessasse a
URL diretamente.

## Dados da academia

**Configurações** guarda dados fiscais, cidade, razão social e parâmetros de emissão
de nota. Alterar aqui afeta a emissão fiscal de todo o sistema.