<?php

namespace Tests\Feature\Gateway;

use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayCustomer;
use App\Models\GatewayPayment;
use App\Models\GatewayTransfer;
use App\Models\Invoice;
use App\Services\Gateway\GatewayAdapterResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportGatewayInvoicesTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAsaas(array $customers = [], array $payments = [], array $transfers = []): void
    {
        $responses = [];

        foreach ($customers as $customer) {
            $responses["sandbox.asaas.com/api/v3/customers/{$customer['id']}"] = Http::response($customer);
        }

        foreach ($payments as $payment) {
            $responses["sandbox.asaas.com/api/v3/payments/{$payment['id']}"] = Http::response($payment);
        }

        foreach ($transfers as $transfer) {
            $responses["sandbox.asaas.com/api/v3/transfers/{$transfer['id']}"] = Http::response($transfer);
        }

        $responses += [
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
        ];

        Http::fake($responses);
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
        $this->assertSame(
            'Joao Silva',
            GatewayCustomer::query()->with('holder')->sole()->holder_name,
        );
    }

    public function test_import_customers_sanitizes_local_and_remote_customer_data(): void
    {
        $account = $this->asaasAccount();
        $client = Client::factory()->create([
            'document' => '12345678901',
            'name' => 'Nome Anterior',
            'email' => 'antigo@example.com',
            'phone' => '11988888888',
            'address' => 'Endereço Anterior',
            'address_number' => '10',
            'address_complement' => 'Casa Anterior',
            'address_district' => 'Bairro Anterior',
            'address_postal_code' => '01000000',
            'address_state' => 'RJ',
            'address_city' => 'Rio de Janeiro',
        ]);
        GatewayCustomer::query()->create([
            'gateway_reference_key' => 'cus_existing',
            'holder_id' => $client->getKey(),
            'holder_type' => $client->getMorphClass(),
            'gateway_account_id' => $account->getKey(),
        ]);

        $this->fakeAsaas([
            [
                'id' => 'cus_existing',
                'name' => '  JOÃO   DA  SILVA  ',
                'email' => ' JOAO@EXAMPLE.COM ',
                'mobilePhone' => '(11) 99999-9999',
                'cpfCnpj' => '123.456.789-01',
                'address' => ' RUA   DAS FLORES ',
                'addressNumber' => ' s/n ',
                'complement' => ' APTO   101 ',
                'province' => ' CENTRO ',
                'postalCode' => '01310-100',
                'cityName' => ' SÃO   PAULO ',
                'state' => ' sp ',
            ],
        ]);

        $this->artisan('gateway:import-customers')->assertSuccessful();

        $client->refresh();
        $this->assertSame('João da Silva', $client->name);
        $this->assertSame('joao@example.com', $client->email);
        $this->assertSame('11999999999', $client->phone);
        $this->assertSame('Rua das Flores', $client->address);
        $this->assertSame('S/N', $client->address_number);
        $this->assertSame('Apto 101', $client->address_complement);
        $this->assertSame('Centro', $client->address_district);
        $this->assertSame('01310100', $client->address_postal_code);
        $this->assertSame('São Paulo', $client->address_city);
        $this->assertSame('SP', $client->address_state);

        Http::assertSent(fn (HttpRequest $request): bool => $request->method() === 'PUT'
            && $request->url() === 'https://sandbox.asaas.com/api/v3/customers/cus_existing'
            && $request['name'] === 'João da Silva'
            && $request['email'] === 'joao@example.com'
            && $request['phone'] === '11999999999'
            && $request['address'] === 'Rua das Flores'
            && $request['addressNumber'] === 'S/N'
            && $request['complement'] === 'Apto 101'
            && $request['province'] === 'Centro'
            && $request['postalCode'] === '01310100'
            && $request['city'] === 'São Paulo'
            && $request['state'] === 'SP');
    }

    public function test_outbound_customer_payload_sanitizes_name_and_address(): void
    {
        $account = $this->asaasAccount();
        $client = Client::factory()->create([
            'name' => '  JOÃO   DA  SILVA  ',
            'document' => '12345678901',
            'email' => ' JOAO@EXAMPLE.COM ',
            'phone' => '11999999999',
            'address' => ' RUA   DAS FLORES ',
            'address_number' => ' s/n ',
            'address_complement' => ' APTO   101 ',
            'address_district' => ' CENTRO ',
            'address_postal_code' => '01310100',
            'address_city' => ' SÃO   PAULO ',
            'address_state' => 'SP',
        ]);

        Http::fake([
            'sandbox.asaas.com/api/v3/customers' => Http::response(['id' => 'cus_outbound']),
        ]);

        app(GatewayAdapterResolver::class)
            ->paymentAdapter($account)
            ->createCustomer($client);

        Http::assertSent(fn (HttpRequest $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://sandbox.asaas.com/api/v3/customers'
            && $request['name'] === 'João da Silva'
            && $request['cpfCnpj'] === '12345678901'
            && $request['email'] === 'joao@example.com'
            && $request['phone'] === '11999999999'
            && $request['address'] === 'Rua das Flores'
            && $request['addressNumber'] === 'S/N'
            && $request['complement'] === 'Apto 101'
            && $request['province'] === 'Centro'
            && $request['postalCode'] === '01310100'
            && $request['city'] === 'São Paulo'
            && $request['state'] === 'SP');
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
        Http::assertNotSent(fn (HttpRequest $request): bool => $request->method() === 'PUT');
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
