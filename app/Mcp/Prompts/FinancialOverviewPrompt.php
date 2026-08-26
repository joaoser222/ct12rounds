<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('financial-overview')]
#[Description('Guia o modelo a apresentar um resumo financeiro com contas a receber vencidas e contas a pagar pendentes.')]
class FinancialOverviewPrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('receivables.view') ?? false;
    }

    public function clientMessage(): ?string
    {
        return null;
    }

    public function handle(Request $request): Response
    {
        return Response::text(<<<'TEXT'
Para montar um resumo financeiro da academia:

1. Leia as contas a receber vencidas para identificar inadimplência.
2. Leia as contas a pagar pendentes para apurar compromissos de curto prazo.
3. Apresente o total vencido a receber, o total pendente a pagar e a posição líquida.
4. Destaque os clientes com maior atraso para cobrança.
TEXT);
    }
}
