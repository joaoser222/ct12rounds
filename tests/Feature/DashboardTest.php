<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page_from_the_home_page()
    {
        $response = $this->get(route('home'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_applications_home_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('home'));
        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('name', config('app.name'))
                ->where('auth.user.id', $user->id)
                ->where('auth.user.name', $user->name)
                ->where('auth.user.email', $user->email)
                ->where('auth.user.permissions_version', $user->permissionsVersion())
                ->missing('sidebarOpen')
            );
    }

    public function test_users_without_dashboard_permission_cannot_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_users_with_dashboard_permission_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'dashboard.view');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('auth.user.id', $user->id)
                ->has('charts.contractsByMonth.labels', 12)
                ->has('charts.receivedByMonth.labels', 12)
                ->has('charts.contractsByPlan.labels')
                ->where('charts.outstanding.pending', 0)
                ->where('charts.outstanding.overdue', 0)
            );
    }
}
