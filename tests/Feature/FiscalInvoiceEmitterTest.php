<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\Gateway\InvoiceStatus as GatewayInvoiceStatus;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayCustomer;
use App\Models\GatewayInvoice;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\Invoice;
use App\Models\Sale;
use App\Services\Gateway\FiscalInvoiceEmitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class FiscalInvoiceEmitterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function makeAccount(array $overrides = []): GatewayAccount
    {
        return GatewayAccount::query()->create(array_merge([
            'name' => 'Asaas',
            'description' => 'Asaas',
            'invoicing_enabled' => true,
            'invoicing_configured' => true,
            'invoicing_supported' => true,
            'settings' => [
                'api_key' => 'test-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
                'invoicing' => [
                    'service_description' => 'Mensalidade',
                    'municipal_service_code' => '1.01',
                ],
            ],
            'visibility' => 'visible',
        ], $overrides));
    }

    private function makeClient(): Client
    {
        return Client::factory()->create();
    }

    private function makeReceivable(Client $client, array $overrides = []): Invoice
    {
        $sale = Sale::query()->create([
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::PIX,
            'installments' => 1,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        return Invoice::query()->create(array_merge([
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
            'operation_type' => OperationType::RECEIVABLE,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => 'client',
            'billable_id' => $sale->id,
            'billable_type' => 'sale',
        ], $overrides));
    }

    private function makeGatewayPayment(Invoice $invoice, GatewayAccount $account): GatewayPayment
    {
        $postback = GatewayPostback::query()->create([
            'postback_event' => 'PAYMENT_CREATED',
            'postback_type' => 'payment',
            'payload' => ['event' => 'PAYMENT_CREATED'],
            'status' => 'success',
            'gateway_account_id' => $account->id,
        ]);

        $customer = GatewayCustomer::query()->create([
            'gateway_reference_key' => 'cus_123',
            'holder_id' => $invoice->holder_id,
            'holder_type' => $invoice->holder_type,
            'gateway_account_id' => $account->id,
            'gateway_postback_id' => $postback->id,
        ]);

        return GatewayPayment::query()->create([
            'gateway_account_id' => $account->id,
            'invoice_id' => $invoice->id,
            'gateway_customer_id' => $customer->id,
            'gateway_postback_id' => $postback->id,
            'gateway_reference_key' => 'pay_123',
            'payment_method' => PaymentMethod::PIX->value,
            'payment_date' => '2026-09-01',
            'status' => 'paid',
            'gross_value' => $invoice->gross_value,
            'fee_value' => 0,
        ]);
    }

    public function test_emit_throws_when_no_gateway_payment_exists(): void
    {
        $client = $this->makeClient();
        $invoice = $this->makeReceivable($client);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não possui pagamento do gateway');

        app(FiscalInvoiceEmitter::class)->emit($invoice);
    }

    public function test_emit_throws_when_account_not_invoicing_eligible(): void
    {
        $account = $this->makeAccount(['invoicing_enabled' => false]);
        $client = $this->makeClient();
        $invoice = $this->makeReceivable($client);
        $this->makeGatewayPayment($invoice, $account);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('não está habilitada para emissão fiscal');

        app(FiscalInvoiceEmitter::class)->emit($invoice);
    }

    public function test_emit_returns_existing_processing_invoice_without_re_requesting(): void
    {
        $account = $this->makeAccount();
        $client = $this->makeClient();
        $invoice = $this->makeReceivable($client);
        $payment = $this->makeGatewayPayment($invoice, $account);

        $existing = GatewayInvoice::query()->create([
            'status' => GatewayInvoiceStatus::PROCESSING->value,
            'value' => 100,
            'gateway_account_id' => $account->id,
            'gateway_payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
        ]);

        $result = app(FiscalInvoiceEmitter::class)->emit($invoice);

        $this->assertTrue($result->is($existing));
    }

    public function test_emit_returns_existing_authorized_invoice(): void
    {
        $account = $this->makeAccount();
        $client = $this->makeClient();
        $invoice = $this->makeReceivable($client);
        $payment = $this->makeGatewayPayment($invoice, $account);

        $existing = GatewayInvoice::query()->create([
            'status' => GatewayInvoiceStatus::AUTHORIZED->value,
            'value' => 100,
            'gateway_account_id' => $account->id,
            'gateway_payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'gateway_reference_key' => 'nf_123',
        ]);

        $result = app(FiscalInvoiceEmitter::class)->emit($invoice);

        $this->assertTrue($result->is($existing));
    }

    public function test_emit_retries_when_previous_status_is_error(): void
    {
        $account = $this->makeAccount();
        $client = $this->makeClient();
        $invoice = $this->makeReceivable($client);
        $payment = $this->makeGatewayPayment($invoice, $account);

        $existing = GatewayInvoice::query()->create([
            'status' => GatewayInvoiceStatus::ERROR->value,
            'value' => 100,
            'gateway_account_id' => $account->id,
            'gateway_payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'error_message' => 'Previous failure',
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'sandbox.asaas.com/api/v3/*' => [
                'id' => 'nf_retry_123',
                'status' => 'PROCESSING',
            ],
        ]);

        $result = app(FiscalInvoiceEmitter::class)->emit($invoice);

        $this->assertNotSame(GatewayInvoiceStatus::ERROR->value, $result->status);
    }

    public function test_eligibility_query_modifies_query_builder(): void
    {
        $account = $this->makeAccount();
        $client = $this->makeClient();
        $invoice = $this->makeReceivable($client);
        $this->makeGatewayPayment($invoice, $account);

        $emitter = app(FiscalInvoiceEmitter::class);
        $query = Invoice::query()->where('id', $invoice->id);
        $modifiedQuery = $emitter->eligibilityQuery($query);

        $sql = $modifiedQuery->toRawSql();

        $this->assertStringContainsString('can_request_gateway_invoice', $sql);
        $this->assertStringContainsString('gateway_invoice_request_reason', $sql);
    }
}
