<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Supplier\CreateSupplierAction;
use App\Actions\Supplier\UpdateSupplierAction;
use App\DTOs\Supplier\CreateSupplierDTO;
use App\DTOs\Supplier\UpdateSupplierDTO;
use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Models\State;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupplierController extends CrudModuleController
{
    public function __construct(
        private readonly CreateSupplierAction $createSupplier,
        private readonly UpdateSupplierAction $updateSupplier,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'name', 'email', 'document', 'phone', 'created_at', 'updated_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['name', 'email', 'document', 'phone'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'created_at', 'updated_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::SUPPLIER;
    }

    protected function modelClass(): string
    {
        return Supplier::class;
    }

    protected function storeRequestClass(): ?string
    {
        return SupplierRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return SupplierRequest::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createSupplier->execute(
            CreateSupplierDTO::from($this->validatedRequestData($request, $this->storeRequestClass()))
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

        /** @var Supplier $supplier */
        $supplier = $this->modelFromRoute($request);

        $result = $this->updateSupplier->execute(
            UpdateSupplierDTO::from([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $supplier->getKey(),
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
                'states' => $this->modelOptions(State::class),
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
