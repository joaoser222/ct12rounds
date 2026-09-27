<?php

namespace Tests\Feature\Actions;

use App\Actions\Clients\UpdateClientAction;
use App\DTOs\Clients\UpdateClientDTO;
use App\Models\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateClientActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_a_client_with_valid_data(): void
    {
        $client = Client::factory()->create([
            'document' => '12345678909',
            'name' => 'Nome Antigo',
        ]);

        $action = app(UpdateClientAction::class);
        $dto = new UpdateClientDTO(
            id: $client->id,
            name: 'Nome Novo',
            email: 'novo@teste.com',
            phone: '11988887777',
            document: '12345678909',
            gender: 'F',
            birth_date: '1995-05-15',
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Nome Novo',
            'email' => 'novo@teste.com',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $client = Client::factory()->create(['document' => '12345678909']);

        $action = app(UpdateClientAction::class);
        $dto = new UpdateClientDTO(
            id: $client->id,
            name: 'Atualizado',
            email: 'at@test.com',
            phone: '11988887777',
            document: '12345678909',
            gender: 'M',
            birth_date: '1990-01-01',
        );

        $result = $action->execute($dto);

        $this->assertSame('Cliente atualizado com sucesso.', $result->message);
    }

    public function test_throws_when_client_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateClientAction::class);
        $dto = new UpdateClientDTO(
            id: 999999,
            name: 'Inexistente',
            email: 'x@test.com',
            phone: '11988887777',
            document: '12345678909',
            gender: 'M',
            birth_date: '1990-01-01',
        );
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateClientAction::class);
        $action->execute('not-a-dto');
    }
}
