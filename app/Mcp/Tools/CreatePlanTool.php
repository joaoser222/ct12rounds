<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Plans\CreatePlanAction;
use App\DTOs\Plans\CreatePlanDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('create-plan')]
#[Description('Cria um novo plano no sistema')]
#[IsIdempotent(false)]
class CreatePlanTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreatePlanAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'plan_category_id' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'duration_months' => 'required|integer|min:1',
            'plan_modalities' => 'nullable|array',
        ]);

        $dto = CreatePlanDTO::from($validated);
        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message.': '.implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'description' => $result->data->description,
            'price' => $result->data->price,
            'duration_months' => $result->data->duration_months,
            'visibility' => $result->data->visibility,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('plans.create') ?? false;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome do plano')->required(),
            'plan_category_id' => $schema->integer()->description('ID da categoria do plano')->required(),
            'description' => $schema->string()->description('Descrição do plano')->nullable(),
            'price' => $schema->number()->description('Preço mensal do plano')->required(),
            'duration_months' => $schema->integer()->description('Duração em meses do plano')->required(),
            'plan_modalities' => $schema->array()->description('Modalidades vinculadas ao plano')->nullable(),
        ];
    }
}
