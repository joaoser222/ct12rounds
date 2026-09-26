<?php

namespace Tests\Feature;

use App\Actions\Contracts\RevertContractAcceptanceAction;
use App\Enums\BillableStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Contract;
use App\Models\GatewayAccount;
use App\Models\GatewayCustomer;
use App\Models\Invoice;
use App\Models\Sale;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\Repositories\Contracts\GatewayPaymentRepositoryInterface;
use App\Services\Billing\InvoiceGenerator;
use App\Services\Gateway\GatewayBillingOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GatewayBillingOrchestratorTest extends TestCase
{
    use RefreshDatabase;

    private function makeClient(): Client
    {
        return Client::factory()->create();
    }

    private function makeSale(Client $client): Sale
    {
        return Sale::query()->create([
            'gross_value' => 200,
            'discount_value' => 0,
            'total' => 200,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::PIX,
            'first_due_date' => '2026-09-01',
            'installments' => 2,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);
    }

    private function makeReceivable(Sale $sale, Client $client, int $installment = 1): Invoice
    {
        return Invoice::query()->create([
            'invoice_type' => 'standard',
            'due_date' => date('Y-m-d'),
            'payment_method' => PaymentMethod::PIX,
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => $installment,
            'status' => InvoiceStatus::PENDING,
            'operation_type' => OperationType::RECEIVABLE,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ]);
    }

    public function test_sync_invoice_returns_false_for_contract_without_accepted_terms(): void
    {
        $client = $this->makeClient();
        $contract = Contract::query()->create([
            'plan_name' => 'Mensal',
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'payment_method' => PaymentMethod::CREDIT_CARD->value,
            'first_due_date' => now()->toDateString(),
            'installments' => 1,
            'accepted_terms' => 'pending',
            'status' => BillableStatus::OPEN,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        $invoice = Invoice::query()->create([
            'invoice_type' => 'standard',
            'due_date' => now()->toDateString(),
            'payment_method' => PaymentMethod::CREDIT_CARD,
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING,
            'operation_type' => OperationType::RECEIVABLE,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $contract->id,
            'billable_type' => 'contract',
        ]);

        $gateway = $this->mock(PaymentGatewayAdapter::class);
        $gateway->shouldNotReceive('createPayment');

        $repo = $this->mock(GatewayPaymentRepositoryInterface::class);
        $repo->shouldReceive('existsWhere')->andReturn(false);

        $orchestrator = $this->makeOrchestrator($gateway, $repo);

        $this->assertFalse($orchestrator->syncInvoice($invoice));
    }

    private function makeOrchestrator(?PaymentGatewayAdapter $gateway = null, ?GatewayPaymentRepositoryInterface $repo = null): GatewayBillingOrchestrator
    {
        return new GatewayBillingOrchestrator(
            app(InvoiceGenerator::class),
            $gateway ?? $this->mock(PaymentGatewayAdapter::class),
            $repo ?? $this->mock(GatewayPaymentRepositoryInterface::class),
            app(RevertContractAcceptanceAction::class),
        );
    }

    public function test_sync_invoice_succeeds_and_updates_status(): void
    {
        $client = $this->makeClient();
        $sale = $this->makeSale($client);
        $invoice = $this->makeReceivable($sale, $client);

        $gateway = $this->mock(PaymentGatewayAdapter::class);
        $gatewayCustomer = new GatewayCustomer();
        $gateway->shouldReceive('createCustomer')->once()->andReturn($gatewayCustomer);
        $gateway->shouldReceive('createPayment')->once();

        $repo = $this->mock(GatewayPaymentRepositoryInterface::class);
        $repo->shouldReceive('existsWhere')->andReturnFalse();

        $orchestrator = $this->makeOrchestrator(gateway: $gateway, repo: $repo);

        $this->assertTrue($orchestrator->syncInvoice($invoice));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => InvoiceStatus::WAITING->value,
        ]);
    }

    public function test_sync_invoice_returns_false_for_payable_operation(): void
    {
        $client = $this->makeClient();
        $sale = $this->makeSale($client);

        $invoice = Invoice::query()->create([
            'invoice_type' => 'standard',
            'due_date' => '2026-09-01',
            'payment_method' => PaymentMethod::PIX,
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING,
            'operation_type' => OperationType::PAYABLE,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ]);

        $this->assertFalse($this->makeOrchestrator()->syncInvoice($invoice));
    }

    public function test_sync_invoice_returns_false_when_gateway_payment_already_exists(): void
    {
        $client = $this->makeClient();
        $sale = $this->makeSale($client);
        $invoice = $this->makeReceivable($sale, $client);

        $repo = $this->mock(GatewayPaymentRepositoryInterface::class);
        $repo->shouldReceive('existsWhere')
            ->with(['invoice_id' => $invoice->id])
            ->andReturnTrue();

        $this->assertFalse($this->makeOrchestrator(repo: $repo)->syncInvoice($invoice));
    }

    public function test_sync_invoice_returns_false_when_not_uses_gateway_method(): void
    {
        $client = $this->makeClient();
        $sale = $this->makeSale($client);

        $invoice = Invoice::query()->create([
            'invoice_type' => 'standard',
            'due_date' => '2026-09-01',
            'payment_method' => PaymentMethod::CASH,
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING,
            'operation_type' => OperationType::RECEIVABLE,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ]);

        $this->assertFalse($this->makeOrchestrator()->syncInvoice($invoice));
    }

    public function test_sync_invoice_returns_false_when_not_should_generate(): void
    {
        $client = $this->makeClient();
        $sale = $this->makeSale($client);

        $invoice = Invoice::query()->create([
            'invoice_type' => 'standard',
            'due_date' => '2026-12-25',
            'payment_method' => PaymentMethod::PIX,
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING,
            'operation_type' => OperationType::RECEIVABLE,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ]);

        $this->assertFalse($this->makeOrchestrator()->syncInvoice($invoice));
    }

    public function test_generate_and_sync_returns_invoices_even_when_sync_fails(): void
    {
        $client = $this->makeClient();
        $sale = $this->makeSale($client);

        $gateway = $this->mock(PaymentGatewayAdapter::class);
        $gateway->shouldReceive('createCustomer')->andThrow(new \RuntimeException('Gateway down'));

        $repo = $this->mock(GatewayPaymentRepositoryInterface::class);
        $repo->shouldReceive('existsWhere')->andReturnFalse();

        $orchestrator = $this->makeOrchestrator(gateway: $gateway, repo: $repo);

        $invoices = $orchestrator->generateAndSync($sale);

        $this->assertCount(2, $invoices);
        $this->assertDatabaseHas('invoices', [
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ]);
    }
}
