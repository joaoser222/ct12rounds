<?php

namespace App\Jobs;

use App\Models\GatewayAccount;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncGatewayDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 600;

    /**
     * @param  array<int, int>  $gatewayAccountIds
     * @param  array<string, int>  $stats
     */
    public function __construct(
        public readonly array $gatewayAccountIds,
        public readonly string $scope,
        public readonly string $lockOwner,
    ) {}

    public function handle(GatewaySyncService $syncService): void
    {
        try {
            foreach ($this->gatewayAccountIds as $accountId) {
                $account = GatewayAccount::query()->find($accountId);

                if ($account === null) {
                    continue;
                }

                $stats = $syncService->sync($account, $this->scope);

                Log::info('Gateway sync completed', [
                    'scope' => $this->scope,
                    'gateway_account_id' => $accountId,
                    'stats' => $stats,
                ]);
            }
        } finally {
            self::releaseAllLocks($this->lockOwner);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        self::releaseAllLocks($this->lockOwner);

        Log::error('Gateway sync job failed', [
            'scope' => $this->scope,
            'error' => $exception?->getMessage(),
        ]);
    }

    public static function lockName(string $scope): string
    {
        return "gateway-sync:{$scope}";
    }

    /**
     * Acquires every sync scope lock so only one gateway sync operation can run at a time.
     *
     * @return array<int, Lock>|null null when any lock is already held
     */
    public static function acquireAllLocks(string $lockOwner): ?array
    {
        $locks = [];

        foreach (GatewaySyncService::SCOPES as $scope) {
            $lock = Cache::lock(self::lockName($scope), 600, $lockOwner);

            if (! $lock->get()) {
                foreach ($locks as $heldLock) {
                    $heldLock->forceRelease();
                }

                return null;
            }

            $locks[] = $lock;
        }

        return $locks;
    }

    public static function releaseAllLocks(string $lockOwner): void
    {
        foreach (GatewaySyncService::SCOPES as $scope) {
            Cache::lock(self::lockName($scope), 600, $lockOwner)->forceRelease();
        }
    }
}
