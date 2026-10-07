<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\FinancialCategories\CreateFinancialCategoryAction;
use App\Actions\FinancialCategories\UpdateFinancialCategoryAction;
use App\DTOs\FinancialCategories\CreateFinancialCategoryDTO;
use App\DTOs\FinancialCategories\UpdateFinancialCategoryDTO;
use App\Enums\OperationType;
use App\Http\Requests\FinancialCategoryRequest;
use App\Models\FinancialCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancialCategoryController extends CrudModuleController
{
    public function __construct(
        private readonly CreateFinancialCategoryAction $createFinancialCategory,
        private readonly UpdateFinancialCategoryAction $updateFinancialCategory,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'cost_center_name', 'color', 'created_at'];

    protected array $joins = ['costCenter'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'financial_categories.id',
        'name' => 'financial_categories.name',
        'color' => 'financial_categories.color',
        'created_at' => 'financial_categories.created_at',
        'cost_center_name' => 'cost_centers.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'cost_center_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'cost_center_name', 'created_at', 'updated_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::FINANCIAL_CATEGORY;
    }

    protected function modelClass(): string
    {
        return FinancialCategory::class;
    }

    protected function storeRequestClass(): ?string
    {
        return FinancialCategoryRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return FinancialCategoryRequest::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createFinancialCategory->execute(
            CreateFinancialCategoryDTO::from($this->validatedRequestData($request, $this->storeRequestClass()))
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        /** @var FinancialCategory $financialCategory */
        $financialCategory = $this->modelFromRoute($request);

        $result = $this->updateFinancialCategory->execute(
            UpdateFinancialCategoryDTO::from([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $financialCategory->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data);
        }

        return redirect()->route($this->routePrefix().'.index');
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'operationTypes' => $this->enumOptions(OperationType::class),
            ],

        ];
    }

    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Não foi possível concluir a operação.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['action' => $message])->withInput();
    }
}
