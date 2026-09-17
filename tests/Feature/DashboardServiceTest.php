<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Services\DashboardService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createContract(string $planName, ?int $planId = null, ?Client $client = null): Contract
    {
        return Contract::query()->create([
            'plan_name' => $planName,
            'plan_id' => $planId,
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'payment_method' => 'cash',
            'first_due_date' => now()->toDateString(),
            'installments' => 1,
            'accepted_terms' => 'accepted',
            'visibility' => 'visible',
            'status' => 'open',
            'client_id' => $client?->id,
        ]);
    }

    private function createPlan(string $name): Plan
    {
        $category = PlanCategory::query()->create(['name' => $name.' Categoria', 'visibility' => 'visible']);

        return Plan::query()->create([
            'name' => $name,
            'plan_category_id' => $category->id,
            'price' => 100,
            'duration_months' => 12,
        ]);
    }

    private function createInvoice(Client $client, array $attributes = []): Invoice
    {
        return Invoice::query()->create(array_merge([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => 'pending',
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => $client->id,
        ], $attributes));
    }

    public function test_contracts_by_month_counts_contracts_and_fills_missing_months(): void
    {
        $this->travelTo(CarbonImmutable::parse('2025-08-10 10:00:00'));
        $this->createContract('Fora da janela');

        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00'));
        $this->createContract('Mensal');
        $this->createContract('Mensal');

        $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00:00'));
        $this->createContract('Trimestral');

        $result = app(DashboardService::class)->contractsByMonth();

        $this->assertCount(12, $result['labels']);
        $this->assertCount(12, $result['contracts']);
        $this->assertSame('12/2025', $result['labels'][0]);
        $this->assertSame('11/2026', $result['labels'][11]);

        $index = array_flip($result['labels']);

        $this->assertSame(2, $result['contracts'][$index['09/2026']]);
        $this->assertSame(1, $result['contracts'][$index['11/2026']]);
        $this->assertSame(0, $result['contracts'][$index['10/2026']]);
        $this->assertSame(3, array_sum($result['contracts']));
    }

    public function test_received_by_month_sums_only_paid_receivables(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00'));

        $client = Client::factory()->create();

        $this->createInvoice($client, [
            'status' => 'paid',
            'paid_value' => 150.5,
            'payment_date' => '2026-09-05',
        ]);
        $this->createInvoice($client, [
            'status' => 'paid',
            'paid_value' => 50,
            'payment_date' => '2026-08-20',
        ]);
        $this->createInvoice($client, ['status' => 'pending']);
        $this->createInvoice($client, [
            'operation_type' => 'payable',
            'status' => 'paid',
            'paid_value' => 999,
            'payment_date' => '2026-09-10',
        ]);

        $result = app(DashboardService::class)->receivedByMonth();

        $this->assertCount(12, $result['labels']);
        $this->assertCount(12, $result['values']);

        $index = array_flip($result['labels']);

        $this->assertSame(150.5, $result['values'][$index['09/2026']]);
        $this->assertSame(50.0, $result['values'][$index['08/2026']]);
        $this->assertSame(0.0, $result['values'][$index['07/2026']]);
    }

    public function test_contracts_by_plan_uses_plan_name_with_fallback_to_contract_plan_name(): void
    {
        $plan = $this->createPlan('Anual');

        $this->createContract('Mensal');
        $this->createContract('Mensal');
        $this->createContract($plan->name, $plan->id);

        $result = app(DashboardService::class)->contractsByPlan();

        $this->assertSame(['Mensal', 'Anual'], $result['labels']);
        $this->assertSame([2, 1], $result['values']);
    }

    public function test_receivable_outstanding_splits_pending_and_overdue(): void
    {
        $client = Client::factory()->create();

        $this->createInvoice($client, ['status' => 'pending']);
        $this->createInvoice($client, ['status' => 'waiting', 'gross_value' => 200]);
        $this->createInvoice($client, [
            'status' => 'pending',
            'gross_value' => 100,
            'paid_value' => 40,
        ]);
        $this->createInvoice($client, ['status' => 'overdued', 'gross_value' => 300]);
        $this->createInvoice($client, [
            'status' => 'paid',
            'paid_value' => 100,
            'payment_date' => now()->toDateString(),
        ]);
        $this->createInvoice($client, ['status' => 'canceled']);
        $this->createInvoice($client, ['operation_type' => 'payable', 'status' => 'pending']);

        $result = app(DashboardService::class)->receivableOutstanding();

        $this->assertSame(360.0, $result['pending']);
        $this->assertSame(300.0, $result['overdue']);
    }
}
