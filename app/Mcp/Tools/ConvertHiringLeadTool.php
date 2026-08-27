<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\HiringLeads\ConvertHiringLeadAction;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\HiringLead;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('convert-hiring-lead')]
#[Description('Converte um pré-cadastro de cliente (hiring_lead) em um cliente efetivo')]
#[IsIdempotent(true)]
class ConvertHiringLeadTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected ConvertHiringLeadAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $lead = HiringLead::query()->find($validated['id']);

        if ($lead === null) {
            return Response::error('Pré-cadastro não encontrado para o ID informado.');
        }

        $result = $this->action->execute($lead);

        if (! $result->success) {
            return Response::error($result->message);
        }

        return Response::json([
            'message' => $result->message,
            'client_id' => $result->data['client_id'] ?? null,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('hiring_leads.update') ?? false;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do pré-cadastro a ser convertido')->required(),
        ];
    }
}
