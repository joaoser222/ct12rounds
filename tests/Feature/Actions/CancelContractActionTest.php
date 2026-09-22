<?php

namespace Tests\Feature\Actions;

use App\Actions\Contracts\CancelContractAction;
use App\DTOs\Contracts\CancelContractDTO;
use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelContractActionTest extends TestCase
{
    use RefreshDatabase;

    private function createPlan(float $price, int $durationMonths, ?float $cancellationFeePercentage): Plan
    {
        $category = PlanCategory::query()->create(['name' => 'Premium', 'visibility' => 'visible']);

        return Plan::query()->create([
            'name' => 'Plano Teste',
            'plan_category_id' => $category->id,
            'price' => $price,
            'duration_months' => $durationMonths,
            'cancellation_fee_percentage' => $cancellationFeePercentage,
        ]);
    }

    private function createContract(Plan $plan, Client $client, ?string $firstDueDate = null): Contract
    {
        return Contract::query()->create([
            'plan_name' => $plan->name,
            'plan_id' => $plan->id,
            'gross_value' => $plan->price * $plan->duration_months,
            'discount_value' => 0,
            'total' => $plan->price * $plan->duration_months,
            'payment_method' => 'cash',
            'first_due_date' => $firstDueDate ?? now()->toDateString(),
            'installments' => $plan->duration_months,
            'accepted_terms' => 'accepted',
            'visibility' => 'visible',
            'status' => 'open',
            'client_id' => $client->id,
        ]);
    }

    private function createInvoice(Contract $contract, Client $client, string $dueDate, InvoiceStatus $status, float $paidValue = 0): Invoice
    {
        return Invoice::query()->create([
            'operation_type' => 'receivable',
            'invoice_type' => 'standard',
            'due_date' => $dueDate,
            'payment_method' => 'cash',
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => $paidValue,
            'installment_number' => 1,
            'status' => $status->value,
            'visibility' => 'visible',
            'holder_type' => 'client',
            'holder_id' => $client->id,
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
        ]);
    }

    public function test_cancel_contract_creates_pending_cancellation_fee_invoice(): void
    {
        $plan = $this->createPlan(199.99, 12, 25.0);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client);

        $action = app(CancelContractAction::class);
        $result = $action->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertTrue($result->success);
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'status' => 'canceled',
        ]);
        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'gross_value' => 599.97,
            'status' => 'pending',
        ]);
    }

    public function test_cancel_contract_applies_default_fee_of_25_percent_of_the_total_contract_value_when_plan_has_no_cancellation_fee(): void
    {
        $plan = $this->createPlan(100.0, 1, null);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client);

        app(CancelContractAction::class)->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'gross_value' => 25.0,
            'status' => InvoiceStatus::PENDING->value,
            'annotations' => 'Multa de cancelamento',
        ]);
    }

    public function test_cancel_contract_uses_percentage_configured_in_settings(): void
    {
        Setting::query()->create([
            'name' => 'cancellation_fee_percentage',
            'label' => 'Percentual da multa de cancelamento (%)',
            'content' => '10',
            'object_type' => 'number',
        ]);

        $plan = $this->createPlan(100.0, 12, null);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client);

        app(CancelContractAction::class)->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'gross_value' => 120.0,
            'status' => InvoiceStatus::PENDING->value,
        ]);
    }

    public function test_cancel_contract_plan_cancellation_fee_percentage_overrides_default_percentage(): void
    {
        Setting::query()->create([
            'name' => 'cancellation_fee_percentage',
            'label' => 'Percentual da multa de cancelamento (%)',
            'content' => '10',
            'object_type' => 'number',
        ]);

        $plan = $this->createPlan(100.0, 12, 50.0);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client);

        app(CancelContractAction::class)->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'gross_value' => 600.0,
            'status' => InvoiceStatus::PENDING->value,
        ]);
    }

    public function test_cancel_contract_applies_fee_to_the_total_contract_value_even_when_fully_paid(): void
    {
        $plan = $this->createPlan(100.0, 1, null);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client);

        $this->createInvoice($contract, $client, '2026-09-09', InvoiceStatus::PAID, 100.0);

        app(CancelContractAction::class)->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'gross_value' => 25.0,
            'status' => InvoiceStatus::PENDING->value,
            'annotations' => 'Multa de cancelamento',
        ]);
    }

    public function test_cancel_contract_does_not_create_fee_invoice_without_client(): void
    {
        $plan = $this->createPlan(199.99, 12, 199.99);
        $contract = Contract::query()->create([
            'plan_name' => $plan->name,
            'plan_id' => $plan->id,
            'gross_value' => $plan->price * $plan->duration_months,
            'discount_value' => 0,
            'total' => $plan->price * $plan->duration_months,
            'payment_method' => 'cash',
            'first_due_date' => now()->toDateString(),
            'installments' => 12,
            'accepted_terms' => 'accepted',
            'visibility' => 'visible',
            'status' => 'open',
        ]);

        $action = app(CancelContractAction::class);
        $action->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_cancel_contract_cancels_existing_pending_invoices_but_keeps_fee_invoice(): void
    {
        $plan = $this->createPlan(100.0, 12, 25.0);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client);

        Invoice::query()->create([
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
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
        ]);

        $action = app(CancelContractAction::class);
        $action->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'installment_number' => 1,
            'status' => InvoiceStatus::CANCELED->value,
        ]);
        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'contract',
            'billable_id' => $contract->id,
            'gross_value' => 300.0,
            'status' => InvoiceStatus::PENDING->value,
        ]);
    }

    public function test_cancel_contract_sets_fee_due_date_to_next_open_installment(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-30'));

        $plan = $this->createPlan(100.0, 12, 25.0);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client, '2026-09-09');

        $this->createInvoice($contract, $client, '2026-09-09', InvoiceStatus::OVERDUED);
        $this->createInvoice($contract, $client, '2026-10-09', InvoiceStatus::PENDING);

        app(CancelContractAction::class)->execute(new CancelContractDTO(contract_id: $contract->id));

        $feeInvoice = Invoice::query()
            ->where('billable_id', $contract->id)
            ->where('billable_type', 'contract')
            ->where('status', InvoiceStatus::PENDING->value)
            ->first();

        $this->assertNotNull($feeInvoice);
        $this->assertSame('2026-10-09', $feeInvoice->due_date->format('Y-m-d'));
    }

    public function test_cancel_contract_falls_back_to_next_billing_date_when_no_open_invoices(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-30'));

        $plan = $this->createPlan(100.0, 12, 25.0);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client, '2026-09-09');

        app(CancelContractAction::class)->execute(new CancelContractDTO(contract_id: $contract->id));

        $feeInvoice = Invoice::query()
            ->where('billable_id', $contract->id)
            ->where('billable_type', 'contract')
            ->first();

        $this->assertNotNull($feeInvoice);
        $this->assertSame('2026-10-09', $feeInvoice->due_date->format('Y-m-d'));
    }

    public function test_cancel_contract_uses_current_month_billing_date_when_still_ahead(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-05'));

        $plan = $this->createPlan(100.0, 12, 25.0);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client, '2026-09-09');

        app(CancelContractAction::class)->execute(new CancelContractDTO(contract_id: $contract->id));

        $feeInvoice = Invoice::query()
            ->where('billable_id', $contract->id)
            ->where('billable_type', 'contract')
            ->first();

        $this->assertNotNull($feeInvoice);
        $this->assertSame('2026-09-09', $feeInvoice->due_date->format('Y-m-d'));
    }
}