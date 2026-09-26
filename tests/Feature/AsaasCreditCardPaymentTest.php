<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Contract;
use App\Models\GatewayAccount;
use App\Models\GatewayCreditCard;
use App\Models\GatewayCustomer;
use App\Models\Invoice;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsaasCreditCardPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_card_payment_sends_the_stored_token_to_charge_immediately(): void
    {
        $adapter = $this->adapter();
        [$invoice, $customer] = $this->makeInvoice(PaymentMethod::CREDIT_CARD);

        $this->storeCard($customer, 'tok_stored_123');

        Http::fake([
            'sandbox.asaas.com/api/v3/payments' => Http::response([
                'id' => 'pay_1',
                'billingType' => 'CREDIT_CARD',
                'status' => 'CONFIRMED',
                'value' => 100.0,
                'netValue' => 97.0,
                'paymentDate' => '2026-09-26T10:00:00Z',
            ]),
        ]);

        $payment = $adapter->createPayment($invoice, $customer, ['description' => 'Contrato #1']);

        Http::assertSent(function ($request): bool {
            $body = $request->data();

            return $request->url() === 'https://sandbox.asaas.com/api/v3/payments'
                && ($body['billingType'] ?? null) === 'CREDIT_CARD'
                && ($body['creditCardToken'] ?? null) === 'tok_stored_123';
        });

        $this->assertSame('pay_1', $payment->gateway_reference_key);
    }

    public function test_explicit_token_option_overrides_the_stored_card(): void
    {
        $adapter = $this->adapter();
        [$invoice, $customer] = $this->makeInvoice(PaymentMethod::CREDIT_CARD);

        $this->storeCard($customer, 'tok_stored_123');

        Http::fake([
            'sandbox.asaas.com/api/v3/payments' => Http::response([
                'id' => 'pay_2',
                'billingType' => 'CREDIT_CARD',
                'status' => 'CONFIRMED',
                'value' => 100.0,
            ]),
        ]);

        $adapter->createPayment($invoice, $customer, ['credit_card_token' => 'tok_option_456']);

        Http::assertSent(function ($request): bool {
            return ($request->data()['creditCardToken'] ?? null) === 'tok_option_456';
        });
    }

    public function test_pix_payment_never_sends_a_credit_card_token(): void
    {
        $adapter = $this->adapter();
        [$invoice, $customer] = $this->makeInvoice(PaymentMethod::PIX);

        $this->storeCard($customer, 'tok_stored_123');

        Http::fake([
            'sandbox.asaas.com/api/v3/payments' => Http::response([
                'id' => 'pay_3',
                'billingType' => 'PIX',
                'status' => 'PENDING',
                'value' => 100.0,
            ]),
        ]);

        $adapter->createPayment($invoice, $customer);

        Http::assertSent(function ($request): bool {
            return ! array_key_exists('creditCardToken', $request->data());
        });
    }

    public function test_credit_card_payment_without_stored_card_omits_the_token(): void
    {
        $adapter = $this->adapter();
        [$invoice, $customer] = $this->makeInvoice(PaymentMethod::CREDIT_CARD);

        Http::fake([
            'sandbox.asaas.com/api/v3/payments' => Http::response([
                'id' => 'pay_4',
                'billingType' => 'CREDIT_CARD',
                'status' => 'PENDING',
                'value' => 100.0,
            ]),
        ]);

        $adapter->createPayment($invoice, $customer);

        Http::assertSent(function ($request): bool {
            return ! array_key_exists('creditCardToken', $request->data());
        });
    }

    private function adapter(): AsaasPaymentGatewayAdapter
    {
        GatewayAccount::query()->create([
            'name' => 'Asaas',
            'description' => 'Asaas',
            'visibility' => 'visible',
            'settings' => [
                'api_key' => 'secret-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
        ]);

        return app(AsaasPaymentGatewayAdapter::class);
    }

    private function storeCard(GatewayCustomer $customer, string $token): GatewayCreditCard
    {
        return GatewayCreditCard::query()->create([
            'gateway_card_token' => $token,
            'gateway_reference_key' => '4111',
            'card_brand' => 'VISA',
            'last_digits' => '4111',
            'gateway_account_id' => $customer->gateway_account_id,
            'gateway_customer_id' => $customer->getKey(),
        ]);
    }

    /**
     * @return array{0: Invoice, 1: GatewayCustomer}
     */
    private function makeInvoice(PaymentMethod $paymentMethod): array
    {
        $client = Client::factory()->create(['status' => 'active']);
        $contract = Contract::query()->create([
            'plan_name' => 'Mensal',
            'gross_value' => 100,
            'discount_value' => 0,
            'total' => 100,
            'payment_method' => $paymentMethod->value,
            'first_due_date' => now()->toDateString(),
            'installments' => 1,
            'accepted_terms' => 'accepted',
            'client_id' => $client->id,
            'visibility' => 'visible',
        ]);

        $customer = GatewayCustomer::query()->create([
            'gateway_reference_key' => 'cus_123',
            'holder_id' => $client->id,
            'holder_type' => $client->getMorphClass(),
            'gateway_account_id' => GatewayAccount::query()->value('id'),
        ]);

        $invoice = Invoice::query()->create([
            'operation_type' => OperationType::RECEIVABLE->value,
            'payment_method' => $paymentMethod->value,
            'due_date' => now()->toDateString(),
            'gross_value' => 100,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 1,
            'status' => InvoiceStatus::PENDING->value,
            'visibility' => 'visible',
            'holder_id' => $client->id,
            'holder_type' => $client->getMorphClass(),
            'billable_id' => $contract->id,
            'billable_type' => $contract->getMorphClass(),
        ]);

        return [$invoice, $customer];
    }
}
