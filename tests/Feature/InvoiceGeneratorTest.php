<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\GenderType;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InvoiceGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_receivable_invoices_from_a_sale(): void
    {
        $client = Client::factory()->create();

        $sale = Sale::query()->create([
            'gross_value' => 100,
            'discount_value' => 10,
            'total' => 90,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::PIX,
            'first_due_date' => '2026-07-10',
            'installments' => 3,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        $service = app(InvoiceGenerator::class);
        $invoices = $service->generate($sale);

        $this->assertCount(3, $invoices);
        $this->assertSame(100.0, round($invoices->sum('gross_value'), 4));
        $this->assertSame(10.0, round($invoices->sum('discount_value'), 4));
        $this->assertSame('2026-07-10', $invoices[0]->due_date?->format('Y-m-d'));
        $this->assertSame('2026-08-10', $invoices[1]->due_date?->format('Y-m-d'));
        $this->assertTrue($invoices[0]->holder->is($client));
        $this->assertTrue($invoices[0]->billable->is($sale));
    }

    public function test_it_generates_payable_invoices_from_a_purchase(): void
    {
        $supplier = Supplier::query()->create([
            'name' => 'Fornecedor Teste',
            'email' => 'fornecedor@example.com',
            'document' => '12345678901234',
            'phone' => '11999999999',
            'visibility' => 'visible',
        ]);

        $purchase = Purchase::query()->create([
            'gross_value' => 250,
            'discount_value' => 0,
            'total' => 250,
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::BOLETO,
            'first_due_date' => '2026-07-15',
            'installments' => 1,
            'visibility' => 'visible',
            'supplier_id' => $supplier->id,
        ]);

        $service = app(InvoiceGenerator::class);
        $invoices = $service->generate($purchase);

        $this->assertCount(1, $invoices);
        $this->assertSame('payable', $invoices[0]->operation_type->value);
        $this->assertSame(250.0, (float) $invoices[0]->gross_value);
        $this->assertTrue($invoices[0]->holder->is($supplier));
        $this->assertTrue($invoices[0]->billable->is($purchase));
    }

    public function test_it_requires_first_due_date_to_generate_contract_invoices(): void
    {
        $client = Client::factory()->create();

        $contract = Contract::query()->create([
            'plan_name' => 'Plano Gold',
            'gross_value' => 300,
            'discount_value' => 0,
            'total' => 300,
            'installments' => 3,
            'accepted_terms' => 'accepted',
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::CASH,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        $service = app(InvoiceGenerator::class);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Billing first due date is required to generate invoices.');

        $service->generate($contract);
    }

    public function test_contract_implements_the_shared_billing_source_contract(): void
    {
        $client = Client::factory()->create([
            'gender' => GenderType::MALE,
        ]);

        $contract = Contract::query()->create([
            'plan_name' => 'Plano Silver',
            'gross_value' => 180,
            'discount_value' => 0,
            'total' => 180,
            'installments' => 6,
            'accepted_terms' => 'accepted',
            'status' => BillableStatus::OPEN,
            'visibility' => 'visible',
            'client_id' => $client->id,
        ]);

        $this->assertSame(180.0, $contract->billingGrossValue());
        $this->assertSame(0.0, $contract->billingDiscountValue());
        $this->assertSame(180.0, $contract->billingTotalValue());
        $this->assertSame(6, $contract->billingInstallments());
        $this->assertSame('receivable', $contract->billingOperationType()->value);
        $this->assertSame(PaymentMethod::CASH, $contract->billingPaymentMethod());
    }

    public function test_it_applies_contract_coupon_discount_only_to_the_first_allowed_installments(): void
    {
        $client = Client::factory()->create();
        $coupon = Coupon::query()->create([
            'code' => 'CURTO',
            'percent' => 10,
            'discount_limit' => 100,
            'duration' => 2,
            'expiration_date' => '2026-12-31',
            'visibility' => 'visible',
        ]);

        $contract = Contract::query()->create([
            'plan_name' => 'Plano Parcial',
            'gross_value' => 120,
            'discount_value' => 0,
            'total' => 120,
            'first_due_date' => '2026-07-10',
            'installments' => 4,
            'accepted_terms' => 'accepted',
            'status' => BillableStatus::OPEN,
            'payment_method' => PaymentMethod::CASH,
            'visibility' => 'visible',
            'coupon_id' => $coupon->id,
            'client_id' => $client->id,
        ]);

        $service = app(InvoiceGenerator::class);
        $invoices = $service->generate($contract);

        $this->assertCount(4, $invoices);
        $this->assertEquals(3.0, $invoices[0]->discount_value);
        $this->assertEquals(3.0, $invoices[1]->discount_value);
        $this->assertEquals(0.0, $invoices[2]->discount_value);
        $this->assertEquals(0.0, $invoices[3]->discount_value);
        $this->assertEquals(6.0, $invoices->sum('discount_value'));
    }
}
