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

    public function test_convert_creates_client_with_site_source(): void
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

    public function test_convert_reuses_existing_client_by_document(): void
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

    public function test_convert_rejects_already_converted_lead(): void
    {
        $client = Client::factory()->create();
        $lead = HiringLead::factory()->create([
            'status' => HiringLeadStatus::CONVERTED,
            'client_id' => $client->id,
        ]);

        $result = app(ConvertHiringLeadAction::class)->execute($lead);

        $this->assertFalse($result->success);
    }

    public function test_convert_rejects_duplicate_document_on_unconverted_lead(): void
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
