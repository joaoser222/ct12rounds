<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Models\Invoice;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductUnity;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Console\QueuedCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PurchaseItemPersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function grantPermission(User $user, string $permission): void
    {
        $permission = Permission::query()->create([
            'name' => $permission,
            'description' => $permission,
        ]);

        $user->permissions()->attach($permission);
    }

    public function test_authenticated_users_can_create_purchase_with_items(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.create');

        $supplier = $this->createSupplier();
        $firstProduct = $this->createProduct('Produto A');
        $secondProduct = $this->createProduct('Produto B');

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::PIX->value,
            'first_due_date' => '2026-07-10',
            'installments' => 2,
            'generate_invoices' => true,
            'discount_value' => 10,
            'annotations' => 'Compra de teste',
            'disable_stock' => false,
            'items' => [
                [
                    'product_id' => $firstProduct->id,
                    'quantity' => 2,
                    'price' => 15.5,
                ],
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 1,
                    'price' => 20,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $purchase = Purchase::query()->with('items')->firstOrFail();
        $firstProduct->refresh();
        $secondProduct->refresh();

        $this->assertSame(51.0, $purchase->gross_value);
        $this->assertSame(10.0, $purchase->discount_value);
        $this->assertSame(41.0, $purchase->total);
        $this->assertSame(BillableStatus::COMPLETED->value, $purchase->status);
        $this->assertSame('2026-07-10', $purchase->first_due_date?->format('Y-m-d'));
        $this->assertSame(2, $purchase->installments);
        $this->assertCount(2, $purchase->items);
        $this->assertDatabaseCount('invoices', 2);
        $this->assertSame(2, $firstProduct->quantity);
        $this->assertSame(1, $secondProduct->quantity);

        $firstInvoice = Invoice::query()->orderBy('installment_number')->firstOrFail();

        $this->assertSame($purchase->id, $firstInvoice->billable_id);
        $this->assertSame('purchase', $firstInvoice->billable_type);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $firstProduct->id,
            'product_name' => 'Produto A',
            'quantity' => 2,
        ]);
        Bus::assertNotDispatched(QueuedCommand::class);
    }

    public function test_authenticated_users_can_create_purchase_without_generating_installments(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.create');

        $supplier = $this->createSupplier();
        $product = $this->createProduct('Produto Sem Parcelas');

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'generate_invoices' => false,
            'discount_value' => 0,
            'annotations' => 'Compra sem parcelas',
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 21,
            ]],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $purchase = Purchase::query()->firstOrFail();

        $this->assertNull($purchase->first_due_date);
        $this->assertSame(1, $purchase->installments);
        $this->assertSame(BillableStatus::OPEN->value, $purchase->status);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_authenticated_users_can_update_purchase_items(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.update');

        $supplier = $this->createSupplier();
        $firstProduct = $this->createProduct('Produto A');
        $secondProduct = $this->createProduct('Produto B');

        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 1,
            'gross_value' => 10,
            'discount_value' => 0,
            'total' => 10,
            'annotations' => null,
            'disable_stock' => false,
            'visibility' => 'visible',
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $firstProduct->id,
            'product_name' => 'Produto A',
            'quantity' => 1,
            'price' => 10,
        ]);

        $response = $this->actingAs($user)->put(route('purchases.update', $purchase), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::PIX->value,
            'first_due_date' => '2026-08-10',
            'installments' => 3,
            'discount_value' => 5,
            'annotations' => 'Atualizada',
            'disable_stock' => true,
            'items' => [
                [
                    'product_id' => $secondProduct->id,
                    'quantity' => 3,
                    'price' => 12,
                ],
            ],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $purchase->refresh();
        $firstProduct->refresh();
        $secondProduct->refresh();

        $this->assertSame(36.0, $purchase->gross_value);
        $this->assertSame(5.0, $purchase->discount_value);
        $this->assertSame(31.0, $purchase->total);
        $this->assertSame(PaymentMethod::PIX, $purchase->payment_method);
        $this->assertSame('2026-08-10', $purchase->first_due_date?->format('Y-m-d'));
        $this->assertSame(3, $purchase->installments);
        $this->assertSame(0, $firstProduct->quantity);
        $this->assertSame(0, $secondProduct->quantity);

        $this->assertDatabaseMissing('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $firstProduct->id,
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $secondProduct->id,
            'product_name' => 'Produto B',
            'quantity' => 3,
        ]);
    }

    public function test_authenticated_users_can_finalize_saved_purchase_by_generating_invoices(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.update');

        $supplier = $this->createSupplier();
        $product = $this->createProduct('Produto Rascunho');

        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 2,
            'gross_value' => 40,
            'discount_value' => 0,
            'total' => 40,
            'annotations' => null,
            'disable_stock' => true,
            'visibility' => 'visible',
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'product_name' => 'Produto Rascunho',
            'quantity' => 2,
            'price' => 20,
        ]);

        $response = $this->actingAs($user)->put(route('purchases.update', $purchase), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 2,
            'generate_invoices' => true,
            'discount_value' => 0,
            'annotations' => null,
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 20,
            ]],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $purchase->refresh();

        $this->assertSame(BillableStatus::COMPLETED->value, $purchase->status);

        $this->assertDatabaseCount('invoices', 2);
        $this->assertDatabaseHas('invoices', [
            'billable_type' => 'purchase',
            'billable_id' => $purchase->id,
            'operation_type' => 'payable',
            'installment_number' => 1,
        ]);
    }

    public function test_finalized_purchases_cannot_be_updated(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.update');

        $supplier = $this->createSupplier();
        $product = $this->createProduct('Produto Finalizado');

        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::COMPLETED->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 1,
            'gross_value' => 20,
            'discount_value' => 0,
            'total' => 20,
            'annotations' => null,
            'disable_stock' => false,
            'visibility' => 'visible',
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'product_name' => 'Produto Finalizado',
            'quantity' => 1,
            'price' => 20,
        ]);

        $response = $this->actingAs($user)->put(route('purchases.update', $purchase), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::PIX->value,
            'first_due_date' => '2026-08-10',
            'installments' => 3,
            'discount_value' => 10,
            'annotations' => 'Nao deveria atualizar',
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 2,
                'price' => 25,
            ]],
        ]);

        $response->assertForbidden();

        $purchase->refresh();

        $this->assertSame(BillableStatus::COMPLETED->value, $purchase->status);
        $this->assertSame(20.0, $purchase->total);
    }

    public function test_purchase_with_disable_stock_does_not_recalculate_product_quantity(): void
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'purchases.create');

        $supplier = $this->createSupplier();
        $product = $this->createProduct('Produto Sem Estoque');

        $response = $this->actingAs($user)->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'first_due_date' => '2026-07-10',
            'installments' => 1,
            'discount_value' => 0,
            'annotations' => null,
            'disable_stock' => true,
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 4,
                'price' => 10,
            ]],
        ]);

        $response->assertRedirect(route('purchases.index'));

        $this->assertSame(0, $product->refresh()->quantity);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::query()->create([
            'name' => 'Fornecedor Teste',
            'email' => 'fornecedor@example.com',
            'document' => '12345678909234',
            'phone' => '11999999999',
            'visibility' => 'visible',
        ]);
    }

    private function createProduct(string $name): Product
    {
        ProductUnity::query()->firstOrCreate([
            'code' => 'UN',
        ], [
            'name' => 'Unidade',
        ]);

        return Product::query()->create([
            'name' => $name,
            'purchase_price' => 10,
            'sale_price' => 15,
            'quantity' => 0,
            'product_type' => ProductType::MERCHANDISE->value,
            'product_unity' => 'UN',
            'visibility' => 'visible',
        ]);
    }
}
