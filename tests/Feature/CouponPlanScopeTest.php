<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\HiringLead;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithPermissions;
use Tests\TestCase;

class CouponPlanScopeTest extends TestCase
{
    use InteractsWithPermissions, RefreshDatabase;

    public function test_a_coupon_without_plans_applies_to_every_plan(): void
    {
        $coupon = $this->createCoupon('GERAL10');
        $plan = $this->createPlan('Mensal');

        $this->assertTrue($coupon->appliesToPlan($plan->id));
    }

    public function test_a_coupon_applies_to_the_plans_it_is_scoped_to(): void
    {
        $mensal = $this->createPlan('Mensal');
        $anual = $this->createPlan('Anual');
        $semestral = $this->createPlan('Semestral');

        $coupon = $this->createCoupon('MENSAL20');
        $coupon->plans()->sync([$mensal->id, $semestral->id]);

        $coupon->refresh();

        $this->assertTrue($coupon->appliesToPlan($mensal->id));
        $this->assertTrue($coupon->appliesToPlan($semestral->id));
        $this->assertFalse($coupon->appliesToPlan($anual->id));
    }

    public function test_the_wizard_rejects_a_coupon_scoped_to_another_plan(): void
    {
        $user = $this->userWith('contracts.create');
        $mensal = $this->createPlan('Mensal');
        $anual = $this->createPlan('Anual');
        $coupon = $this->createCoupon('MENSAL20');
        $coupon->plans()->sync([$mensal->id]);

        $this->actingAs($user)->post(route('contracts.store'), [
            'plan_id' => $anual->id,
            'installments' => 12,
            'coupon_id' => $coupon->id,
        ])->assertSessionHasErrors('coupon_id');

        $this->assertDatabaseCount('contracts', 0);
    }

    public function test_the_wizard_accepts_a_coupon_scoped_to_the_selected_plan(): void
    {
        $user = $this->userWith('contracts.create');
        $mensal = $this->createPlan('Mensal');
        $anual = $this->createPlan('Anual');
        $coupon = $this->createCoupon('MENSAL20');
        $coupon->plans()->sync([$mensal->id, $anual->id]);

        $this->actingAs($user)->post(route('contracts.store'), [
            'plan_id' => $mensal->id,
            'installments' => 1,
            'coupon_id' => $coupon->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contracts', [
            'plan_id' => $mensal->id,
            'coupon_id' => $coupon->id,
        ]);
    }

    public function test_a_coupon_inherited_from_a_pre_registration_respects_the_plan_scope(): void
    {
        $user = $this->userWith('contracts.create');
        $mensal = $this->createPlan('Mensal');
        $anual = $this->createPlan('Anual');
        $coupon = $this->createCoupon('MENSAL20');
        $coupon->plans()->sync([$mensal->id]);

        $lead = HiringLead::query()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '5511888888888',
            'status' => 'new',
            'source' => 'site',
            'visibility' => 'visible',
            'coupon_id' => $coupon->id,
        ]);

        // The plan chosen in the wizard is not the one the coupon covers, so the
        // coupon is not carried over.
        $this->actingAs($user)->post(route('contracts.store'), [
            'plan_id' => $anual->id,
            'installments' => 12,
            'lead_id' => $lead->id,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contracts', [
            'plan_id' => $anual->id,
            'coupon_id' => null,
        ]);

        $lead->refresh();
        $this->assertNotNull($lead->contract_id);
    }

    public function test_the_wizard_exposes_the_scope_of_each_coupon(): void
    {
        $user = $this->userWith('contracts.create');
        $this->createPlan('Mensal');
        $this->createPlan('Anual');

        $global = $this->createCoupon('GERAL10');
        $scoped = $this->createCoupon('MENSAL20');
        $scoped->plans()->sync([Plan::query()->where('name', 'Mensal')->value('id')]);

        $this->actingAs($user)->get(route('contracts.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('contracts/Details')
                ->where('options.coupons.0.plan_ids', [])
                ->where('options.coupons.0.title', 'GERAL10')
                ->where('options.coupons.1.plan_ids', [Plan::query()->where('name', 'Mensal')->value('id')])
                ->where('options.coupons.1.title', 'MENSAL20 — Mensal')
            );

        $this->assertNotNull($global->id);
    }

    public function test_saving_a_coupon_stores_its_plan_scope(): void
    {
        $user = $this->userWith('coupons.create');
        $mensal = $this->createPlan('Mensal');
        $anual = $this->createPlan('Anual');

        $this->actingAs($user)->post(route('coupons.store'), [
            'code' => 'NEGOCIO30',
            'percent' => 30,
            // discount_limit is NOT NULL and duration is an integer column,
            // even though both rules say nullable string.
            'discount_limit' => 0,
            'duration' => '1',
            'plan_ids' => [$mensal->id, $anual->id],
        ])->assertSessionHasNoErrors();

        $coupon = Coupon::query()->where('code', 'NEGOCIO30')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$mensal->id, $anual->id],
            $coupon->plans->pluck('id')->all()
        );
    }

    public function test_clearing_the_plan_scope_makes_the_coupon_global_again(): void
    {
        $user = $this->userWith('coupons.create', 'coupons.update');
        $mensal = $this->createPlan('Mensal');
        $anual = $this->createPlan('Anual');

        $coupon = $this->createCoupon('MENSAL20');
        $coupon->plans()->sync([$mensal->id]);

        $this->actingAs($user)->put(route('coupons.update', $coupon), [
            'plan_ids' => [],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($coupon->refresh()->appliesToPlan($anual->id));
    }

    public function test_updating_a_coupon_without_touching_plans_keeps_the_scope(): void
    {
        $user = $this->userWith('coupons.create', 'coupons.update');
        $mensal = $this->createPlan('Mensal');
        $anual = $this->createPlan('Anual');

        $coupon = $this->createCoupon('MENSAL20');
        $coupon->plans()->sync([$mensal->id]);

        $this->actingAs($user)->put(route('coupons.update', $coupon), [
            'percent' => 25,
        ])->assertSessionHasNoErrors();

        $coupon->refresh();

        $this->assertEquals(25.0, $coupon->percent);
        $this->assertFalse($coupon->appliesToPlan($anual->id));
    }

    private function createCoupon(string $code): Coupon
    {
        return Coupon::query()->create([
            'code' => $code,
            'percent' => 10,
            'discount_limit' => 100,
            'duration' => 1,
            'expiration_date' => '2026-12-31',
            'visibility' => 'visible',
        ]);
    }

    private function createPlan(string $name): Plan
    {
        $category = PlanCategory::query()->firstOrCreate(
            ['name' => 'Premium'],
            ['visibility' => 'visible'],
        );

        return Plan::query()->create([
            'name' => $name,
            'plan_category_id' => $category->id,
            'price' => 100,
            'duration_months' => 12,
            'visibility' => 'visible',
        ]);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        $role = \App\Models\Role::query()->create([
            'name' => 'tester-'.uniqid(),
            'description' => 'Teste',
        ]);

        foreach ($permissions as $permission) {
            $this->grantPermission($user, $permission);
        }

        $user->update(['role_id' => $role->id]);

        return $user;
    }
}
