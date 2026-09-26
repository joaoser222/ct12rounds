<?php

namespace Tests\Feature\Actions;

use App\Actions\Contracts\CreateContractAction;
use App\DTOs\Contracts\CreateContractDTO;
use App\Enums\ClientStatus;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateContractActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateContractAction $createContract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createContract = app(CreateContractAction::class);
    }

    public function test_contract_is_created_billed_by_credit_card_by_default(): void
    {
        $plan = $this->createPlan(100.0, 12);

        $this->createContract->execute(
            new CreateContractDTO(plan_id: $plan->id, installments: 12),
        );

        $contract = Contract::query()->firstOrFail();

        $this->assertSame(PaymentMethod::CREDIT_CARD, $contract->payment_method);
        $this->assertSame('pending', $contract->accepted_terms);
    }

    public function test_first_installment_is_eligible_for_gateway_sync(): void
    {
        $plan = $this->createPlan(100.0, 12);
        $client = Client::factory()->create(['status' => ClientStatus::ACTIVE->value]);

        $this->createContract->execute(
            new CreateContractDTO(plan_id: $plan->id, installments: 12),
        );

        $contract = Contract::query()->firstOrFail();
        $contract->update(['client_id' => $client->id]);

        $invoices = app(InvoiceGenerator::class)->generate($contract->fresh());

        $this->assertCount(12, $invoices);
        $this->assertSame(PaymentMethod::CREDIT_CARD, $invoices->first()->payment_method);
        $this->assertTrue($invoices->first()->shouldGenerateGatewayTransaction());
        $this->assertFalse($invoices->last()->shouldGenerateGatewayTransaction());
    }

    public function test_contract_gross_value_is_plan_price_times_installments(): void
    {
        $plan = $this->createPlan(100.0, 12);

        $this->createContract->execute(
            new CreateContractDTO(plan_id: $plan->id, installments: 3),
        );

        $contract = Contract::query()->firstOrFail();

        $this->assertSame(300.0, $contract->gross_value);
        $this->assertSame(0.0, $contract->discount_value);
        $this->assertSame(300.0, $contract->total);
    }

    private function createPlan(float $price, int $durationMonths): Plan
    {
        $category = PlanCategory::query()->create(['name' => 'Premium', 'visibility' => 'visible']);

        return Plan::query()->create([
            'name' => 'Plano Teste',
            'plan_category_id' => $category->id,
            'price' => $price,
            'duration_months' => $durationMonths,
        ]);
    }
}
