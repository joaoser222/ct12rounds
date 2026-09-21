<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\ModalityCategories\CreateModalityCategoryAction;
use App\Actions\ModalityCategories\UpdateModalityCategoryAction;
use App\DTOs\ModalityCategories\CreateModalityCategoryDTO;
use App\DTOs\ModalityCategories\UpdateModalityCategoryDTO;
use App\Enums\Audience;
use App\Models\ModalityCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ModalityCategoryController extends CrudModuleController
{
    public function __construct(
        private readonly CreateModalityCategoryAction $createModalityCategory,
        private readonly UpdateModalityCategoryAction $updateModalityCategory,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'slug', 'audience', 'created_at'];

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
        return AccessModule::MODALITY_CATEGORY;
    }

    protected function modelClass(): string
    {
        return ModalityCategory::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createModalityCategory->execute(
            CreateModalityCategoryDTO::from($request->validate([
                'name' => ['required', 'string', 'max:100'],
                'slug' => ['required', 'string', 'max:100', 'unique:modality_categories,slug'],
                'audience' => ['required', 'string', 'in:adult,child'],
            ]))
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

        /** @var ModalityCategory $modalityCategory */
        $modalityCategory = $this->modelFromRoute($request);

        $result = $this->updateModalityCategory->execute(
            UpdateModalityCategoryDTO::from([
                ...$request->validate([
                    'name' => ['nullable', 'string', 'max:100'],
                    'slug' => ['nullable', 'string', 'max:100', 'unique:modality_categories,slug,'.$modalityCategory->id],
                    'audience' => ['nullable', 'string', 'in:adult,child'],
                ]),
                'id' => $modalityCategory->getKey(),
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
