<?php

namespace Tests\Feature\Actions;

use App\Models\Client;
use App\Models\Modality;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\ProductUnity;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllerDelegationTest extends TestCase
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

    public function test_client_store_persists_via_create_client_action(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.create');

        $this->actingAs($user)->post(route('clients.store'), [
            'name' => 'Delegation Client',
            'email' => 'delegation@test.com',
            'phone' => '11999999999',
            'document' => '12345678901',
            'gender' => 'M',
            'birth_date' => '1990-01-01',
            'legal_representative' => false,
            'address_postal_code' => '01001000',
            'address_number' => '100',
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'email' => 'delegation@test.com',
            'status' => 'active',
        ]);
    }

    public function test_client_update_persists_via_update_client_action(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'clients.update');
        $client = Client::factory()->create(['document' => '12345678901']);

        $this->actingAs($user)->put(route('clients.update', $client), [
            'name' => 'Updated via Action',
            'email' => 'updated@test.com',
            'phone' => '11988887777',
            'document' => '12345678901',
            'gender' => 'F',
            'birth_date' => '1995-05-15',
            'legal_representative' => false,
            'address_postal_code' => '01001000',
            'address_number' => '100',
        ])->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated via Action',
        ]);
    }

    public function test_modality_store_persists_via_create_modality_action(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.create');

        $this->actingAs($user)->postJson(route('modalities.store'), ['name' => 'Pilates'])
            ->assertCreated();

        $this->assertDatabaseHas('modalities', ['name' => 'Pilates']);
    }

    public function test_modality_update_persists_via_update_modality_action(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'modalities.update');
        $modality = Modality::query()->create(['name' => 'Original', 'visibility' => 'visible']);

        $this->actingAs($user)->putJson(route('modalities.update', $modality), ['name' => 'Atualizada'])
            ->assertOk();

        $this->assertDatabaseHas('modalities', ['id' => $modality->id, 'name' => 'Atualizada']);
    }

    public function test_plan_store_persists_via_create_plan_action(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'plans.create');
        $category = PlanCategory::query()->create(['name' => 'Basica', 'visibility' => 'visible']);

        $this->actingAs($user)->post(route('plans.store'), [
            'name' => 'Plano Delegation',
            'plan_category_id' => $category->id,
            'price' => 100.0,
            'duration_months' => 1,
            'plan_modalities' => [],
        ])->assertRedirect(route('plans.index'));

        $plan = Plan::query()->where('name', 'Plano Delegation')->first();
        $this->assertNotNull($plan);
        $this->assertSame(100.0, $plan->price);
        $this->assertSame(1, $plan->duration_months);
    }

    public function test_product_store_persists_via_create_product_action(): void
    {
        ProductUnity::query()->create(['name' => 'Unidade', 'code' => 'UN']);
        $user = User::factory()->create();
        $this->grantPermission($user, 'products.create');

        $this->actingAs($user)->postJson(route('products.store'), [
            'name' => 'Produto Delegation',
            'purchase_price' => 10,
            'sale_price' => 20,
            'product_type' => 'merchandise',
            'product_unity' => 'UN',
        ])->assertCreated();

        $this->assertDatabaseHas('products', ['name' => 'Produto Delegation']);
    }

    public function test_settings_update_persists_via_update_settings_action(): void
    {
        Setting::query()->create([
            'name' => 'company_name',
            'label' => 'Nome',
            'content' => 'old',
            'object_type' => 'text',
        ]);

        $user = User::factory()->create();
        $this->grantPermission($user, 'settings.update');

        $this->actingAs($user)->putJson(route('settings.update'), [
            'settings' => ['company_name' => 'New Company'],
        ])->assertRedirect();

        $this->assertSame('New Company', Setting::query()->where('name', 'company_name')->value('content'));
    }

    public function test_user_store_persists_via_save_user_action(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'users.create');

        $this->actingAs($user)->post(route('users.store'), [
            'name' => 'Novo Delegation',
            'email' => 'novo.delegation@test.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'novo.delegation@test.com', 'name' => 'Novo Delegation']);
    }

    public function test_role_update_persists_via_update_role_permissions_action(): void
    {
        $role = Role::query()->create(['name' => 'manager', 'description' => 'Gerente']);
        $permission = Permission::query()->create(['name' => 'clients.view', 'description' => 'Ver']);

        $user = User::factory()->create();
        $this->grantPermission($user, 'users.update');

        $this->actingAs($user)->putJson(route('roles.update', $role), [
            'permission_ids' => [$permission->id],
        ])->assertOk();

        $this->assertDatabaseHas('permission_role', [
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }
}
