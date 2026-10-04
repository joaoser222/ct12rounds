<?php

namespace Tests\Feature\Actions;

use App\Actions\GatewayPostbacks\ProcessGatewayPostbackAction;
use App\DTOs\GatewayPostbacks\ProcessGatewayPostbackDTO;
use App\Models\GatewayAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessGatewayPostbackActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function makeAccount(string $name = 'Asaas'): GatewayAccount
    {
        return GatewayAccount::query()->create([
            'name' => $name,
            'description' => "$name Account",
            'invoicing_enabled' => true,
            'settings' => [
                'api_key' => 'test-key',
                'base_url' => 'https://sandbox.asaas.com/api/v3',
            ],
            'visibility' => 'visible',
        ]);
    }

    public function test_processes_asaas_payment_created_postback(): void
    {
        $account = $this->makeAccount('Asaas');

        Http::fake([
            'sandbox.asaas.com/api/v3/payments/*' => Http::response([
                'id' => 'pay_123',
                'status' => 'CONFIRMED',
                'value' => 100,
            ]),
        ]);

        $action = app(ProcessGatewayPostbackAction::class);
        $dto = new ProcessGatewayPostbackDTO(
            gateway_account_id: $account->id,
            payload: [
                'payment' => [
                    'id' => 'pay_123',
                    'status' => 'CONFIRMED',
                    'value' => 100,
                ],
            ],
        );

        $result = $action->execute($dto);

        $this->assertNotNull($result);
        $this->assertDatabaseHas('gateway_postbacks', [
            'gateway_account_id' => $account->id,
        ]);
    }

    public function test_throws_for_unsupported_provider(): void
    {
        $account = $this->makeAccount('UnknownProvider');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $action = app(ProcessGatewayPostbackAction::class);
        $dto = new ProcessGatewayPostbackDTO(
            gateway_account_id: $account->id,
            payload: ['event' => 'test'],
        );

        $action->execute($dto);
    }

    public function test_throws_when_account_not_found(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $action = app(ProcessGatewayPostbackAction::class);
        $dto = new ProcessGatewayPostbackDTO(
            gateway_account_id: 999999,
            payload: ['event' => 'test'],
        );

        $action->execute($dto);
    }
}
