<?php

namespace Tests\Feature;

use App\Models\GatewayAccount;
use App\Models\GatewayTransferRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayTransferRecipientModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_permission_can_create_gateway_transfer_recipients(): void
    {
        $user = User::factory()->create();
        $gatewayAccount = GatewayAccount::factory()->create();
        $this->grantPermission($user, 'gateway_transfer_recipients.create');

        $this->actingAs($user)
            ->postJson(route('gateway-transfer-recipients.store'), [
                'gateway_account_id' => $gatewayAccount->id,
                'label' => 'Personal trainer',
                'holder_name' => '  MARIA   DA   SILVA  ',
                'holder_document' => '12345678901',
                'pix_key' => 'maria@example.com',
                'pix_key_type' => 'EMAIL',
            ])
            ->assertCreated();

        $recipient = GatewayTransferRecipient::query()->sole();
        $this->assertSame('maria@example.com', $recipient->pix_key);
        $this->assertSame('Maria da Silva', $recipient->holder_name);
        $this->assertDatabaseHas('gateway_transfer_recipients', [
            'gateway_account_id' => $gatewayAccount->id,
            'label' => 'Personal trainer',
        ]);
    }

    public function test_users_with_permission_can_request_gateway_pix_transfers(): void
    {
        $user = User::factory()->create();
        $recipient = GatewayTransferRecipient::factory()->create();
        $this->grantPermission($user, 'gateway_transfers.create');

        Http::fake([
            'https://sandbox.asaas.com/api/v3/transfers' => Http::response([
                'id' => 'transfer_123',
                'value' => 150.00,
                'netValue' => 147.50,
                'status' => 'PENDING',
            ]),
        ]);

        $this->actingAs($user)
            ->postJson(route('gateway-transfers.store'), [
                'gateway_transfer_recipient_id' => $recipient->id,
                'value' => 150,
                'description' => 'Pagamento do instrutor',
            ])
            ->assertCreated()
            ->assertJsonPath('gateway_transfer_recipient_id', $recipient->id);

        $this->assertDatabaseHas('gateway_transfers', [
            'gateway_reference_key' => 'transfer_123',
            'gateway_transfer_recipient_id' => $recipient->id,
        ]);
    }
}
