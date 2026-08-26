<?php

declare(strict_types=1);

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Prompt;

#[Name('collect-receivable')]
#[Description('Guia o modelo a registrar o recebimento de uma conta a receber de um cliente.')]
class CollectReceivablePrompt extends Prompt
{
    public function shouldRegister(): bool
    {
        return auth()->user()?->can('receivables.mark_paid') ?? false;
    }

    public function clientMessage(): ?string
    {
        return null;
    }

    public function handle(Request $request): Response
    {
        return Response::text(<<<'TEXT'
Para registrar o recebimento de uma conta a receber:

1. Localize a conta a receber pendente do cliente (por ID ou documento).
2. Confirme o valor a ser recebido com o usuário.
3. Use a ferramenta de marcar conta a receber como paga informando o ID da conta e a data de recebimento.
4. Informe o comprovante/recibo gerado ao usuário.
TEXT);
    }
}
