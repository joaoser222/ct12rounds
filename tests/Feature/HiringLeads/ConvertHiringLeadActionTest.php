<?php

declare(strict_types=1);

namespace Tests\Feature\HiringLeads;

use App\Actions\HiringLeads\ConvertHiringLeadAction;
use App\Enums\ClientSource;
use App\Enums\HiringLeadStatus;
use App\Models\Client;
use App\Models\HiringLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConvertHiringLeadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_convert_cria_cliente_com_source_site(): void
    {
        $lead = HiringLead::factory()->contractFlow()->create();

        $result = app(ConvertHiringLeadAction::class)->execute($lead);

        $this->assertTrue($result->success);

        $this->assertDatabaseHas('clients', [
            'name' => $lead->name,
            'document' => $lead->document,
            'client_source' => ClientSource::SITE->value,
        ]);

        $lead->refresh();

        $this->assertEquals(HiringLeadStatus::CONVERTED, $lead->status);
        $this->assertNotNull($lead->client_id);
        $this->assertNotNull($lead->converted_at);
    }

    public function test_convert_reutiliza_cliente_existente_por_documento(): void
    {
        $existingClient = Client::factory()->create([
            'document' => '11122233344',
        ]);

        $lead = HiringLead::factory()->create([
            'document' => $existingClient->document,
        ]);

        $result = app(ConvertHiringLeadAction::class)->execute($lead);

        $this->assertTrue($result->success);

        $this->assertDatabaseCount('clients', 1);

        $lead->refresh();
        $this->assertEquals($existingClient->id, $lead->client_id);
    }

    public function test_convert_rejeita_lead_ja_convertido(): void
    {
        $client = Client::factory()->create();
        $lead = HiringLead::factory()->create([
            'status' => HiringLeadStatus::CONVERTED,
            'client_id' => $client->id,
        ]);

        $result = app(ConvertHiringLeadAction::class)->execute($lead);

        $this->assertFalse($result->success);
    }

    public function test_convert_rejeita_documento_duplicado_em_lead_nao_convertido(): void
    {
        HiringLead::factory()->create([
            'document' => '12312312300',
        ]);

        $lead = HiringLead::factory()->create([
            'document' => '12312312300',
        ]);

        $result = app(ConvertHiringLeadAction::class)->execute($lead);

        $this->assertFalse($result->success);
        $this->assertDatabaseCount('clients', 0);
    }
}
