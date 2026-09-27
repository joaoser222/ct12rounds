<?php

namespace Tests\Feature\Actions;

use App\Actions\Trainer\CreateTrainerAction;
use App\DTOs\Trainer\CreateTrainerDTO;
use App\Models\Trainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTrainerActionTest extends TestCase
{
    use RefreshDatabase;

    private function validData(): array
    {
        return [
            'name' => 'Instrutor Teste',
            'email' => 'instrutor@teste.com',
            'document' => '12345678909',
            'phone' => '11999999999',
            'gender' => 'male',
        ];
    }

    public function test_creates_trainer_with_valid_data(): void
    {
        $action = app(CreateTrainerAction::class);
        $dto = CreateTrainerDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('trainers', [
            'name' => 'Instrutor Teste',
            'document' => '12345678909',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateTrainerAction::class);
        $dto = CreateTrainerDTO::from($this->validData());

        $result = $action->execute($dto);

        $this->assertSame('Instrutor criado com sucesso.', $result->message);
    }

    public function test_stores_optional_address_fields(): void
    {
        $data = array_merge($this->validData(), [
            'address' => 'Rua Teste',
            'address_number' => '100',
            'address_state' => 'SP',
            'address_city' => 'São Paulo',
            'address_postal_code' => '01001000',
        ]);

        $action = app(CreateTrainerAction::class);
        $dto = CreateTrainerDTO::from($data);

        $result = $action->execute($dto);

        $this->assertDatabaseHas('trainers', [
            'name' => 'Instrutor Teste',
            'address' => 'Rua Teste',
            'address_city' => 'São Paulo',
        ]);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateTrainerAction::class);
        $action->execute('not-a-dto');
    }
}
