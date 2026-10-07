<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Products\CreateProductAction;
use App\Actions\Products\UpdateProductAction;
use App\DTOs\Products\CreateProductDTO;
use App\DTOs\Products\UpdateProductDTO;
use App\Enums\ProductType;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends CrudModuleController
{
    public function __construct(
        private readonly CreateProductAction $createProduct,
        private readonly UpdateProductAction $updateProduct,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'sale_price', 'quantity', 'product_type', 'product_unity_label', 'created_at'];

    protected array $joins = ['productUnity'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'sale_price', 'created_at'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'products.id',
        'name' => 'products.name',
        'sale_price' => 'products.sale_price',
        'quantity' => 'products.quantity',
        'product_type' => 'products.product_type',
        'product_unity_label' => 'product_unities.name',
        'created_at' => 'products.created_at',
    ];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PRODUCT;
    }

    protected function modelClass(): string
    {
        return Product::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'productTypes' => $this->enumOptions(ProductType::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'productTypes' => $this->enumOptions(ProductType::class),
            ],
        ];
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createProduct->execute(
            CreateProductDTO::from($this->validatedProductData($request))
        );

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' criado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var Product $product */
        $product = $this->modelFromRoute($request);

        $result = $this->updateProduct->execute(
            UpdateProductDTO::from([
                ...$this->validatedProductData($request),
                'id' => $product->getKey(),
            ])
        );

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($this->accessModule()->label().' atualizado com sucesso.'),
        ]);

        return redirect()->route($this->routePrefix().'.index');
    }

    /** @return array<string, mixed> */
    private function validatedProductData(Request $request): array
    {
        return $this->validatedRequestData($request, ProductRequest::class);
    }
}
