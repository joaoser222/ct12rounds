<?php

namespace App\Jobs;

use App\Models\GatewayAccount;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncFullGatewayAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    /**
     * Stages run in dependency order. A failure aborts the remaining stages.
     *
     * @var array<int, string>
     */
    public const STAGES = ['customers', 'payments', 'transfers', 'postbacks'];

    public function __construct(
        public readonly int $gatewayAccountId,
        public readonly string $lockOwner,
    ) {}

    public function handle(GatewaySyncService $syncService): void
    {
        $account = GatewayAccount::query()->find($this->gatewayAccountId);

        if ($account === null) {
            SyncGatewayDataJob::releaseAllLocks($this->lockOwner);

            return;
        }

        try {
            foreach (self::STAGES as $scope) {
                try {
                    $stats = $syncService->sync($account, $scope);

                    Log::info('Gateway full sync stage completed', [
                        'scope' => $scope,
                        'gateway_account_id' => $this->gatewayAccountId,
                        'stats' => $stats,
                    ]);
                } catch (\Throwable $exception) {
                    Log::error('Gateway full sync stage failed', [
                        'scope' => $scope,
                        'gateway_account_id' => $this->gatewayAccountId,
                        'error' => $exception->getMessage(),
                    ]);

                    throw $exception;
                }
            }
        } finally {
            SyncGatewayDataJob::releaseAllLocks($this->lockOwner);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        SyncGatewayDataJob::releaseAllLocks($this->lockOwner);

        Log::error('Gateway full sync job failed', [
            'gateway_account_id' => $this->gatewayAccountId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
