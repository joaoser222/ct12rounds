<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Clients\UpdateClientAction;
use App\DTOs\Clients\UpdateClientDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use App\Models\Client;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('update-client')]
#[Description('Atualiza um cliente existente')]
#[IsIdempotent(true)]
class UpdateClientTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected UpdateClientAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|min:1',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|min:10|max:11',
            'document' => 'nullable|string|size:11',
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date',
            'audience_category' => 'nullable|in:adult,child',
            'legal_representative_name' => 'nullable|string|max:255',
            'legal_representative_document' => 'nullable|string|size:11',
            'legal_representative_birth_date' => 'nullable|date',
            'address_postal_code' => 'nullable|string|max:8',
            'address' => 'nullable|string|max:200',
            'address_number' => 'nullable|string|max:10',
            'address_complement' => 'nullable|string|max:100',
            'address_district' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|size:2',
            'address_city' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $client = Client::find($validated['id']);

        if (! $client) {
            return Response::error('Cliente não encontrado.');
        }

        $dto = UpdateClientDTO::from(array_merge(
            ['id' => $client->id],
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
        return auth()->user()?->can('clients.update') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('ID do cliente')->required(),
            'name' => $schema->string()->description('Nome completo do cliente')->nullable(),
            'email' => $schema->string()->description('E-mail do cliente')->nullable(),
            'phone' => $schema->string()->description('Telefone do cliente (DDD + número)')->nullable(),
            'document' => $schema->string()->description('CPF do cliente (11 dígitos)')->nullable(),
            'gender' => $schema->string()->description('Gênero: male, female ou other')->nullable(),
            'birth_date' => $schema->string()->description('Data de nascimento (Y-m-d)')->nullable(),
            'audience_category' => $schema->string()->description('Categoria de público: adult ou child')->nullable(),
            'legal_representative_name' => $schema->string()->description('Nome do representante legal')->nullable(),
            'legal_representative_document' => $schema->string()->description('CPF do representante legal (11 dígitos)')->nullable(),
            'legal_representative_birth_date' => $schema->string()->description('Data de nascimento do representante legal (Y-m-d)')->nullable(),
            'address_postal_code' => $schema->string()->description('CEP (8 dígitos)')->nullable(),
            'address' => $schema->string()->description('Logradouro do endereço')->nullable(),
            'address_number' => $schema->string()->description('Número do endereço')->nullable(),
            'address_complement' => $schema->string()->description('Complemento do endereço')->nullable(),
            'address_district' => $schema->string()->description('Bairro do endereço')->nullable(),
            'address_state' => $schema->string()->description('UF (2 letras)')->nullable(),
            'address_city' => $schema->string()->description('Cidade do endereço')->nullable(),
            'status' => $schema->string()->description('Status: active ou inactive')->nullable(),
        ];
    }
}
