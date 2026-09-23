<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Jobs\SyncGatewayDataJob;
use App\Models\GatewayAccount;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GatewaySyncController extends Controller
{
    public function __invoke(Request $request, string $scope): JsonResponse
    {
        Gate::authorize(AccessModule::GATEWAY_ACCOUNT->value.'.'.AccessAction::UPDATE->value);

        if (! in_array($scope, ['payments', 'transfers', 'customers', 'postbacks'], true)) {
            throw new InvalidArgumentException("Unsupported gateway sync scope [{$scope}].");
        }

        $accountIds = GatewayAccount::query()->pluck('id')->all();

        if ($accountIds === []) {
            return response()->json([
                'message' => 'Nenhuma conta de gateway configurada.',
            ], 422);
        }

        $lockOwner = Str::random(40);

        if (SyncGatewayDataJob::acquireAllLocks($lockOwner) === null) {
            return response()->json([
                'message' => GatewaySyncService::IN_PROGRESS_MESSAGE,
            ], 409);
        }

        try {
            SyncGatewayDataJob::dispatch($accountIds, $scope, $lockOwner);
        } catch (\Throwable $exception) {
            SyncGatewayDataJob::releaseAllLocks($lockOwner);

            throw $exception;
        }

        return response()->json([
            'message' => 'Sincronização iniciada.',
            'scope' => $scope,
        ], 202);
    }
}
