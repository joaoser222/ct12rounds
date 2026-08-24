<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Supplier\CreateSupplierAction;
use App\Actions\Supplier\UpdateSupplierAction;
use App\DTOs\Supplier\CreateSupplierDTO;
use App\DTOs\Supplier\UpdateSupplierDTO;
use App\Models\Supplier;
use App\Models\Uf;
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createSupplier->execute(
            CreateSupplierDTO::from($request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'document' => ['required', 'string', 'max:20'],
                'phone' => ['nullable', 'string', 'max:20'],
                'address' => ['nullable', 'string', 'max:255'],
                'address_number' => ['required', 'string', 'max:50'],
                'address_complement' => ['nullable', 'string', 'max:255'],
                'address_state' => ['nullable', 'string', 'max:2'],
                'address_city' => ['nullable', 'string', 'max:255'],
                'address_district' => ['nullable', 'string', 'max:255'],
                'address_postal_code' => ['required', 'string', 'max:10'],
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

        /** @var Supplier $supplier */
        $supplier = $this->modelFromRoute($request);

        $result = $this->updateSupplier->execute(
            UpdateSupplierDTO::from([
                ...$request->validate([
                    'name' => ['nullable', 'string', 'max:255'],
                    'email' => ['nullable', 'email', 'max:255'],
                    'document' => ['nullable', 'string', 'max:20'],
                    'phone' => ['nullable', 'string', 'max:20'],
                    'address' => ['nullable', 'string', 'max:255'],
                    'address_number' => ['required', 'string', 'max:50'],
                    'address_complement' => ['nullable', 'string', 'max:255'],
                    'address_state' => ['nullable', 'string', 'max:2'],
                    'address_city' => ['nullable', 'string', 'max:255'],
                    'address_district' => ['nullable', 'string', 'max:255'],
                    'address_postal_code' => ['required', 'string', 'max:10'],
                ]),
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
                'ufs' => $this->modelOptions(Uf::class),
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
