<?php

declare(strict_types=1);

namespace Tests\Feature\HiringLeads;

use App\Enums\HiringLeadSource;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicHiringLandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_landing_is_disabled(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_see_home_when_landing_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('auth.user.id', $user->id)
            );
    }

    public function test_landing_renders_static_page_with_configurable_settings_when_enabled(): void
    {
        $this->enableLanding();

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Landing')
                ->where('subtitle', 'Pré-cadastre-se e converse com a nossa equipe sobre a melhor forma de começar.')
                ->where('ctaText', 'Começar agora')
                ->where('whatsappUrl', 'https://wa.me/5563981019160')
                ->where('title', 'CT 12 Rounds — Centro de Treinamento')
                ->where('storeUrl', route('public.landing.store'))
                ->missing('contentHtml'));
    }

    public function test_landing_store_creates_lead_with_site_source(): void
    {
        $this->enableLanding();

        $this->post(route('public.landing.store'), [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'accepted' => true,
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('hiring_leads', [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'document' => null,
            'source' => HiringLeadSource::SITE->value,
        ]);
    }

    public function test_landing_store_requires_terms_acceptance(): void
    {
        $this->enableLanding();

        $this->post(route('public.landing.store'), [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
        ])->assertSessionHasErrors('accepted');

        $this->assertDatabaseCount('hiring_leads', 0);
    }

    public function test_landing_store_redirects_without_creating_when_disabled(): void
    {
        $this->post(route('public.landing.store'), [
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'accepted' => true,
        ])->assertRedirect(route('home'));

        $this->assertDatabaseCount('hiring_leads', 0);
    }

    public function test_landing_renders_contract_cta_with_register_url(): void
    {
        $this->enableLanding();

        $this->get(route('home', ['contract' => 'token-abc']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Landing')
                ->where('contract', 'token-abc')
                ->where('registerUrl', route('public.register', ['contract' => 'token-abc'])));
    }

    private function enableLanding(): void
    {
        Setting::query()->create([
            'name' => 'landing_enabled',
            'label' => 'Landing page ativa',
            'content' => '1',
            'object_type' => 'boolean',
        ]);

        Setting::query()->create([
            'name' => 'landing_hero_title',
            'label' => 'Landing - Título principal',
            'content' => 'Treine em paz. Evolua sem medo.',
            'object_type' => 'text',
        ]);

        Setting::query()->create([
            'name' => 'landing_hero_subtitle',
            'label' => 'Landing - Subtítulo',
            'content' => 'Pré-cadastre-se e converse com a nossa equipe sobre a melhor forma de começar.',
            'object_type' => 'textarea',
        ]);
    }
}