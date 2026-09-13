<?php

namespace Tests\Feature\LandingAdmin;

use App\Services\LandingStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrateLandingOriginalCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $snapshotDirectory = storage_path('framework/testing/landing-snapshot');

        if (! is_dir($snapshotDirectory)) {
            mkdir($snapshotDirectory, 0775, true);
        }

        config([
            'landing.snapshot_path' => $snapshotDirectory.'/published.html',
        ]);
    }

    public function test_command_publishes_the_original_design_with_local_assets(): void
    {
        $this->assertFileExists(resource_path('views/index.html'));

        $exitCode = Artisan::call('landing:migrate-original');

        $this->assertSame(0, $exitCode);

        $published = (string) Storage::disk('local')->get('landing/published.html');

        $this->assertStringStartsWith('<style>', $published);
        $this->assertStringContainsString('/landing-assets/img/', $published);
        $this->assertStringContainsString('https://wa.me/', $published);
        $this->assertStringNotContainsString('r2.dev', $published);
        $this->assertStringNotContainsString('<script', $published);
        $this->assertSame('[]', (string) Storage::disk('local')->get('landing/project.json'));

        $meta = json_decode((string) Storage::disk('local')->get('landing/meta.json'), true);
        $this->assertSame(LandingStorageService::STATUS_PUBLISHED, $meta['status']);

        $snapshot = (string) config('landing.snapshot_path');
        $this->assertFileExists($snapshot);
        $this->assertSame($published, (string) file_get_contents($snapshot));

        $seedPath = resource_path('js/pages/landing-admin/landingTemplate.json');
        $this->assertFileExists($seedPath);

        $seed = json_decode((string) file_get_contents($seedPath), true);
        $this->assertIsArray($seed);
        $this->assertStringStartsWith('*{margin:0;padding:0;box-sizing:border-box', $seed['css']);
        $this->assertStringContainsString('/landing-assets/img/', $seed['html']);
        $this->assertStringNotContainsString('r2.dev', $seed['html']);
        $this->assertStringContainsString('>[[subtitle]]<', $seed['html']);
        $this->assertStringContainsString('>[[ctaText]]<', $seed['html']);
        $this->assertStringContainsString('[[whatsappUrl]]', $seed['html']);
    }

    public function test_command_fails_when_the_original_file_is_missing(): void
    {
        $exitCode = Artisan::call('landing:migrate-original', [
            'file' => resource_path('views/nonexistent.html'),
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertNull(Storage::disk('local')->get('landing/published.html'));
    }
}