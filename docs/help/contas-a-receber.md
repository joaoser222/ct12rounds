# Contas a Receber

Onde nasce a cobrança do cliente e como registrar pagamento.

## Onde nasce a cobrança

Contratos com status `OPEN` geram parcelas em **Contas a Receber**. Cada parcela tem
valor, vencimento e situação.

## Situação de uma parcela

O valor é o da etiqueta `InvoiceStatus`. A coluna `status` aceita na busca é o mesmo
valor.

| Valor | Etiqueta | Significado |
|---|---|---|
| `pending` | Pendente | Ainda não emitida para o cliente. |
| `waiting` | Aguardando Pagamento | Emitida, dentro do prazo. |
| `overdued` | Vencido | Venceu e segue em aberto. |
| `paid` | Pago | Quitada. |
| `canceled` | Cancelado | Cancelada. |

Atenção ao valor: é `overdued`, não `overdue`. A busca é literal.

## Registrar pagamento

1. Abra **Financeiro → Contas a Receber**.
2. Localize a parcela.
3. Clique em registrar pagamento.
4. Confirme o valor.

Parcela quitada passa para `paid`.

## Registrar pagamento parcial

No mesmo formulário, informe um valor menor que o saldo. A parcela guarda o valor já
pago e continua em `waiting`, ou passa para `overdued` se já venceu.

## Localizar parcelas

A busca pesquisa `due_date`, `payment_date` e `status`.

- Vencidas: `receivables?searchField=status&search=overdued`
- Emitidas e aguardando: `receivables?searchField=status&search=waiting`
- Por período de vencimento: `receivables?searchField=due_date&search=2026-01`

Sempre informe o campo. Sem `searchField`, a busca assume a primeira coluna da lista,
que é `due_date` — e o filtro de situação não funciona.

## Baixa

Baixa é o estorno de um recebimento já registrado. Ela devolve o valor e reabre a
parcela. Use apenas para devoluções, não para corrigir valor digitado errado — nesse
caso, exclua o pagamento errado e registre de novo.