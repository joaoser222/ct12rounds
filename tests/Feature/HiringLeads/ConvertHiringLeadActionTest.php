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
            'email' => $lead->email,
            'phone' => $lead->phone,
            'client_source' => ClientSource::SITE->value,
        ]);

        $lead->refresh();

        $this->assertEquals(HiringLeadStatus::CONVERTED, $lead->status);
        $this->assertNotNull($lead->client_id);
        $this->assertNotNull($lead->converted_at);
    }

    public function test_convert_creates_client_without_document_from_site_lead(): void
    {
        $lead = HiringLead::factory()->create([
            'document' => null,
        ]);

        $result = app(ConvertHiringLeadAction::class)->execute($lead);

        $this->assertTrue($result->success);

        $this->assertDatabaseHas('clients', [
            'name' => $lead->name,
            'email' => $lead->email,
            'document' => null,
            'client_source' => ClientSource::SITE->value,
        ]);
    }

    public function test_convert_reuses_existing_client_by_email_and_phone(): void
    {
        $existingClient = Client::factory()->create([
            'email' => 'cliente@example.com',
            'phone' => '11999999999',
        ]);

        $lead = HiringLead::factory()->create([
            'email' => $existingClient->email,
            'phone' => $existingClient->phone,
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

    public function test_convert_rejects_duplicate_email_and_phone_on_unconverted_lead(): void
    {
        HiringLead::factory()->create([
            'email' => 'duplicado@example.com',
            'phone' => '11988887777',
        ]);

        $lead = HiringLead::factory()->create([
            'email' => 'duplicado@example.com',
            'phone' => '11988887777',
        ]);

        $result = app(ConvertHiringLeadAction::class)->execute($lead);

        $this->assertFalse($result->success);
        $this->assertDatabaseCount('clients', 0);
    }
}
