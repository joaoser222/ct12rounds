# Contratos

Como criar, acompanhar e encerrar contratos.

## Criar contrato

1. Vá em **Contratos** e clique em **Novo**.
2. Selecione o cliente.
3. Escolha o **plano**. O plano define o valor e o que o contrato incluye.
4. Defina a data de início.
5. Salve.

O contrato fica com status `OPEN`. Esse é o status que dá acesso à área do cliente e
que entra em faturamento.

## Ver contratos de um cliente

Na tela de detalhe do cliente há a lista de contratos. Cada um mostra plano, status e
período.

A busca da lista de contratos pesquisa `plan_name` e `client_name`:
`contracts?searchField=client_name&search=Maria`.

## Status possíveis

| Status | Significado |
|---|---|
| `OPEN` | Ativo. Fatura e dá acesso à área do cliente. |
| `COMPLETED` | Encerrado cumprindo o prazo. Não fatura. |
| `CANCELED` | Encerrado por desistência. |
| `RETURNED` | Devolvido. Não fatura. |

Só `OPEN` gera cobrança e libera a área do cliente.

## Encerrar contrato

Abra o contrato e altere o status para `COMPLETED`, `CANCELED` ou `RETURNED`.

Depois de encerrado, o contrato não aparece mais nas telas de faturamento em aberto.
Ele continua no histórico.

## Baixa e cancelamento

Baixa de contrato é um fluxo financeiro, não de contrato. Se a intenção é devolver
dinheiro ao cliente, o caminho é **Financeiro → Contas a Receber**.

Para localizar contratos em aberto: `contracts?searchField=status&search=open`.

O valor é o de `BillableStatus`: `open`, `completed`, `canceled`, `returned`.