# Fechamento e Relatórios

Como ler o resultado financeiro do mês.

## Contas a receber em aberto

**Financeiro → Contas a Receber**, filtrando por situação.

- Vencidas: `receivables?searchField=status&search=overdued`
- Aguardando pagamento: `receivables?searchField=status&search=waiting`

Sempre informe `searchField`, senão a busca assume vencimento.

## Contas a pagar do período

**Financeiro → Contas a Pagar**, filtrando por vencimento.

## Movimentos

**Financeiro → Movimentos** é o extrato: tudo que entrou e saiu, por centro de custo.
É a base do DRE.

## Relatórios

Em **Relatórios**:

- **Financeiro**: entradas, saídas e saldo por período e centro de custo.
- **Faturamento**: receita por plano e por contrato.
- **Inadimplência**: parcelas vencidas, por cliente e por idade.

## Marcar vencidos

Todo dia à 00:10 o sistema marca automaticamente as parcelas cujo vencimento passou e
que seguem em aberto, passando para `overdued`.

Se o comando não roda, o relatório de inadimplência fica incompleto. Verifique se o
agendador está ativo e se o comando não ficou bloqueado por uma execução anterior.