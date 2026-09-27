<?php

namespace Tests\Feature;

use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Visibility;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductUnity;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use App\Services\StockRecalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockRecalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recalculates_stock_from_purchases_and_sales(): void
    {
        $service = app(StockRecalculationService::class);
        $product = $this->createProduct('Produto Estoque');

        $purchase = Purchase::query()->create([
            'supplier_id' => $this->createSupplier()->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'gross_value' => 0,
            'discount_value' => 0,
            'total' => 0,
            'disable_stock' => false,
            'visibility' => Visibility::VISIBLE->value,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 10,
            'quantity' => 8,
        ]);

        $sale = Sale::query()->create([
            'client_id' => $this->createClient()->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'gross_value' => 0,
            'discount_value' => 0,
            'total' => 0,
            'disable_stock' => false,
            'visibility' => Visibility::VISIBLE->value,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 15,
            'quantity' => 3,
        ]);

        $recalculatedProduct = $service->recalculateProduct($product);

        $this->assertSame(5, $recalculatedProduct->quantity);
    }

    public function test_it_ignores_documents_with_disable_stock_or_archived_visibility(): void
    {
        $service = app(StockRecalculationService::class);
        $product = $this->createProduct('Produto Ignorado');
        $supplier = $this->createSupplier();
        $client = $this->createClient();

        $purchase = Purchase::query()->create([
            'supplier_id' => $supplier->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'gross_value' => 0,
            'discount_value' => 0,
            'total' => 0,
            'disable_stock' => true,
            'visibility' => Visibility::VISIBLE->value,
        ]);

        PurchaseItem::query()->create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 10,
            'quantity' => 10,
        ]);

        $sale = Sale::query()->create([
            'client_id' => $client->id,
            'status' => BillableStatus::OPEN->value,
            'payment_method' => PaymentMethod::CASH->value,
            'gross_value' => 0,
            'discount_value' => 0,
            'total' => 0,
            'disable_stock' => false,
            'visibility' => Visibility::ARCHIVED->value,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 15,
            'quantity' => 4,
        ]);

        $recalculatedProduct = $service->recalculateProduct($product);

        $this->assertSame(0, $recalculatedProduct->quantity);
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
            'visibility' => Visibility::VISIBLE->value,
        ]);
    }

    private function createSupplier(): Supplier
    {
        return Supplier::query()->create([
            'name' => 'Fornecedor Estoque',
            'email' => 'fornecedor-estoque@example.com',
            'document' => '12345678909234',
            'phone' => '11999999999',
            'visibility' => Visibility::VISIBLE->value,
        ]);
    }

    private function createClient(): Client
    {
        return Client::factory()->create();
    }
}
