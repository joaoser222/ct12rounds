<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Trainer\UpdateTrainerAction;
use App\DTOs\Trainer\UpdateTrainerDTO;
use App\Models\Trainer;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-trainer')]
#[Description('Atualiza um instrutor existente')]
#[IsIdempotent(true)]
class UpdateTrainerTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateTrainerAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'document' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'profile_image' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'address_number' => 'nullable|string|max:50',
            'address_complement' => 'nullable|string|max:255',
            'address_state' => 'nullable|string|max:2',
            'address_city' => 'nullable|string|max:255',
            'address_district' => 'nullable|string|max:255',
            'address_postal_code' => 'nullable|string|max:10',
            'trainer_modalities' => 'nullable|array',
        ]);

        $trainer = Trainer::find($validated['id']);

        if (! $trainer) {
            return Response::error('Instrutor não encontrado.');
        }

        $dto = UpdateTrainerDTO::from(array_merge(
            ['id' => $trainer->id],
            array_filter($validated, fn ($v) => $v !== null, ARRAY_FILTER_USE_KEY),
        ));

        $result = $this->action->execute($dto);

        if (! $result->success) {
            return Response::error($result->message . ': ' . implode(', ', $result->errors ?? []));
        }

        return Response::json([
            'id' => $result->data->id,
            'name' => $result->data->name,
            'email' => $result->data->email,
            'document' => $result->data->document,
        ]);
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('trainers.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do instrutor')->required(),
            'name' => $schema->string()->description('Novo nome do instrutor')->nullable(),
            'email' => $schema->string()->description('Novo e-mail do instrutor')->nullable(),
            'document' => $schema->string()->description('Novo CPF ou documento do instrutor')->nullable(),
            'birth_date' => $schema->string()->description('Nova data de nascimento (Y-m-d)')->nullable(),
            'phone' => $schema->string()->description('Novo telefone do instrutor')->nullable(),
            'gender' => $schema->string()->description('Novo gênero (male, female, other)')->nullable(),
            'profile_image' => $schema->string()->description('Nova URL da imagem de perfil')->nullable(),
            'address' => $schema->string()->description('Novo logradouro do endereço')->nullable(),
            'address_number' => $schema->string()->description('Novo número do endereço')->nullable(),
            'address_complement' => $schema->string()->description('Novo complemento do endereço')->nullable(),
            'address_state' => $schema->string()->description('Novo estado (UF, 2 caracteres)')->nullable(),
            'address_city' => $schema->string()->description('Nova cidade do endereço')->nullable(),
            'address_district' => $schema->string()->description('Novo bairro do endereço')->nullable(),
            'address_postal_code' => $schema->string()->description('Novo CEP do endereço')->nullable(),
            'trainer_modalities' => $schema->array()->description('Novas modalidades vinculadas ao instrutor')->nullable(),
        ];
    }
}
