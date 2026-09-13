<?php

namespace Tests\Feature\LandingAdmin;

use App\Models\LandingAdminUser;
use App\Models\Setting;
use App\Services\LandingStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_view_the_editor(): void
    {
        Storage::fake('local');

        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->get(route('landing-admin.editor'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing-admin/Editor')
                ->where('status', 'draft')
                ->where('draftHtml', '')
                ->where('viewUrl', route('home'))
                ->where('templateSeed.ctaText', 'Começar agora')
                ->where('templateSeed.whatsappUrl', 'https://wa.me/5563981019160')
                ->where('templateSeed.subtitle', 'Boxe, Kickboxing e Jiu-Jitsu com a Metodologia 12 Rounds. Treinamentos que transformam — do iniciante ao atleta competitivo.'));
    }

    public function test_editor_seed_reflects_configured_landing_settings(): void
    {
        Storage::fake('local');

        Setting::query()->create([
            'name' => 'landing_hero_subtitle',
            'label' => 'Landing - Subtítulo',
            'content' => 'Pré-cadastre-se para a aula gratuita.',
            'object_type' => 'textarea',
        ]);

        Setting::query()->create([
            'name' => 'landing_main_cta_text',
            'label' => 'Landing - Texto do CTA',
            'content' => 'Quero minha aula',
            'object_type' => 'text',
        ]);

        Setting::query()->create([
            'name' => 'landing_whatsapp_phone',
            'label' => 'Landing - WhatsApp',
            'content' => '5563981011111',
            'object_type' => 'text',
        ]);

        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->get(route('landing-admin.editor'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing-admin/Editor')
                ->where('templateSeed.subtitle', 'Pré-cadastre-se para a aula gratuita.')
                ->where('templateSeed.ctaText', 'Quero minha aula')
                ->where('templateSeed.whatsappUrl', 'https://wa.me/5563981011111'));
    }

    public function test_admin_can_save_a_draft(): void
    {
        Storage::fake('local');

        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->put(route('landing-admin.api.content'), [
                'project' => ['pages' => []],
                'html' => '<div>Novo layout</div>',
                'css' => '.novo{color:red}',
            ])->assertOk();

        $this->assertSame(
            '<style>.novo{color:red}</style><div>Novo layout</div>',
            Storage::disk('local')->get('landing/draft.html'),
        );
        $this->assertSame(['pages' => []], json_decode((string) Storage::disk('local')->get('landing/project.json'), true));

        $meta = json_decode((string) Storage::disk('local')->get('landing/meta.json'), true);
        $this->assertSame(LandingStorageService::STATUS_DRAFT, $meta['status']);
    }

    public function test_draft_requires_html_and_project(): void
    {
        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->put(route('landing-admin.api.content'), ['project' => []])
            ->assertSessionHasErrors('html');

        $this->actingAs($admin, 'landing_admin')
            ->put(route('landing-admin.api.content'), ['html' => '<div>X</div>'])
            ->assertSessionHasErrors('project');
    }

    public function test_admin_can_publish_the_draft(): void
    {
        Storage::fake('local');

        config([
            'landing.snapshot_path' => storage_path('framework/testing/landing-snapshot/published.html'),
        ]);

        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->put(route('landing-admin.api.content'), [
                'project' => ['pages' => []],
                'html' => '<div>Publicar</div>',
                'css' => '',
            ])->assertOk();

        $this->actingAs($admin, 'landing_admin')
            ->post(route('landing-admin.api.publish'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(
            '<div>Publicar</div>',
            Storage::disk('local')->get('landing/published.html'),
        );

        $meta = json_decode((string) Storage::disk('local')->get('landing/meta.json'), true);
        $this->assertSame(LandingStorageService::STATUS_PUBLISHED, $meta['status']);
        $this->assertNotEmpty($meta['published_at'] ?? '');
    }

    public function test_guests_cannot_access_api_endpoints(): void
    {
        $this->put(route('landing-admin.api.content'), [
            'project' => [],
            'html' => '<div>X</div>',
        ])->assertRedirect(route('landing-admin.login'));

        $this->post(route('landing-admin.api.publish'))
            ->assertRedirect(route('landing-admin.login'));
    }

    public function test_admin_can_upload_an_image(): void
    {
        Storage::fake('public');

        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->post(route('landing-admin.api.images'), [
                'file' => UploadedFile::fake()->create('ct12.jpg', 100, 'image/jpeg'),
            ])->assertOk()
                ->assertJsonStructure(['url']);

        $this->assertCount(1, Storage::disk('public')->allFiles('landing'));
    }

    public function test_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');

        $admin = LandingAdminUser::factory()->create();

        $this->actingAs($admin, 'landing_admin')
            ->post(route('landing-admin.api.images'), [
                'file' => UploadedFile::fake()->create('nota.txt', 100),
            ])->assertSessionHasErrors('file');

        $this->assertCount(0, Storage::disk('public')->allFiles('landing'));
    }

    public function test_published_assets_are_served_by_the_storage_route(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('landing/logo.png', 'binary-data');

        $this->get(route('landing.storage', ['path' => 'logo.png']))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_storage_route_returns_not_found_for_missing_assets(): void
    {
        Storage::fake('public');

        $this->get(route('landing.storage', ['path' => 'missing.png']))
            ->assertNotFound();
    }

    public function test_storage_route_blocks_path_traversal(): void
    {
        Storage::fake('public');

        $encoded = $this->get('/storage/landing/..%2F..%2F.env');
        $plain = $this->get('/storage/landing/../.env');

        $this->assertContains($encoded->getStatusCode(), [403, 404]);
        $this->assertContains($plain->getStatusCode(), [403, 404]);
    }

}
