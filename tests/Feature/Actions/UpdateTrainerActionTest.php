<?php

namespace Tests\Feature\Actions;

use App\Actions\Trainer\UpdateTrainerAction;
use App\DTOs\Trainer\UpdateTrainerDTO;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrainerActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_trainer_with_valid_data(): void
    {
        $trainer = Trainer::query()->create([
            'name' => 'Nome Antigo',
            'document' => '12345678909',
            'phone' => '11988887777',
            'gender' => 'male',
        ]);

        $action = app(UpdateTrainerAction::class);
        $dto = UpdateTrainerDTO::from([
            'id' => $trainer->id,
            'name' => 'Nome Novo',
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('trainers', [
            'id' => $trainer->id,
            'name' => 'Nome Novo',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $trainer = Trainer::query()->create([
            'name' => 'Atualizar',
            'document' => '12345678909',
            'phone' => '11988887777',
            'gender' => 'male',
        ]);

        $action = app(UpdateTrainerAction::class);
        $dto = UpdateTrainerDTO::from([
            'id' => $trainer->id,
            'name' => 'Atualizado',
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Instrutor atualizado com sucesso.', $result->message);
    }

    public function test_throws_when_trainer_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateTrainerAction::class);
        $dto = UpdateTrainerDTO::from([
            'id' => 999999,
            'name' => 'Inexistente',
        ]);
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateTrainerAction::class);
        $action->execute('not-a-dto');
    }
}
