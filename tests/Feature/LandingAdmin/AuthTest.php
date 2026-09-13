<?php

namespace Tests\Feature\LandingAdmin;

use App\Models\LandingAdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_admin_login_screen_can_be_rendered(): void
    {
        $this->get(route('landing-admin.login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing-admin/Login'));
    }

    public function test_landing_admin_can_authenticate(): void
    {
        $admin = LandingAdminUser::factory()->create();

        $this->post(route('landing-admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('landing-admin.editor', absolute: false));

        $this->assertAuthenticated('landing_admin');
    }

    public function test_landing_admin_cannot_authenticate_with_invalid_password(): void
    {
        $admin = LandingAdminUser::factory()->create();

        $this->post(route('landing-admin.login.store'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest('landing_admin');
    }

    public function test_system_users_cannot_authenticate_on_the_panel(): void
    {
        $user = User::factory()->create();

        $this->post(route('landing-admin.login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest('landing_admin');
    }

    public function test_landing_admin_can_logout(): void
    {
        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->post(route('landing-admin.logout'))
            ->assertRedirect(route('landing-admin.login'));

        $this->assertGuest('landing_admin');
    }

    public function test_guests_are_redirected_to_panel_login(): void
    {
        $this->get(route('landing-admin.editor'))
            ->assertRedirect(route('landing-admin.login'));
    }

    public function test_landing_admin_login_is_rate_limited(): void
    {
        $admin = LandingAdminUser::factory()->create();

        $throttleKey = 'landing_admin:'.Str::transliterate(Str::lower($admin->email).'|127.0.0.1');

        foreach (range(1, 5) as $_) {
            RateLimiter::hit($throttleKey);
        }

        $this->post(route('landing-admin.login.store'), [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_authenticated_system_user_is_redirected_to_dashboard_when_visiting_system_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('login'))
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_authenticated_landing_admin_is_redirected_to_panel_when_visiting_panel_login(): void
    {
        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->get(route('landing-admin.login'))
            ->assertRedirect('/landing-admin');
    }
}