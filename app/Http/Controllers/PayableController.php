<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Payables\CreatePayableAction;
use App\Actions\Payables\UpdatePayableAction;
use App\DTOs\Payables\CreatePayableDTO;
use App\DTOs\Payables\UpdatePayableDTO;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Http\Requests\PayableRequest;
use App\Models\Payable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayableController extends CrudModuleController
{
    public function __construct(
        private readonly CreatePayableAction $createPayable,
        private readonly UpdatePayableAction $updatePayable,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'due_date', 'payment_date', 'total', 'status', 'created_at'];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['due_date', 'payment_date', 'status'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'due_date', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::PAYABLE;
    }

    protected function modelClass(): string
    {
        return Payable::class;
    }

    protected function newModelQuery(): Builder
    {
        return Payable::query()->where('operation_type', OperationType::PAYABLE->value);
    }

    protected function storeRequestClass(): ?string
    {
        return PayableRequest::class;
    }

    protected function updateRequestClass(): ?string
    {
        return PayableRequest::class;
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createPayable->execute(
            CreatePayableDTO::from($this->validatedRequestData($request, $this->storeRequestClass()))
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

        /** @var Payable $payable */
        $payable = $this->modelFromRoute($request);

        $result = $this->updatePayable->execute(
            UpdatePayableDTO::from([
                ...$this->validatedRequestData($request, $this->updateRequestClass()),
                'id' => $payable->getKey(),
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

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'invoiceStatus' => $this->enumOptions(InvoiceStatus::class),
            ],
        ];
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        return [
            'options' => [
                'invoiceStatus' => $this->enumOptions(InvoiceStatus::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
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
