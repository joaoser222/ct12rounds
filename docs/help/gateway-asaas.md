# Gateway de Pagamentos (Asaas)

Integração com o gateway: contas, faturas, cobranças e transferências.

## Cadastrar conta do gateway

1. Vá em **Gateway** e abra **Contas do Gateway**.
2. Cadastre a conta com a chave de API.
3. Escolha quais serviços quer usar: cartão de crédito, PIX, boleto.

A chave é guardada criptografada. Ela não aparece depois de salva.

## Sincronizar

Cada tela do gateway tem um botão **Sincronizar**. Ele dispara o job de sync para o
escopo aquela tela.

Escopos: `payments`, `transfers`, `customers`, `postbacks`.

Um escopo por vez, máximo de 5 por minuto. Se um sync falhar por falta de lock, outro
sync já está rodando — aguarde em vez de repetir.

## Faturas

Em **Gateway → Faturas**, cada fatura tem o número, o cliente, o valor e o status.

Para emitir NFS-e das faturas pagas, use **Gateway → Contas do Gateway → Nota Fiscal**
e escolha o serviço municipal.

## Transferências

**Gateway → Transferências** lista as saídas para o banco ou fornecedor. O
**destinatário** (`Gateway → Destinatários`) é quem recebe.

Antes de uma transferência sair, o destinatário precisa estar com chave cadastrada e
validada. Sem isso, a transferência é recusada pelo gateway.

## Clientes do gateway

**Gateway → Clientes** espelha os clientes no gateway. Sincronizar antes de cobrar de
um cliente novo, senão a cobrança sai para um cadastro inexistente no outro lado.