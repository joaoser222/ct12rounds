<?php

namespace Tests\Feature\Actions;

use App\Actions\Plans\CreatePlanAction;
use App\DTOs\Plans\CreatePlanDTO;
use App\Models\Modality;
use App\Models\PlanCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePlanActionTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(): PlanCategory
    {
        return PlanCategory::query()->create(['name' => 'Premium', 'visibility' => 'visible']);
    }

    public function test_creates_a_plan_with_price_and_duration(): void
    {
        $category = $this->createCategory();
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano Gold',
            'plan_category_id' => $category->id,
            'description' => 'Plano completo',
            'price' => 99.9,
            'duration_months' => 1,
            'plan_modalities' => [],
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plans', [
            'name' => 'Plano Gold',
            'price' => 99.9,
            'duration_months' => 1,
            'cancellation_fee_percentage' => null,
        ]);
    }

    public function test_creates_a_plan_without_cancellation_fee_when_not_provided(): void
    {
        $category = $this->createCategory();
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano Anual',
            'plan_category_id' => $category->id,
            'description' => 'Plano completo',
            'price' => 199.99,
            'duration_months' => 12,
            'plan_modalities' => [],
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plans', [
            'name' => 'Plano Anual',
            'price' => 199.99,
            'duration_months' => 12,
            'cancellation_fee_percentage' => null,
        ]);
    }

    public function test_creates_a_plan_with_explicit_cancellation_fee_percentage(): void
    {
        $category = $this->createCategory();
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano Semestral',
            'plan_category_id' => $category->id,
            'description' => 'Plano completo',
            'price' => 159.0,
            'duration_months' => 6,
            'cancellation_fee_percentage' => 120.0,
            'plan_modalities' => [],
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plans', [
            'name' => 'Plano Semestral',
            'cancellation_fee_percentage' => 120.0,
        ]);
    }

    public function test_creates_a_plan_with_modalities(): void
    {
        $category = $this->createCategory();
        $modality = Modality::query()->create(['name' => 'Pilates', 'visibility' => 'visible']);
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano Pilates',
            'plan_category_id' => $category->id,
            'price' => 150.0,
            'duration_months' => 3,
            'plan_modalities' => [$modality->id],
        ]);

        $result = $action->execute($dto);

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('plan_modalities', [
            'plan_id' => $result->data->id,
            'modality_id' => $modality->id,
        ]);
    }

    public function test_returns_plan_with_loaded_relations(): void
    {
        $category = $this->createCategory();
        $action = app(CreatePlanAction::class);

        $dto = CreatePlanDTO::fromArray([
            'name' => 'Plano VIP',
            'plan_category_id' => $category->id,
            'price' => 500.0,
            'duration_months' => 6,
            'plan_modalities' => [],
        ]);

        $result = $action->execute($dto);

        $this->assertSame('Plano VIP', $result->data->name);
        $this->assertSame(500.0, $result->data->price);
        $this->assertSame(6, $result->data->duration_months);
        $this->assertCount(0, $result->data->modalities);
    }

    public function test_rejects_invalid_dto_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $action = app(CreatePlanAction::class);
        $action->execute('not-a-dto');
    }
}
