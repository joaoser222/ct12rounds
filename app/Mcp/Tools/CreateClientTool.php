<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\Clients\CreateClientAction;
use App\DTOs\Clients\CreateClientDTO;
use App\Mcp\Tools\Concerns\HasMcpToolName;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tool;

#[Name('create-client')]
#[Description('Cria um novo cliente no sistema')]
#[IsIdempotent(false)]
class CreateClientTool extends Tool
{
    use HasMcpToolName;

    public function __construct(
        protected CreateClientAction $action,
    ) {}

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|min:10|max:11',
            'document' => 'required|string|size:11',
            'gender' => 'required|in:male,female,other',
            'birth_date' => 'required|date',
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
        ]);

        $dto = CreateClientDTO::from($validated);
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
        return auth()->user()?->can('clients.create') ?? false;
    }

    public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Nome completo do cliente')->required(),
            'email' => $schema->string()->description('E-mail do cliente')->required(),
            'phone' => $schema->string()->description('Telefone do cliente (DDD + número)')->required(),
            'document' => $schema->string()->description('CPF do cliente (11 dígitos)')->required(),
            'gender' => $schema->string()->description('Gênero: male, female ou other')->required(),
            'birth_date' => $schema->string()->description('Data de nascimento (Y-m-d)')->required(),
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
        ];
    }
}
