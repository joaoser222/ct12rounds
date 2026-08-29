<?php

namespace Tests\Feature\Actions;

use App\Actions\Modalities\UpdateModalityAction;
use App\DTOs\Modalities\UpdateModalityDTO;
use App\Models\Modality;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateModalityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_a_modality_with_valid_data(): void
    {
        $modality = Modality::query()->create(['name' => 'Pilates', 'visibility' => 'visible']);
        $action = app(UpdateModalityAction::class);

        $dto = new UpdateModalityDTO(id: $modality->id, name: 'Pilates Avançado');
        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('modalities', ['id' => $modality->id, 'name' => 'Pilates Avançado']);
    }

    public function test_updates_a_modality_with_color_and_icon(): void
    {
        $modality = Modality::query()->create(['name' => 'Yoga', 'visibility' => 'visible']);
        $action = app(UpdateModalityAction::class);

        $dto = new UpdateModalityDTO(
            id: $modality->id,
            name: 'Yoga Terapêutico',
            color: '#00AAFF',
            icon: 'modalities/xyz789.jpg',
        );
        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('modalities', [
            'id' => $modality->id,
            'name' => 'Yoga Terapêutico',
            'color' => '#00AAFF',
            'icon' => 'modalities/xyz789.jpg',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $modality = Modality::query()->create(['name' => 'Yoga', 'visibility' => 'visible']);
        $action = app(UpdateModalityAction::class);

        $dto = new UpdateModalityDTO(id: $modality->id, name: 'Yoga Terapêutico');
        $result = $action->execute($dto);

        $this->assertSame('Modalidade atualizada com sucesso.', $result->message);
    }

    public function test_throws_when_modality_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $action = app(UpdateModalityAction::class);
        $dto = new UpdateModalityDTO(id: 999999, name: 'Inexistente');
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdateModalityAction::class);
        $action->execute('not-a-dto');
    }
}
