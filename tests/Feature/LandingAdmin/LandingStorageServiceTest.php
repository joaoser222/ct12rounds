<?php

namespace Tests\Feature\LandingAdmin;

use App\Models\LandingContent;
use App\Services\LandingStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingStorageServiceTest extends TestCase
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

    public function test_published_html_is_null_when_nothing_is_published(): void
    {
        $service = app(LandingStorageService::class);

        $this->assertNull($service->publishedHtml());
        $this->assertSame(LandingStorageService::STATUS_DRAFT, $service->status());
        $this->assertSame('', $service->draftHtml());
        $this->assertSame([], $service->project());
    }

    public function test_save_draft_writes_files_with_draft_status(): void
    {

        $service = app(LandingStorageService::class);

        $service->saveDraft(['pages' => []], '<style>.x{color:red}</style><div>Novo</div>');

        $this->assertSame(['pages' => []], $service->project());
        $this->assertSame('<style>.x{color:red}</style><div>Novo</div>', $service->draftHtml());
        $this->assertNull($service->publishedHtml());
        $this->assertSame(LandingStorageService::STATUS_DRAFT, $service->status());

        $meta = json_decode((string) Storage::disk('local')->get('landing/meta.json'), true);
        $this->assertSame(LandingStorageService::STATUS_DRAFT, $meta['status']);
    }

    public function test_publish_copies_draft_and_records_timestamp(): void
    {

        $service = app(LandingStorageService::class);

        $service->saveDraft(['pages' => []], '<div>Publicar</div>');
        $result = $service->publish();

        $this->assertSame('<div>Publicar</div>', $service->publishedHtml());
        $this->assertSame(LandingStorageService::STATUS_PUBLISHED, $service->status());
        $this->assertNotNull($service->publishedAt());
        $this->assertNotEmpty($result['publishedAt']);

        $meta = json_decode((string) Storage::disk('local')->get('landing/meta.json'), true);
        $this->assertSame(LandingStorageService::STATUS_PUBLISHED, $meta['status']);
        $this->assertSame($result['publishedAt'], $meta['published_at']);
    }

    public function test_publish_writes_versioned_snapshot_to_repo_path(): void
    {
        $service = app(LandingStorageService::class);

        $service->saveDraft(['pages' => []], '<div>Versão publicada</div>');
        $service->publish();

        $this->assertSame(
            '<div>Versão publicada</div>',
            file_get_contents((string) config('landing.snapshot_path')),
        );
    }

    public function test_publish_skips_snapshot_when_directory_is_not_writable(): void
    {
        config(['landing.snapshot_path' => '/proc/landing/published.html']);

        $service = app(LandingStorageService::class);

        $service->saveDraft(['pages' => []], '<div>Publicar</div>');
        $result = $service->publish();

        $this->assertSame('<div>Publicar</div>', $service->publishedHtml());
        $this->assertNotEmpty($result['publishedAt']);
    }

    public function test_legacy_database_content_is_imported_to_files(): void
    {

        LandingContent::factory()->create([
            'project' => ['pages' => ['page-1' => []]],
            'draft_html' => '<div>Rascunho legado</div>',
            'published_html' => '<div>Publicado legado</div>',
            'status' => LandingStorageService::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $service = app(LandingStorageService::class);

        $this->assertSame('<div>Publicado legado</div>', $service->publishedHtml());
        $this->assertSame(LandingStorageService::STATUS_PUBLISHED, $service->status());
        $this->assertNotNull($service->publishedAt());
        $this->assertSame('<div>Rascunho legado</div>', $service->draftHtml());
        $this->assertSame(['pages' => ['page-1' => []]], $service->project());
        $this->assertSame(
            '<div>Rascunho legado</div>',
            Storage::disk('local')->get('landing/draft.html'),
        );
        $this->assertSame(
            '<div>Publicado legado</div>',
            Storage::disk('local')->get('landing/published.html'),
        );
    }

    public function test_writes_after_legacy_import_no_longer_touch_database(): void
    {

        LandingContent::factory()->create([
            'project' => ['old' => true],
            'draft_html' => '<div>Legado</div>',
            'published_html' => '<div>Publicado</div>',
            'status' => LandingStorageService::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);

        $service = app(LandingStorageService::class);

        $this->assertSame('<div>Publicado</div>', $service->publishedHtml());

        $service->saveDraft(['new' => true], '<div>Novo</div>');

        $this->assertSame(['new' => true], $service->project());
        $this->assertSame('<div>Novo</div>', $service->draftHtml());
        $this->assertSame(LandingStorageService::STATUS_DRAFT, $service->status());

        $row = LandingContent::query()->first();
        $this->assertSame('<div>Legado</div>', $row->draft_html);
        $this->assertSame(LandingStorageService::STATUS_PUBLISHED, $row->status);
    }
}