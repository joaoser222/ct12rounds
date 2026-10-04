# Fidelidade e Graduações

Como o sistema acompanha a evolução do aluno.

## O que é

**Graduação** é uma faixa dentro de uma modalidade — por exemplo, faixa roxa de
jiu-jitsu. Cada graduação tem nome, cor e ordem.

**Modalidade** agrupa as graduações de uma área.

**Fidelidade** é o contador do cliente: a graduação atual, quantos meses ele está
nessa graduação e desde quando.

## Ajustar a graduação de um cliente

1. Abra o cliente.
2. Vá em **Graduações**.
3. Adicione ou remova a graduação.
4. Salve.

Ao adicionar uma graduação de nível superior, atualize a fidelidade para que a
sequência e o contador façam sentido.

## Definir regras de fidelidade

Em **Níveis de Fidelidade** você define quanto tempo cada graduação dura até a
promoção e o que precisa para avançar.

O comando `loyalty:refresh` roda toda madrugada às 03:00 e recalcula os contadores a
partir dessas regras. Ele não roda na hora — se precisar recalcular agora, use o
botão de recálculo na tela de fidelidade.

## Consultar quem está há mais tempo em uma graduação

A tela de fidelidade lista os clientes com mês de Permanência na graduação atual. É
onde se identifica quem está pronto para promover.