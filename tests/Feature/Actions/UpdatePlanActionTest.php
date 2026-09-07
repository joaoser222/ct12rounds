<?php

namespace Tests\Feature\Actions;

use App\Actions\Plans\UpdatePlanAction;
use App\DTOs\Plans\UpdatePlanDTO;
use App\Models\Modality;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanModality;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePlanActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_updates_plan_with_new_price_and_duration(): void
    {
        $category = PlanCategory::query()->create(['name' => 'Basico', 'visibility' => 'visible']);
        $plan = Plan::query()->create([
            'name' => 'Plano Antigo',
            'plan_category_id' => $category->id,
            'price' => 100.0,
            'duration_months' => 1,
        ]);

        $action = app(UpdatePlanAction::class);
        $dto = new UpdatePlanDTO(
            id: $plan->id,
            name: 'Plano Novo',
            plan_category_id: $category->id,
            price: 250.0,
            duration_months: 3,
            plan_modalities: [],
        );

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'name' => 'Plano Novo',
            'price' => 250.0,
            'duration_months' => 3,
        ]);
    }

    public function test_updates_plan_modalities(): void
    {
        $category = PlanCategory::query()->create(['name' => 'Basico', 'visibility' => 'visible']);
        $modality1 = Modality::query()->create(['name' => 'Pilates', 'visibility' => 'visible']);
        $modality2 = Modality::query()->create(['name' => 'Yoga', 'visibility' => 'visible']);

        $plan = Plan::query()->create([
            'name' => 'Plano',
            'plan_category_id' => $category->id,
            'price' => 100.0,
            'duration_months' => 1,
        ]);
        PlanModality::query()->create(['plan_id' => $plan->id, 'modality_id' => $modality1->id]);

        $action = app(UpdatePlanAction::class);
        $dto = new UpdatePlanDTO(
            id: $plan->id,
            name: 'Plano',
            plan_category_id: $category->id,
            price: 100.0,
            duration_months: 1,
            plan_modalities: [$modality2->id],
        );

        $result = $action->execute($dto);

        $this->assertDatabaseMissing('plan_modalities', ['plan_id' => $plan->id, 'modality_id' => $modality1->id]);
        $this->assertDatabaseHas('plan_modalities', ['plan_id' => $plan->id, 'modality_id' => $modality2->id]);
    }

    public function test_throws_when_plan_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $category = PlanCategory::query()->create(['name' => 'X', 'visibility' => 'visible']);
        $action = app(UpdatePlanAction::class);
        $dto = new UpdatePlanDTO(
            id: 999999,
            name: 'Inexistente',
            plan_category_id: $category->id,
            price: 10.0,
            duration_months: 1,
            plan_modalities: [],
        );
        $action->execute($dto);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(UpdatePlanAction::class);
        $action->execute('not-a-dto');
    }
}
