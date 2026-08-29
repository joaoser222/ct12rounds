<?php

namespace Tests\Feature\Actions;

use App\Actions\Modalities\CreateModalityAction;
use App\DTOs\Modalities\CreateModalityDTO;
use App\Models\Modality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateModalityActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_modality_with_valid_data(): void
    {
        $action = app(CreateModalityAction::class);

        $dto = new CreateModalityDTO(name: 'Pilates');
        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('modalities', ['name' => 'Pilates']);
    }

    public function test_creates_a_modality_with_color_and_icon(): void
    {
        $action = app(CreateModalityAction::class);

        $dto = new CreateModalityDTO(
            name: 'Pilates',
            color: '#FF5733',
            icon: 'modalities/abc123.png',
        );
        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('modalities', [
            'name' => 'Pilates',
            'color' => '#FF5733',
            'icon' => 'modalities/abc123.png',
        ]);
    }

    public function test_returns_success_message(): void
    {
        $action = app(CreateModalityAction::class);

        $dto = new CreateModalityDTO(name: 'Yoga');
        $result = $action->execute($dto);

        $this->assertSame('Modalidade criada com sucesso.', $result->message);
    }

    public function test_returns_modality_model_in_data(): void
    {
        $action = app(CreateModalityAction::class);

        $dto = new CreateModalityDTO(name: 'Crossfit');
        $result = $action->execute($dto);

        $this->assertInstanceOf(Modality::class, $result->data);
        $this->assertSame('Crossfit', $result->data->name);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreateModalityAction::class);
        $action->execute('not-a-dto');
    }
}
