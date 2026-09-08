<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Receivables\MarkReceivablePaidAction;
use App\Actions\Receivables\RequestGatewayInvoiceAction;
use App\DTOs\Receivables\MarkReceivablePaidDTO;
use App\DTOs\Receivables\RequestGatewayInvoiceDTO;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Http\Requests\ReceivableSettlementRequest;
use App\Models\Receivable;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\Services\Gateway\FiscalInvoiceEmitter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReceivableController extends CrudModuleController
{
    public function __construct(
        private readonly FiscalInvoiceEmitter $fiscalInvoiceEmitter,
        private readonly MarkReceivablePaidAction $markReceivablePaid,
        private readonly RequestGatewayInvoiceAction $requestGatewayInvoice,
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
        return AccessModule::RECEIVABLE;
    }

    protected function modelClass(): string
    {
        return Receivable::class;
    }

    protected function newModelQuery(): Builder
    {
        return Receivable::query()->where('operation_type', OperationType::RECEIVABLE->value);
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

    /**
     * Return the available routes for the frontend.
     */
    protected function getModuleRoutes(): array
    {
        $routes = parent::getModuleRoutes();
        $markPaidRoute = route('receivables.mark-paid', ['receivable' => '__id__']);

        return [
            ...$routes,
            'markPaid' => str_replace('__id__', ':id', $markPaidRoute),
            'requestGatewayInvoice' => str_replace(
                '__id__',
                ':id',
                route('receivables.request-gateway-invoice', ['receivable' => '__id__']),
            ),
        ];
    }

    /**
     * @param  class-string<FormRequest>|null  $formRequestClass
     * @return array<string, mixed>
     */
    protected function validatedRequestData(Request $request, ?string $formRequestClass): array
    {
        $data = parent::validatedRequestData($request, $formRequestClass);

        unset($data['payment_date'], $data['total']);

        if (array_key_exists('holder_id', $data)) {
            $data['holder_type'] = 'client';
        }

        return $data;
    }

    public function markPaid(ReceivableSettlementRequest $request, Receivable $receivable): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::MARK_PAID);

        abort_unless(
            $receivable->operation_type === OperationType::RECEIVABLE,
            404,
        );

        abort_if(
            $receivable->status === InvoiceStatus::PAID,
            422,
            'This receivable has already been settled.',
        );

        $result = $this->markReceivablePaid->execute(MarkReceivablePaidDTO::from([
            ...$request->validated(),
            'id' => $receivable->getKey(),
        ]));

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Receivable settled successfully.',
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Receivable settled successfully.',
        ]);

        return redirect()->route('receivables.index');
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        /** @var Receivable $receivable */
        $receivable = $this->fiscalInvoiceEmitter->eligibilityQuery($this->newModelQuery())
            ->whereKey($this->modelFromRoute($request)->getKey())
            ->with('gatewayPayment')
            ->firstOrFail();

        if ($request->expectsJson()) {
            return response()->json($receivable);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $receivable,
            'id' => $receivable->getKey(),
            'routes' => $this->getModuleRoutes(),
            'pixQrCode' => $this->pixQrCode($receivable),
            ...$this->moduleDetailsProps($receivable),
        ]);
    }

    public function requestGatewayInvoice(Request $request, Receivable $receivable): JsonResponse|RedirectResponse
    {
        $this->authorizeAccess(AccessAction::REQUEST_INVOICE);
        abort_unless($receivable->operation_type === OperationType::RECEIVABLE, 404);

        try {
            $result = $this->requestGatewayInvoice->execute(
                RequestGatewayInvoiceDTO::from(['id' => $receivable->getKey()]),
            );
        } catch (\Throwable $exception) {
            report($exception);

            return $this->actionFailureResponse($request, null, $exception->getMessage());
        }

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($result->data, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Fiscal invoice requested successfully.',
        ]);

        return back();
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    private function actionFailureResponse(Request $request, ?array $errors, ?string $message): RedirectResponse|JsonResponse
    {
        $message ??= 'Could not complete the operation.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], 422);
        }

        return back()->withErrors($errors ?? ['action' => $message])->withInput();
    }

    private function pixQrCode(Receivable $receivable): ?array
    {
        if ($receivable->payment_method !== PaymentMethod::PIX || $receivable->gatewayPayment === null) {
            return null;
        }

        try {
            return app(PaymentGatewayAdapter::class)->getPixQrCode($receivable->gatewayPayment);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
