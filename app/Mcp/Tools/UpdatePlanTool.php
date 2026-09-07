<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Plans\UpdatePlanAction;
use App\DTOs\Plans\UpdatePlanDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\Plan;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update-plan')]
#[Description('Atualiza um plano existente')]
#[IsIdempotent(true)]
class UpdatePlanTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdatePlanAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'plan_category_id' => 'nullable|integer|min:1',
            'description' => 'nullable|string|max:500',
            'price' => 'nullable|numeric|min:0',
            'duration_months' => 'nullable|integer|min:1',
            'plan_modalities' => 'nullable|array',
        ]);

        $plan = Plan::find($validated['id']);

        if (! $plan) {
            return Response::error('Plano não encontrado.');
        }

        $dto = UpdatePlanDTO::from(array_merge(
            ['id' => $plan->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

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
        return auth()->user()?->can('plans.update') ?? false;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do plano')->required(),
            'name' => $schema->string()->description('Novo nome do plano')->nullable(),
            'plan_category_id' => $schema->integer()->description('Novo ID da categoria do plano')->nullable(),
            'description' => $schema->string()->description('Nova descrição do plano')->nullable(),
            'price' => $schema->number()->description('Novo preço mensal do plano')->nullable(),
            'duration_months' => $schema->integer()->description('Nova duração em meses do plano')->nullable(),
            'plan_modalities' => $schema->array()->description('Novas modalidades vinculadas ao plano')->nullable(),
        ];
    }
}
