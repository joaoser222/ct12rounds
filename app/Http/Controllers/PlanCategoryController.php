<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\PlanCategories\CreatePlanCategoryAction;
use App\Actions\PlanCategories\UpdatePlanCategoryAction;
use App\DTOs\PlanCategories\CreatePlanCategoryDTO;
use App\DTOs\PlanCategories\UpdatePlanCategoryDTO;
use App\Http\Requests\PlanCategoryRequest;
use App\Models\PlanCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanCategoryController extends CrudModuleController
{
    public function __construct(
        private readonly CreatePlanCategoryAction $createPlanCategory,
        private readonly UpdatePlanCategoryAction $updatePlanCategory,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'name', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PLAN_CATEGORY;
    }

    protected function modelClass(): string
    {
        return PlanCategory::class;
    }

    protected function storeRequestClass(): ?string
    {
        return PlanCategoryRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return PlanCategoryRequest::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createPlanCategory->execute(
            CreatePlanCategoryDTO::from($this->validatedRequestData($request, $this->storeRequestClass()))
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

        /** @var PlanCategory $planCategory */
        $planCategory = $this->modelFromRoute($request);

        $result = $this->updatePlanCategory->execute(
            UpdatePlanCategoryDTO::from([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $planCategory->getKey(),
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
