<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Actions\GatewayTransfers\CreateGatewayTransferAction;
use App\Enums\Gateway\TransactionStatus;
use App\Models\GatewayTransfer;
use App\Models\GatewayTransferRecipient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\GatewayTransferRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GatewayTransferController extends ReadOnlyModuleController
{
    public function __construct(private readonly CreateGatewayTransferAction $createGatewayTransfer) {}

    /**
     * @var array<int, string>
     */
    protected array $fields = [
        'id',
        'gateway_reference_key',
        'gross_value',
        'fee_value',
        'total',
        'status',
        'created_at',
    ];

    /**
     * @var array<int, string>
     */
    protected array $searchableFields = ['gateway_reference_key', 'status'];

    /**
     * @var array<int, string>
     */
    protected array $sortableFields = ['id', 'gross_value', 'fee_value', 'total', 'status', 'created_at'];

    protected function accessModule(): AccessModule
    {
        return AccessModule::GATEWAY_TRANSFER;
    }

    protected function modelClass(): string
    {
        return GatewayTransfer::class;
    }

    protected function moduleIndexProps(Request $request): array
    {
        return [
            'options' => [
                'transactionStatus' => $this->enumOptions(TransactionStatus::class),
            ],
        ];
    }

    protected function getModuleRoutes(): array
    {
        return [
            ...parent::getModuleRoutes(),
            'create' => route('gateway-transfers.create'),
            'store' => route('gateway-transfers.store'),
        ];
    }

    public function create(): Response
    {
        $this->authorizeAccess(AccessAction::CREATE);

        return Inertia::render('gateway_transfers/Request', [
            'recipients' => GatewayTransferRecipient::query()
                ->where('visibility', 'visible')
                ->orderBy('label')
                ->get(['id', 'label', 'holder_name'])
                ->map(fn (GatewayTransferRecipient $recipient): array => [
                    'value' => $recipient->id,
                    'label' => "{$recipient->label} - {$recipient->holder_name}",
                ])
                ->all(),
            'routes' => $this->getModuleRoutes(),
        ]);
    }

    public function store(GatewayTransferRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAccess(AccessAction::CREATE);

        $transfer = $this->createGatewayTransfer->execute($request->validated());

        if ($request->expectsJson()) {
            return response()->json($transfer, 201);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Transferência solicitada com sucesso.',
        ]);

        return redirect()->route('gateway-transfers.index');
    }
}
