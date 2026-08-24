<?php

namespace Tests\Feature\Gateway;

use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayCustomer;
use App\Models\GatewayPayment;
use App\Models\GatewayTransfer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportGatewayInvoicesTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAsaas(array $customers = [], array $payments = [], array $transfers = []): void
    {
        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response([
                'object' => 'list',
                'hasMore' => false,
                'data' => $customers,
            ]),
            'sandbox.asaas.com/api/v3/payments*' => Http::response([
                'object' => 'list',
                'hasMore' => false,
                'data' => $payments,
            ]),
            'sandbox.asaas.com/api/v3/transfers*' => Http::response([
                'object' => 'list',
                'hasMore' => false,
                'data' => $transfers,
            ]),
        ]);
    }

    private function asaasAccount(): GatewayAccount
    {
        return GatewayAccount::factory()->create([
            'name' => 'Asaas',
            'settings' => [
                'api_key' => 'test-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
        ]);
    }

    public function test_import_customers_command_creates_clients_and_gateway_customers(): void
    {
        $this->asaasAccount();

        $this->fakeAsaas([
            [
                'id' => 'cus_1',
                'name' => 'Joao Silva',
                'email' => 'joao@example.com',
                'phone' => '11999999999',
                'cpfCnpj' => '12345678901',
                'externalReference' => null,
            ],
        ]);

        $this->artisan('gateway:import-customers')->assertSuccessful();

        $this->assertDatabaseHas('clients', ['document' => '12345678901', 'name' => 'Joao Silva']);
        $this->assertDatabaseHas('gateway_customers', [
            'gateway_reference_key' => 'cus_1',
            'gateway_account_id' => GatewayAccount::where('name', 'Asaas')->first()->id,
        ]);
    }

    public function test_import_invoices_command_creates_invoices_and_payments(): void
    {
        $this->asaasAccount();

        $this->fakeAsaas(
            [
                [
                    'id' => 'cus_1',
                    'name' => 'Joao Silva',
                    'email' => 'joao@example.com',
                    'phone' => '11999999999',
                    'cpfCnpj' => '12345678901',
                    'externalReference' => null,
                ],
            ],
            [
                [
                    'id' => 'pay_1',
                    'customer' => 'cus_1',
                    'value' => 100.00,
                    'netValue' => 95.00,
                    'status' => 'RECEIVED',
                    'billingType' => 'PIX',
                    'dueDate' => '2026-01-10',
                    'paymentDate' => '2026-01-10',
                    'description' => 'Mensalidade',
                ],
            ],
        );

        $this->artisan('gateway:import-customers')->assertSuccessful();
        $this->artisan('gateway:import-invoices')->assertSuccessful();

        $invoice = Invoice::where('external_reference', 'pay_1')->first();
        $this->assertNotNull($invoice);
        $this->assertSame('receivable', $invoice->operation_type->value);
        $this->assertSame('paid', $invoice->status->value);
        $this->assertEquals(100.0, $invoice->gross_value);

        $this->assertDatabaseHas('gateway_payments', [
            'gateway_reference_key' => 'pay_1',
            'status' => 'paid',
            'gross_value' => 100.0,
            'fee_value' => 5.0,
            'payment_method' => 'pix',
        ]);
    }

    public function test_import_transfers_command_creates_gateway_transfers(): void
    {
        $this->asaasAccount();

        $this->fakeAsaas([], [], [
            [
                'id' => 'tr_1',
                'value' => 200.00,
                'netValue' => 198.00,
                'status' => 'DONE',
                'dateCreated' => '2026-01-05',
            ],
        ]);

        $this->artisan('gateway:import-transfers')->assertSuccessful();

        $this->assertDatabaseHas('gateway_transfers', [
            'gateway_reference_key' => 'tr_1',
            'status' => 'paid',
            'gross_value' => 200.0,
            'fee_value' => 2.0,
        ]);
    }

    public function test_import_is_idempotent_on_reference_key(): void
    {
        $this->asaasAccount();

        $this->fakeAsaas(
            [
                [
                    'id' => 'cus_1',
                    'name' => 'Joao Silva',
                    'email' => 'joao@example.com',
                    'phone' => '11999999999',
                    'cpfCnpj' => '12345678901',
                    'externalReference' => null,
                ],
            ],
            [
                [
                    'id' => 'pay_1',
                    'customer' => 'cus_1',
                    'value' => 50.00,
                    'netValue' => 50.00,
                    'status' => 'PENDING',
                    'billingType' => 'BOLETO',
                    'dueDate' => '2026-02-01',
                    'description' => null,
                ],
            ],
            [
                [
                    'id' => 'tr_1',
                    'value' => 200.00,
                    'netValue' => 198.00,
                    'status' => 'DONE',
                    'dateCreated' => '2026-01-05',
                ],
            ],
        );

        $this->artisan('gateway:import-customers')->assertSuccessful();
        $this->artisan('gateway:import-invoices')->assertSuccessful();
        $this->artisan('gateway:import-transfers')->assertSuccessful();

        $this->artisan('gateway:import-customers')->assertSuccessful();
        $this->artisan('gateway:import-invoices')->assertSuccessful();
        $this->artisan('gateway:import-transfers')->assertSuccessful();

        $this->assertSame(1, Client::where('document', '12345678901')->count());
        $this->assertSame(1, Invoice::where('external_reference', 'pay_1')->count());
        $this->assertSame(1, GatewayPayment::where('gateway_reference_key', 'pay_1')->count());
        $this->assertSame(1, GatewayTransfer::where('gateway_reference_key', 'tr_1')->count());
    }

    public function test_payment_without_matching_customer_is_skipped(): void
    {
        $this->asaasAccount();

        $this->fakeAsaas(
            [],
            [
                [
                    'id' => 'pay_orphan',
                    'customer' => 'cus_missing',
                    'value' => 30.00,
                    'netValue' => 30.00,
                    'status' => 'PENDING',
                    'billingType' => 'PIX',
                    'dueDate' => '2026-02-01',
                    'description' => null,
                ],
            ],
        );

        $this->artisan('gateway:import-invoices')->assertSuccessful();

        $this->assertDatabaseMissing('gateway_payments', ['gateway_reference_key' => 'pay_orphan']);
    }

    public function test_command_fails_when_account_missing(): void
    {
        $this->artisan('gateway:import-invoices')->assertFailed();
        $this->artisan('gateway:import-customers')->assertFailed();
        $this->artisan('gateway:import-transfers')->assertFailed();
    }
}
