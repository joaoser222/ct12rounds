<?php

namespace Tests\Feature;

use App\Models\LoyaltyLevel;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LoyaltyLevelModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function grantPermission(User $user, string $permission): void
    {
        $perm = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($perm);
    }

    public function test_guests_are_redirected_from_loyalty_levels_index(): void
    {
        $response = $this->get(route('loyalty-levels.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_loyalty_levels_index(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'loyalty_levels.view');

        $response = $this->actingAs($user)->get(route('loyalty-levels.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('loyalty_levels/Index')
            ->has('loyalty-levels.data')
            ->has('routes')
        );
    }

    public function test_authenticated_users_can_visit_loyalty_level_create(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'loyalty_levels.create');

        $response = $this->actingAs($user)->get(route('loyalty-levels.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('loyalty_levels/Details')
            ->where('loyalty-level', null)
            ->where('id', 'new')
            ->has('routes')
        );
    }

    public function test_loyalty_level_index_requires_view_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('loyalty-levels.index'));

        $response->assertForbidden();
    }

    public function test_users_can_create_loyalty_level(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'loyalty_levels.create');

        $response = $this->actingAs($user)->post(route('loyalty-levels.store'), [
            'name' => 'Novato',
            'min_months' => 0,
            'color' => '#95a5a6',
            'description' => 'Nivel inicial',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('loyalty_levels', [
            'name' => 'Novato',
            'min_months' => 0,
        ]);
    }

    public function test_users_can_update_loyalty_level(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'loyalty_levels.update');
        $level = LoyaltyLevel::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($user)->put(route('loyalty-levels.update', $level), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('loyalty_levels', [
            'id' => $level->id,
            'name' => 'New Name',
        ]);
    }

    public function test_users_can_delete_loyalty_level(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'loyalty_levels.delete');
        $level = LoyaltyLevel::factory()->create();

        $response = $this->actingAs($user)->delete(route('loyalty-levels.destroy'), [
            'items' => [$level->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('loyalty_levels', ['id' => $level->id]);
    }
}
