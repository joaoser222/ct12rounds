<?php

namespace App\Http\Controllers;

use App\AccessControl\AccessAction;
use App\AccessControl\AccessModule;
use App\Jobs\SyncFullGatewayAccountJob;
use App\Jobs\SyncGatewayDataJob;
use App\Models\GatewayAccount;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class GatewayAccountSyncController extends Controller
{
    public function __invoke(Request $request, GatewayAccount $gatewayAccount): JsonResponse
    {
        Gate::authorize(AccessModule::GATEWAY_ACCOUNT->value.'.'.AccessAction::UPDATE->value);

        $lockOwner = Str::random(40);

        if (SyncGatewayDataJob::acquireAllLocks($lockOwner) === null) {
            return response()->json([
                'message' => GatewaySyncService::IN_PROGRESS_MESSAGE,
            ], 409);
        }

        try {
            SyncFullGatewayAccountJob::dispatch($gatewayAccount->getKey(), $lockOwner);
        } catch (\Throwable $exception) {
            SyncGatewayDataJob::releaseAllLocks($lockOwner);

            throw $exception;
        }

        return response()->json([
            'message' => 'Sincronização iniciada.',
            'gateway_account_id' => $gatewayAccount->getKey(),
        ], 202);
    }
}
