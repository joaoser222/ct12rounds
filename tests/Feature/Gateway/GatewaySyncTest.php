<?php

namespace Tests\Feature\Gateway;

use App\Enums\Gateway\PostbackStatus;
use App\Enums\Gateway\TransactionStatus;
use App\Enums\PaymentMethod;
use App\Jobs\SyncGatewayDataJob;
use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayCustomer;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\User;
use App\Services\Gateway\GatewaySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class GatewaySyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
    }

    private function asaasAccount(): GatewayAccount
    {
        return GatewayAccount::factory()->create([
            'name' => 'Asaas',
            'settings' => [
                'api_key' => 'test-api-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
        ]);
    }

    private function userWithSyncPermission(): User
    {
        $user = User::factory()->create();
        $this->grantPermission($user, 'gateway_accounts.update');

        return $user;
    }

    private function makeGatewayPayment(GatewayAccount $account, string $referenceKey, TransactionStatus $status): GatewayPayment
    {
        $client = Client::factory()->create();
        $customer = GatewayCustomer::query()->create([
            'gateway_reference_key' => 'cus_'.$referenceKey,
            'holder_id' => $client->id,
            'holder_type' => $client->getMorphClass(),
            'gateway_account_id' => $account->id,
        ]);

        return GatewayPayment::query()->create([
            'gateway_reference_key' => $referenceKey,
            'gateway_account_id' => $account->id,
            'gateway_customer_id' => $customer->id,
            'status' => $status,
            'gross_value' => 100.00,
            'fee_value' => 5.00,
            'payment_method' => PaymentMethod::PIX->value,
            'payment_date' => '2026-01-10',
        ]);
    }

    public function test_sync_endpoint_requires_update_permission(): void
    {
        $this->asaasAccount();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('gateway.sync', 'payments'));

        $response->assertForbidden();
    }

    public function test_sync_endpoint_queues_job_and_returns_accepted(): void
    {
        Queue::fake();
        $account = $this->asaasAccount();
        $user = $this->userWithSyncPermission();

        $response = $this->actingAs($user)->postJson(route('gateway.sync', 'payments'));

        $response->assertStatus(202)->assertJsonPath('scope', 'payments');
        Queue::assertPushed(SyncGatewayDataJob::class, function (SyncGatewayDataJob $job) use ($account): bool {
            return $job->scope === 'payments'
                && $job->gatewayAccountIds === [$account->id];
        });
    }

    public function test_sync_endpoint_rejects_when_lock_is_held(): void
    {
        Queue::fake();
        $this->asaasAccount();
        $user = $this->userWithSyncPermission();

        Cache::lock(SyncGatewayDataJob::lockName('payments'), 600, Str::random(40))->get();

        $response = $this->actingAs($user)->postJson(route('gateway.sync', 'payments'));

        $response->assertStatus(409)->assertJsonPath(
            'message',
            GatewaySyncService::IN_PROGRESS_MESSAGE,
        );
        Queue::assertNothingPushed();
    }

    public function test_full_sync_endpoint_requires_update_permission(): void
    {
        $account = $this->asaasAccount();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('gateway-accounts.sync', $account));

        $response->assertForbidden();
    }

    public function test_full_sync_endpoint_queues_job_and_returns_accepted(): void
    {
        Queue::fake();
        $account = $this->asaasAccount();
        $user = $this->userWithSyncPermission();

        $response = $this->actingAs($user)->postJson(route('gateway-accounts.sync', $account));

        $response->assertStatus(202)->assertJsonPath('gateway_account_id', $account->id);
        Queue::assertPushed(
            \App\Jobs\SyncFullGatewayAccountJob::class,
            fn (\App\Jobs\SyncFullGatewayAccountJob $job): bool => $job->gatewayAccountId === $account->id,
        );
    }

    public function test_full_sync_endpoint_rejects_when_any_sync_lock_is_held(): void
    {
        Queue::fake();
        $account = $this->asaasAccount();
        $user = $this->userWithSyncPermission();

        Cache::lock(SyncGatewayDataJob::lockName('transfers'), 600, Str::random(40))->get();

        $response = $this->actingAs($user)->postJson(route('gateway-accounts.sync', $account));

        $response->assertStatus(409)->assertJsonPath(
            'message',
            GatewaySyncService::IN_PROGRESS_MESSAGE,
        );
        Queue::assertNothingPushed();
    }

    public function test_partial_sync_rejects_when_full_sync_holds_all_locks(): void
    {
        Queue::fake();
        $this->asaasAccount();
        $user = $this->userWithSyncPermission();

        $lockOwner = Str::random(40);
        SyncGatewayDataJob::acquireAllLocks($lockOwner);

        $response = $this->actingAs($user)->postJson(route('gateway.sync', 'payments'));

        $response->assertStatus(409)->assertJsonPath(
            'message',
            GatewaySyncService::IN_PROGRESS_MESSAGE,
        );
        Queue::assertNothingPushed();

        SyncGatewayDataJob::releaseAllLocks($lockOwner);
    }

    public function test_full_sync_job_runs_stages_in_order_and_releases_locks(): void
    {
        $account = $this->asaasAccount();
        $lockOwner = Str::random(40);
        $this->assertNotNull(SyncGatewayDataJob::acquireAllLocks($lockOwner));

        $order = [];
        $service = Mockery::mock(GatewaySyncService::class);
        $service
            ->shouldReceive('sync')
            ->andReturnUsing(function ($receivedAccount, string $scope) use (&$order, $account): array {
                $this->assertSame($account->id, $receivedAccount->id);
                $order[] = $scope;

                return [];
            });

        $job = new \App\Jobs\SyncFullGatewayAccountJob($account->id, $lockOwner);
        $job->handle($service);

        $this->assertSame(
            ['customers', 'payments', 'transfers', 'postbacks'],
            $order,
        );

        foreach (GatewaySyncService::SCOPES as $scope) {
            $this->assertTrue(
                Cache::lock(SyncGatewayDataJob::lockName($scope), 600)->get(),
                "Lock for scope [{$scope}] was not released.",
            );
        }
    }

    public function test_full_sync_job_aborts_remaining_stages_on_failure(): void
    {
        $account = $this->asaasAccount();
        $lockOwner = Str::random(40);
        $this->assertNotNull(SyncGatewayDataJob::acquireAllLocks($lockOwner));

        $order = [];
        $service = Mockery::mock(GatewaySyncService::class);
        $service
            ->shouldReceive('sync')
            ->andReturnUsing(function ($receivedAccount, string $scope) use (&$order): array {
                $order[] = $scope;

                if ($scope === 'payments') {
                    throw new \RuntimeException('payments stage failed');
                }

                return [];
            })
            ->byDefault();

        $job = new \App\Jobs\SyncFullGatewayAccountJob($account->id, $lockOwner);

        try {
            $job->handle($service);
            $this->fail('Expected full sync job to abort after stage failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('payments stage failed', $exception->getMessage());
        }

        $this->assertSame(['customers', 'payments'], $order);

        foreach (GatewaySyncService::SCOPES as $scope) {
            $this->assertTrue(
                Cache::lock(SyncGatewayDataJob::lockName($scope), 600)->get(),
                "Lock for scope [{$scope}] was not released after failure.",
            );
        }
    }

    public function test_sync_endpoint_returns_unprocessable_when_there_is_no_gateway_account(): void
    {
        $user = $this->userWithSyncPermission();

        $response = $this->actingAs($user)->postJson(route('gateway.sync', 'payments'));

        $response->assertStatus(422);
    }

    public function test_sync_job_releases_lock_after_completion(): void
    {
        $account = $this->asaasAccount();
        $scope = 'customers';
        $lockOwner = Str::random(40);
        $lock = Cache::lock(SyncGatewayDataJob::lockName($scope), 600, $lockOwner);
        $lock->get();

        Http::fake([
            'sandbox.asaas.com/api/v3/customers*' => Http::response([
                'object' => 'list',
                'hasMore' => false,
                'data' => [],
            ]),
        ]);

        $job = new SyncGatewayDataJob([$account->id], $scope, $lockOwner);
        $job->handle(app(\App\Services\Gateway\GatewaySyncService::class));

        $this->assertTrue(
            Cache::lock(SyncGatewayDataJob::lockName($scope), 600)->get(),
        );
    }

    public function test_sync_job_refreshes_local_payment_status(): void
    {
        $account = $this->asaasAccount();

        $payment = $this->makeGatewayPayment($account, 'pay_1', TransactionStatus::PENDING);

        Http::fake([
            'sandbox.asaas.com/api/v3/payments/pay_1' => Http::response([
                'id' => 'pay_1',
                'status' => 'RECEIVED',
                'value' => 100.00,
                'netValue' => 95.00,
                'billingType' => 'PIX',
                'paymentDate' => '2026-01-10',
                'dueDate' => '2026-01-10',
            ]),
            'sandbox.asaas.com/api/v3/payments*' => Http::response([
                'object' => 'list',
                'hasMore' => false,
                'data' => [],
            ]),
        ]);

        $scope = 'payments';
        $lockOwner = Str::random(40);
        $lock = Cache::lock(SyncGatewayDataJob::lockName($scope), 600, $lockOwner);
        $lock->get();

        $job = new SyncGatewayDataJob([$account->id], $scope, $lockOwner);
        $job->handle(app(\App\Services\Gateway\GatewaySyncService::class));

        $this->assertSame(
            TransactionStatus::PAID,
            $payment->fresh()->status,
        );
    }

    public function test_sync_job_reprocesses_failed_postbacks(): void
    {
        $account = $this->asaasAccount();

        $payment = $this->makeGatewayPayment($account, 'pay_missing', TransactionStatus::PENDING);

        $postback = GatewayPostback::query()->create([
            'gateway_account_id' => $account->id,
            'postback_event' => 'PAYMENT_RECEIVED',
            'postback_type' => 'PAYMENT',
            'external_event_key' => 'evt_1',
            'payload' => [
                'event' => 'PAYMENT_RECEIVED',
                'payment' => [
                    'id' => 'pay_missing',
                    'status' => 'RECEIVED',
                    'value' => 10.00,
                    'netValue' => 10.00,
                    'billingType' => 'PIX',
                ],
            ],
            'status' => PostbackStatus::FAILED,
        ]);

        $scope = 'postbacks';
        $lockOwner = Str::random(40);
        $lock = Cache::lock(SyncGatewayDataJob::lockName($scope), 600, $lockOwner);
        $lock->get();

        $job = new SyncGatewayDataJob([$account->id], $scope, $lockOwner);
        $job->handle(app(\App\Services\Gateway\GatewaySyncService::class));

        $this->assertSame(PostbackStatus::SUCCESS, $postback->fresh()->status);
        $this->assertSame(TransactionStatus::PAID, $payment->fresh()->status);
    }
}
