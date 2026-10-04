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
     * Lock lifetime must outlive the longest running sync job, which is
     * SyncFullGatewayAccountJob::$timeout. Keep it in sync with that value
     * and with the queue retry_after setting.
     */
    public const LOCK_TTL = 1900;

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
            $accounts = GatewayAccount::query()
                ->whereIn('id', $this->gatewayAccountIds)
                ->get()
                ->keyBy('id');

            foreach ($this->gatewayAccountIds as $accountId) {
                $account = $accounts->get($accountId);

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
            $lock = Cache::lock(self::lockName($scope), self::LOCK_TTL, $lockOwner);

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
            Cache::lock(self::lockName($scope), self::LOCK_TTL, $lockOwner)->forceRelease();
        }
    }
}
