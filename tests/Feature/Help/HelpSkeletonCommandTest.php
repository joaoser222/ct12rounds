<?php

declare(strict_types=1);

namespace Tests\Feature\Help;

use App\Services\Help\HelpService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HelpSkeletonCommandTest extends TestCase
{
    private string $helpDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->helpDirectory = base_path('docs/help');
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->helpDirectory.'/_drafts');

        parent::tearDown();
    }

    public function test_it_writes_one_draft_per_module(): void
    {
        $this->artisan('help:skeleton')->assertSuccessful();

        $drafts = File::files($this->helpDirectory.'/_drafts');

        $this->assertNotEmpty($drafts);
        $this->assertContains('receivables.md', array_map(fn ($file): string => $file->getFilename(), $drafts));
    }

    public function test_draft_states_facts_taken_from_the_controller(): void
    {
        $this->artisan('help:skeleton')->assertSuccessful();

        $draft = File::get($this->helpDirectory.'/_drafts/receivables.md');

        $this->assertStringContainsString('/receivables', $draft);
        $this->assertStringContainsString('`due_date`, `payment_date`, `status`', $draft);
        $this->assertStringContainsString('`overdued`', $draft);
        $this->assertStringContainsString('TODO', $draft);
    }

    public function test_draft_marks_read_only_screens(): void
    {
        $this->artisan('help:skeleton')->assertSuccessful();

        $this->assertStringContainsString(
            'Somente leitura',
            File::get($this->helpDirectory.'/_drafts/gateway_payments.md'),
        );
    }

    public function test_drafts_stay_out_of_the_chat_corpus(): void
    {
        $this->artisan('help:skeleton')->assertSuccessful();

        $this->assertFileExists($this->helpDirectory.'/_drafts/payables.md');
        $this->assertArrayNotHasKey(
            'payables',
            app(HelpService::class)->guides(),
            'Um rascunho com TODO não pode entrar no contexto do chat.',
        );
    }

    public function test_it_keeps_existing_drafts_without_force(): void
    {
        File::ensureDirectoryExists($this->helpDirectory.'/_drafts');
        File::put($this->helpDirectory.'/_drafts/payables.md', 'rascunho em andamento');

        $this->artisan('help:skeleton')->assertSuccessful();

        $this->assertSame('rascunho em andamento', File::get($this->helpDirectory.'/_drafts/payables.md'));
    }

    public function test_force_overwrites_existing_drafts(): void
    {
        File::ensureDirectoryExists($this->helpDirectory.'/_drafts');
        File::put($this->helpDirectory.'/_drafts/payables.md', 'rascunho em andamento');

        $this->artisan('help:skeleton --force')->assertSuccessful();

        $this->assertStringContainsString(
            '# Contas a Pagar',
            File::get($this->helpDirectory.'/_drafts/payables.md'),
        );
    }
}