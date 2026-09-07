<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\Contracts\ApplyContractAction;
use App\Actions\Contracts\CancelContractAction;
use App\Actions\Contracts\CreateContractAction;
use App\Actions\Contracts\UpdateContractAction;
use App\DTOs\Contracts\CancelContractDTO;
use App\DTOs\Contracts\CreateContractDTO;
use App\DTOs\Contracts\UpdateContractDTO;
use App\Enums\BillableStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\ContractWizardRequest;
use App\Models\Contract;
use App\Models\Coupon;
use App\Models\HiringLead;
use App\Models\Plan;
use App\Services\QrCodeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends CrudModuleController
{
    public function __construct(
        private readonly CreateContractAction $createContract,
        private readonly UpdateContractAction $updateContract,
        private readonly CancelContractAction $cancelContract,
        private readonly ApplyContractAction $applyContract,
        private readonly QrCodeService $qrCodeService,
    ) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = ['id', 'plan_name', 'total', 'first_due_date', 'installments', 'status', 'accepted_terms', 'created_at'];

    protected array $joins = ['client'];

    /**
     * @var array<string, string>
     */
    protected array $fieldsMapping = [
        'id' => 'contracts.id',
        'plan_name' => 'contracts.plan_name',
        'total' => 'contracts.total',
        'first_due_date' => 'contracts.first_due_date',
        'installments' => 'contracts.installments',
        'status' => 'contracts.status',
        'accepted_terms' => 'contracts.accepted_terms',
        'created_at' => 'contracts.created_at',
        'client_name' => 'clients.name',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['plan_name', 'client_name'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'plan_name', 'first_due_date', 'created_at', 'accepted_terms'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::CONTRACT;
    }

    protected function modelClass(): string
    {
        return Contract::class;
    }

    public function create(): Response
    {
        $this->authorizeAccess(AccessAction::CREATE);

        return Inertia::render($this->detailsComponent(), [
            'routes' => [
                'index' => route('contracts.index'),
                'store' => route('contracts.store'),
                'update' => route('contracts.update', ['contract' => '__id__']),
                'create' => route('contracts.create'),
                'show' => route('contracts.show', ['contract' => '__id__']),
                'destroy' => route('contracts.destroy'),
                'changeVisibility' => route('contracts.change-visibility'),
            ],
            'options' => [
                'plans' => $this->planOptions(),
                'coupons' => $this->couponOptions(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $result = $this->createContract->execute(
            CreateContractDTO::from(
                $this->validatedRequestData($request, ContractWizardRequest::class)
            )
        );

        if (! $result->success) {
            return back()->withErrors($result->errors ?? ['contract' => $result->message])->withInput();
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route('contracts.show', ['contract' => $result->data->id]);
    }

    public function cancel(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CANCEL);

        $contract = $this->modelFromRoute($request);

        $result = $this->cancelContract->execute(
            CancelContractDTO::from([
                ...$request->validate(['reason' => ['nullable', 'string', 'max:500']]),
                'contract_id' => $contract->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result->message,
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route('contracts.index');
    }

    public function apply(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $contract = $this->modelFromRoute($request);

        $result = $this->applyContract->execute($contract->getKey());

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message, 422);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $result->message,
            ]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route('contracts.show', ['contract' => $contract]);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::UPDATE);

        $contract = $this->modelFromRoute($request);

        $result = $this->updateContract->execute(
            UpdateContractDTO::from([
                ...$request->validate(['annotations' => ['nullable', 'string', 'max:500']]),
                'id' => $contract->getKey(),
            ])
        );

        if (! $result->success) {
            return $this->actionFailureResponse($request, $result->errors, $result->message);
        }

        if ($request->expectsJson()) {
            return response()->json($contract->refresh());
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result->message,
        ]);

        return redirect()->route('contracts.index');
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    private function actionFailureResponse(Request $request, ?array $errors, ?string $message, int $status = 422): RedirectResponse|JsonResponse
    {
        $message ??= 'Não foi possível concluir a operação.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], $status);
        }

        return back()->withErrors($errors ?? ['contract' => $message])->withInput();
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
            ],
        ];
    }

    public function show(Request $request): Response|JsonResponse
    {
        $this->authorizeAccess(AccessAction::VIEW);

        $model = $this->modelFromRoute($request);

        if ($request->expectsJson()) {
            return response()->json($model);
        }

        $this->shareModuleRoutes();

        return Inertia::render($this->detailsComponent(), [
            $this->itemPropName() => $model,
            'id' => $model->getKey(),
            'routes' => [
                ...$this->getModuleRoutes(),
                'apply' => route('contracts.apply', ['contract' => $model]),
            ],
            ...$this->moduleDetailsProps($model),
        ]);
    }

    protected function moduleDetailsProps(?Model $model = null): array
    {
        $clientInfo = null;
        $couponInfo = null;
        $registration = null;
        $linkedLead = null;
        $applyRoute = null;

        if ($model instanceof Contract) {
            $model->load(['client', 'coupon']);
            $clientInfo = $model->client
                ? "{$model->client->name} - {$model->client->document}"
                : null;
            $couponInfo = $model->coupon?->code;

            if ($model->registration_token !== null && $model->client_id === null) {
                $registrationUrl = route('public.cadastro', ['contract' => $model->registration_token]);

                $registration = [
                    'url' => $registrationUrl,
                    'qr' => $this->qrCodeService->dataUri($registrationUrl),
                ];

                $applyRoute = route('contracts.apply', ['contract' => $model]);

                /** @var Collection<int, HiringLead> $leads */
                $leads = $model->hiringLeads()->latest('id')->get();
                $latestLead = $leads->first();

                if ($latestLead !== null) {
                    $linkedLead = [
                        'id' => $latestLead->id,
                        'name' => $latestLead->name,
                        'email' => $latestLead->email,
                        'phone' => $latestLead->phone,
                        'document' => $latestLead->document,
                        'gender' => $latestLead->gender,
                        'birth_date' => $latestLead->birth_date?->format('Y-m-d'),
                        'address' => $latestLead->address,
                        'address_number' => $latestLead->address_number,
                        'address_complement' => $latestLead->address_complement,
                        'address_district' => $latestLead->address_district,
                        'address_state' => $latestLead->address_state,
                        'address_city' => $latestLead->address_city,
                        'address_postal_code' => $latestLead->address_postal_code,
                        'status' => $latestLead->status->value,
                    ];
                }
            }
        }

        return [
            'cancelRoute' => $model instanceof Contract
                ? route('contracts.cancel', ['contract' => $model])
                : null,
            'applicationRoute' => $applyRoute,
            'registration' => $registration,
            'linkedLead' => $linkedLead,
            'clientInfo' => $clientInfo,
            'couponInfo' => $couponInfo,
            'options' => [
                'billableStatus' => $this->enumOptions(BillableStatus::class),
                'paymentMethods' => $this->enumOptions(PaymentMethod::class),
                'plans' => $this->planOptions(),
                'coupons' => $this->couponOptions(),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function planOptions(): array
    {
        return Plan::query()
            ->with(['planCategory', 'modalities.modality'])
            ->where('visibility', 'visible')
            ->orderBy('name')
            ->get()
            ->map(function (Plan $plan): array {
                return [
                    'value' => $plan->id,
                    'title' => $plan->name,
                    'category' => $plan->planCategory?->name,
                    'modality_quantity' => $plan->modalities()->count(),
                    'modalities' => $plan->modalities->map(fn ($pm) => [
                        'id' => $pm->modality->id,
                        'name' => $pm->modality->name,
                    ])->all(),
                    'price' => (float) $plan->price,
                    'duration_months' => $plan->duration_months,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function couponOptions(): array
    {
        return Coupon::query()
            ->where('visibility', 'visible')
            ->orderBy('code')
            ->get()
            ->map(function (Coupon $coupon): array {
                return [
                    'value' => $coupon->id,
                    'title' => $coupon->code,
                    'code' => $coupon->code,
                    'percent' => (float) $coupon->percent,
                    'discount_limit' => (float) ($coupon->discount_limit ?? 0),
                    'duration' => $coupon->duration,
                    'expiration_date' => $coupon->expiration_date?->format('Y-m-d'),
                ];
            })
            ->all();
    }
}
