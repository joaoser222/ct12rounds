<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteModeTest extends TestCase
{
    use RefreshDatabase;

    private function setMode(string $mode): void
    {
        Setting::query()->updateOrCreate(
            ['name' => 'site_mode'],
            [
                'label' => 'Estado público do site',
                'content' => $mode,
                'object_type' => 'options:off|No ar,construction|Em construção,maintenance|Em manutenção',
                'group' => 'site',
            ],
        );
    }

    public function test_guests_see_the_construction_placeholder_when_mode_is_construction(): void
    {
        $this->setMode('construction');

        $this->get(route('home'))
            ->assertStatus(503)
            ->assertSee('Estamos em construção');
    }

    public function test_guests_see_the_maintenance_placeholder_when_mode_is_maintenance(): void
    {
        $this->setMode('maintenance');

        $this->get(route('home'))
            ->assertStatus(503)
            ->assertSee('Estamos em manutenção');
    }

    public function test_guests_are_not_blocked_when_mode_is_off(): void
    {
        $this->setMode('off');

        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_guests_are_not_blocked_when_mode_is_absent(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_the_login_page_remains_accessible_during_maintenance(): void
    {
        $this->setMode('maintenance');

        $this->get(route('login'))->assertOk();
    }

    public function test_authenticated_users_bypass_the_placeholder(): void
    {
        $this->setMode('construction');

        Setting::query()->create([
            'name' => 'landing_enabled',
            'label' => 'Landing page ativa',
            'content' => '1',
            'object_type' => 'boolean',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))->assertOk();
    }
}
