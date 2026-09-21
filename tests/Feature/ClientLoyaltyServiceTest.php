<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LoyaltyLevel;
use App\Services\Loyalty\ClientLoyaltyService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientLoyaltyServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClientLoyaltyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ClientLoyaltyService();
    }

    public function test_client_with_no_invoices_gets_zero_streak(): void
    {
        $client = Client::factory()->create();

        $result = $this->service->calculateStreak($client);

        $this->assertSame(0, $result['streak_months']);
        $this->assertNull($result['loyalty_level_id']);
        $this->assertNull($result['loyalty_since']);
    }

    public function test_client_with_continuous_invoices_gets_correct_streak(): void
    {
        $this->seed(\Database\Seeders\LoyaltyLevelSeeder::class);
        $client = Client::factory()->create();
        $now = Carbon::now();

        for ($i = 0; $i < 8; $i++) {
            $client->invoices()->create([
                'operation_type' => 'receivable',
                'invoice_type' => 'standard',
                'due_date' => $now->copy()->subMonths($i)->startOfMonth(),
                'gross_value' => 100,
                'status' => 'paid',
                'visibility' => 'visible',
                'holder_type' => 'client',
                'holder_id' => $client->id,
            ]);
        }

        $result = $this->service->calculateStreak($client);

        $this->assertSame(8, $result['streak_months']);
        $this->assertNotNull($result['loyalty_level_id']);
        $this->assertNotNull($result['loyalty_since']);
    }

    public function test_gap_of_three_months_resets_streak(): void
    {
        $this->seed(\Database\Seeders\LoyaltyLevelSeeder::class);
        $client = Client::factory()->create();
        $now = Carbon::now();

        for ($i = 0; $i < 3; $i++) {
            $client->invoices()->create([
                'operation_type' => 'receivable',
                'invoice_type' => 'standard',
                'due_date' => $now->copy()->subMonths($i)->startOfMonth(),
                'gross_value' => 100,
                'status' => 'paid',
                'visibility' => 'visible',
                'holder_type' => 'client',
                'holder_id' => $client->id,
            ]);
        }

        for ($i = 8; $i < 12; $i++) {
            $client->invoices()->create([
                'operation_type' => 'receivable',
                'invoice_type' => 'standard',
                'due_date' => $now->copy()->subMonths($i)->startOfMonth(),
                'gross_value' => 100,
                'status' => 'paid',
                'visibility' => 'visible',
                'holder_type' => 'client',
                'holder_id' => $client->id,
            ]);
        }

        $result = $this->service->calculateStreak($client);

        $this->assertSame(3, $result['streak_months']);
    }

    public function test_gap_of_two_months_does_not_reset(): void
    {
        $this->seed(\Database\Seeders\LoyaltyLevelSeeder::class);
        $client = Client::factory()->create();
        $now = Carbon::now();

        $monthsToCreate = [0, 1, 4, 5, 6];

        foreach ($monthsToCreate as $i) {
            $client->invoices()->create([
                'operation_type' => 'receivable',
                'invoice_type' => 'standard',
                'due_date' => $now->copy()->subMonths($i)->startOfMonth(),
                'gross_value' => 100,
                'status' => 'paid',
                'visibility' => 'visible',
                'holder_type' => 'client',
                'holder_id' => $client->id,
            ]);
        }

        $result = $this->service->calculateStreak($client);

        $this->assertSame(5, $result['streak_months']);
    }

    public function test_unpaid_invoices_do_not_break_streak(): void
    {
        $this->seed(\Database\Seeders\LoyaltyLevelSeeder::class);
        $client = Client::factory()->create();
        $now = Carbon::now();

        for ($i = 0; $i < 5; $i++) {
            $client->invoices()->create([
                'operation_type' => 'receivable',
                'invoice_type' => 'standard',
                'due_date' => $now->copy()->subMonths($i)->startOfMonth(),
                'gross_value' => 100,
                'status' => $i === 2 ? 'pending' : 'paid',
                'visibility' => 'visible',
                'holder_type' => 'client',
                'holder_id' => $client->id,
            ]);
        }

        $result = $this->service->calculateStreak($client);

        $this->assertSame(5, $result['streak_months']);
    }

    public function test_refresh_client_updates_model(): void
    {
        $this->seed(\Database\Seeders\LoyaltyLevelSeeder::class);
        $client = Client::factory()->create();
        $now = Carbon::now();

        for ($i = 0; $i < 7; $i++) {
            $client->invoices()->create([
                'operation_type' => 'receivable',
                'invoice_type' => 'standard',
                'due_date' => $now->copy()->subMonths($i)->startOfMonth(),
                'gross_value' => 100,
                'status' => 'paid',
                'visibility' => 'visible',
                'holder_type' => 'client',
                'holder_id' => $client->id,
            ]);
        }

        $this->service->refreshClient($client->fresh());

        $client->refresh();

        $this->assertSame(7, $client->loyalty_streak_months);
        $this->assertNotNull($client->loyalty_level_id);
        $this->assertNotNull($client->loyalty_since);
    }
}
