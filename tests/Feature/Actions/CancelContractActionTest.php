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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelContractActionTest extends TestCase
{
    use RefreshDatabase;

    private function createPlan(float $price, int $durationMonths, ?float $cancellationFee): Plan
    {
        $category = PlanCategory::query()->create(['name' => 'Premium', 'visibility' => 'visible']);

        return Plan::query()->create([
            'name' => 'Plano Teste',
            'plan_category_id' => $category->id,
            'price' => $price,
            'duration_months' => $durationMonths,
            'cancellation_fee' => $cancellationFee,
        ]);
    }

    private function createContract(Plan $plan, Client $client): Contract
    {
        return Contract::query()->create([
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
            'client_id' => $client->id,
        ]);
    }

    public function test_cancel_contract_creates_pending_cancellation_fee_invoice(): void
    {
        $plan = $this->createPlan(199.99, 12, 199.99);
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
            'gross_value' => 199.99,
            'status' => 'pending',
        ]);
    }

    public function test_cancel_contract_does_not_create_fee_invoice_when_plan_has_no_cancellation_fee(): void
    {
        $plan = $this->createPlan(99.9, 1, null);
        $client = Client::factory()->create();
        $contract = $this->createContract($plan, $client);

        $action = app(CancelContractAction::class);
        $action->execute(new CancelContractDTO(contract_id: $contract->id));

        $this->assertDatabaseCount('invoices', 0);
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
        $plan = $this->createPlan(100.0, 12, 100.0);
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
            'gross_value' => 100.0,
            'status' => InvoiceStatus::PENDING->value,
        ]);
    }
}