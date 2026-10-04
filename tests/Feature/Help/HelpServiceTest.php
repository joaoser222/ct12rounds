<?php

declare(strict_types=1);

namespace Tests\Feature\Help;

use App\Services\Help\HelpIndex;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use App\Services\Help\HelpService;
use Tests\TestCase;

class HelpServiceTest extends TestCase
{
    public function test_every_guide_is_readable(): void
    {
        $guides = app(HelpService::class)->guides();

        $this->assertNotEmpty($guides);
        $this->assertArrayHasKey('contas-a-receber', $guides);

        foreach ($guides as $slug => $body) {
            $this->assertStringContainsString('#', $body, "O guia {$slug} precisa ter título.");
        }
    }

    public function test_relevant_guides_match_question_terms(): void
    {
        $service = app(HelpService::class);

        $this->assertArrayHasKey('contas-a-receber', $service->relevantGuides('Como registro o pagamento de uma parcela?'));
        $this->assertArrayHasKey('contratos', $service->relevantGuides('Como crio um contrato para o cliente?'));
    }

    public function test_relevant_guides_ignore_accents(): void
    {
        $service = app(HelpService::class);

        $this->assertArrayHasKey(
            'contratos',
            $service->relevantGuides('como faço uma baixa de contrato'),
            'Busca sem acento tem que achar o guia com acento.',
        );
    }

    public function test_relevant_guides_returns_nothing_for_empty_question(): void
    {
        $this->assertSame([], app(HelpService::class)->relevantGuides('oi'));
    }

    public function test_relevant_guides_are_capped(): void
    {
        $service = app(HelpService::class);

        $this->assertLessThanOrEqual(
            3,
            count($service->relevantGuides('cliente contrato turma fidelidade pagamento gateway nota fiscal')),
        );
    }

    public function test_system_prompt_forbids_answering_with_data(): void
    {
        $prompt = app(HelpService::class)->systemPrompt();

        $this->assertStringContainsString('Nunca responda valores', $prompt);
        $this->assertStringContainsString('português do Brasil', $prompt);
        $this->assertStringNotContainsString('ferramenta', mb_strtolower($prompt));
    }

    public function test_context_is_empty_when_no_guide_matches(): void
    {
        $this->assertSame('', app(HelpService::class)->contextFor('xyzzy'));
    }
}

class HelpIndexTest extends TestCase
{
    public function test_every_filtered_link_declares_its_search_field(): void
    {
        foreach (HelpIndex::links() as $slug => $link) {
            if (! str_contains($link['url'], '?search=')) {
                continue;
            }

            parse_str((string) parse_url($link['url'], PHP_URL_QUERY), $query);

            $this->assertArrayHasKey(
                'searchField',
                $query,
                "O link {$slug} filtra sem searchField e busca na coluna errada.",
            );
        }
    }

    public function test_receivable_status_links_use_enum_values(): void
    {
        $links = HelpIndex::links();

        $this->assertStringContainsString('search=overdued', $links['receivables_overdued']['url']);
        $this->assertStringContainsString('search=waiting', $links['receivables_waiting']['url']);
    }

    public function test_every_link_points_to_a_registered_route(): void
    {
        $paths = collect(Route::getRoutes()->getRoutes())
            ->map(fn (RoutingRoute $route): string => '/'.ltrim($route->uri(), '/'))
            ->all();

        foreach (HelpIndex::links() as $slug => $link) {
            $path = '/'.ltrim((string) parse_url($link['url'], PHP_URL_PATH), '/');

            $this->assertContains($path, $paths, "O link {$slug} aponta para uma tela que não existe.");
        }
    }

    public function test_link_instructions_list_every_link(): void
    {
        $instructions = HelpIndex::linkInstructions();

        foreach (HelpIndex::links() as $link) {
            $this->assertStringContainsString($link['url'], $instructions);
        }
    }
}