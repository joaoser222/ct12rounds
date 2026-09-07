<?php

namespace Tests\Feature;

use App\Models\Modality;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanModality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        return [
            'name' => 'Plano Gold',
            'plan_category_id' => $planCategory->id,
            'description' => 'Plano com duração variável.',
            'price' => 99.9,
            'duration_months' => 1,
            'plan_modalities' => [],
        ];
    }

    public function test_authenticated_users_can_create_plan_with_required_fields_and_empty_modalities(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.create');

        $response = $this->actingAs($user)->post(route('plans.store'), $this->validPayload());

        $response->assertRedirect(route('plans.index'));

        $plan = Plan::query()->with('modalities')->firstOrFail();

        $this->assertSame('Plano Gold', $plan->name);
        $this->assertSame(99.9, $plan->price);
        $this->assertSame(1, $plan->duration_months);
        $this->assertCount(0, $plan->modalities);
    }

    public function test_price_is_required_when_creating_a_plan(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.create');

        $payload = $this->validPayload();
        unset($payload['price']);

        $response = $this->actingAs($user)->post(route('plans.store'), $payload);

        $response->assertSessionHasErrors(['price']);
        $this->assertDatabaseCount('plans', 0);
    }

    public function test_duration_months_is_required_when_creating_a_plan(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.create');

        $payload = $this->validPayload();
        unset($payload['duration_months']);

        $response = $this->actingAs($user)->post(route('plans.store'), $payload);

        $response->assertSessionHasErrors(['duration_months']);
        $this->assertDatabaseCount('plans', 0);
    }

    public function test_authenticated_users_can_update_plan_and_clear_specific_modalities(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.update');

        $planCategory = PlanCategory::query()->create([
            'name' => 'Premium',
            'visibility' => 'visible',
        ]);

        $firstModality = Modality::query()->create([
            'name' => 'Musculação',
            'visibility' => 'visible',
        ]);

        $secondModality = Modality::query()->create([
            'name' => 'Pilates',
            'visibility' => 'visible',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plano Inicial',
            'plan_category_id' => $planCategory->id,
            'description' => 'Descrição inicial',
            'price' => 100.0,
            'duration_months' => 1,
            'visibility' => 'visible',
        ]);

        PlanModality::query()->create([
            'plan_id' => $plan->id,
            'modality_id' => $firstModality->id,
        ]);

        PlanModality::query()->create([
            'plan_id' => $plan->id,
            'modality_id' => $secondModality->id,
        ]);

        $response = $this->actingAs($user)->put(route('plans.update', $plan), [
            'name' => 'Plano Atualizado',
            'plan_category_id' => $planCategory->id,
            'description' => 'Sem modalidades específicas.',
            'price' => 150.0,
            'duration_months' => 3,
            'plan_modalities' => [],
        ]);

        $response->assertRedirect(route('plans.index'));

        $plan->refresh();

        $this->assertSame('Plano Atualizado', $plan->name);
        $this->assertSame(150.0, $plan->price);
        $this->assertSame(3, $plan->duration_months);
        $this->assertDatabaseCount('plan_modalities', 0);
    }
}
