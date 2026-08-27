<?php

declare(strict_types=1);

namespace Tests\Feature\HiringLeads;

use App\Models\HiringLead;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HiringLeadAccessTest extends TestCase
{
    use RefreshDatabase;

    private function givePermission(User $user, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['name' => $permissionName, 'description' => $permissionName],
        );

        $user->permissions()->attach($permission);
    }

    public function test_hiring_leads_index_requires_view_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/hiring-leads')->assertForbidden();
    }

    public function test_hiring_leads_index_loads_with_view_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'hiring_leads.view');

        $this->actingAs($user)->get('/hiring-leads')->assertOk();
    }

    public function test_hiring_leads_does_not_expose_creation_or_update_routes(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'hiring_leads.view');

        $this->actingAs($user)
            ->get(route('hiring-leads.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('hiring_leads/Index')
                ->missing('routes.create')
                ->missing('routes.store')
                ->missing('routes.update')
            );

        $this->assertFalse(Route::has('hiring-leads.create'));
        $this->assertFalse(Route::has('hiring-leads.store'));
        $this->assertFalse(Route::has('hiring-leads.update'));

        $lead = HiringLead::factory()->create();

        $this->actingAs($user)->post('/hiring-leads', ['name' => 'Bloqueado'])->assertMethodNotAllowed();
        $this->actingAs($user)->put("/hiring-leads/{$lead->id}", ['name' => 'Bloqueado'])->assertMethodNotAllowed();
    }

    public function test_hiring_leads_details_is_read_only_and_keeps_convert_route(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'hiring_leads.view');
        $this->givePermission($user, 'hiring_leads.update');

        $lead = HiringLead::factory()->create();

        $this->actingAs($user)
            ->get(route('hiring-leads.show', $lead))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('hiring_leads/Details')
                ->where('hiring-lead.id', $lead->id)
                ->has('routes.convert')
                ->missing('routes.store')
                ->missing('routes.update')
            );
    }
}
