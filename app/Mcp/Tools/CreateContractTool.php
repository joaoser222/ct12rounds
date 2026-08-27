<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Contracts\CreateContractAction;
use App\DTOs\Contracts\CreateContractDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-contract')]
#[Description('Cria um novo contrato pendente com QR Code de cadastro')]
#[IsIdempotent(false)]
class CreateContractTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateContractAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'plan_id' => 'required|integer|min:1',
            'installments' => 'required|integer|min:1',
            'coupon_id' => 'nullable|integer',
            'annotations' => 'nullable|string|max:500',
        ]);

        $dto = CreateContractDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'plan_name' => $result->data->plan_name,
            'status' => $result->data->status,
            'total' => $result->data->total,
            'installments' => $result->data->installments,
            'first_due_date' => $result->data->first_due_date,
            'registration_token' => $result->data->registration_token,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('contracts.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'plan_id' => $schema->integer()->description('ID do plano contratado')->required(),
            'installments' => $schema->integer()->description('Número de parcelas')->required(),
            'coupon_id' => $schema->integer()->description('ID do cupom de desconto (opcional)')->nullable(),
            'annotations' => $schema->string()->description('Observações do contrato')->nullable(),
        ];
    }
}