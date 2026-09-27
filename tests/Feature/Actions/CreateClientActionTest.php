<?php

namespace Tests\Feature\Actions;

use App\Actions\Clients\CreateClientAction;
use App\DTOs\Clients\CreateClientDTO;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateClientActionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        return [
            'name' => 'Cliente Teste',
            'email' => 'cliente@teste.com',
            'phone' => '11999999999',
            'document' => '12345678909',
            'gender' => 'M',
            'birth_date' => '1990-01-01',
            'legal_representative' => false,
        ];
    }

    public function test_creates_a_client_with_valid_data(): void
    {
        $action = app(CreateClientAction::class);
        $dto = CreateClientDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('clients', [
            'email' => 'cliente@teste.com',
            'document' => '12345678909',
        ]);
    }

    public function test_sets_client_status_to_active(): void
    {
        $action = app(CreateClientAction::class);
        $dto = CreateClientDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertSame('active', $result->data->status);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateClientAction::class);
        $dto = CreateClientDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertSame('Cliente criado com sucesso.', $result->message);
    }

    public function test_stores_optional_address_fields(): void
    {
        $data = array_merge($this->validData(), [
            'address_postal_code' => '01001000',
            'address' => 'Rua Teste',
            'address_number' => '100',
            'address_complement' => 'Apto 1',
            'address_district' => 'Centro',
            'address_state' => 'SP',
            'address_city' => 'São Paulo',
        ]);

        $action = app(CreateClientAction::class);
        $dto = CreateClientDTO::from($data);

        $result = $action->execute($dto);

        $this->assertDatabaseHas('clients', [
            'email' => 'cliente@teste.com',
            'address' => 'Rua Teste',
            'address_city' => 'São Paulo',
        ]);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateClientAction::class);
        $action->execute('not-a-dto');
    }
}
