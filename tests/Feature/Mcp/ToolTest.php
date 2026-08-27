<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ToolTest extends TestCase
{
    use RefreshDatabase;

    private function givePermission(User $user, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['name' => $permissionName, 'description' => $permissionName],
        );

        $user->permissions()->attach($permission);
    }

    private function mcpCall(User $user, string $method, array $params = []): TestResponse
    {
        return $this->actingAs($user)->postJson('/mcp/gymnamite', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);
    }

    private function isMcpError(array $body): bool
    {
        return $body['result']['isError'] ?? false;
    }

    private function mcpErrorMessage(array $body): string
    {
        return $body['result']['content'][0]['text'] ?? '';
    }

    public function test_server_initializes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/mcp/gymnamite', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'clientInfo' => ['name' => 'test', 'version' => '1.0'],
                'protocolVersion' => '2025-03-26',
            ],
        ]);

        $response->assertOk();
    }

    public function test_list_tools_returns_registered_tools(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'coupons.create');
        $this->givePermission($user, 'coupons.update');

        $response = $this->mcpCall($user, 'tools/list');

        $response->assertOk();

        $body = $response->json();
        $this->assertArrayHasKey('result', $body);
        $this->assertArrayHasKey('tools', $body['result']);
        $toolNames = array_column($body['result']['tools'], 'name');
        $this->assertContains('create-coupon', $toolNames);
        $this->assertContains('update-coupon', $toolNames);
    }

    public function test_all_43_tools_are_registered(): void
    {
        $user = User::factory()->create();

        $allPermissions = [
            'clients.create', 'clients.update',
            'contracts.create', 'contracts.update', 'contracts.cancel', 'contracts.view',
            'sales.create', 'sales.update',
            'purchases.create', 'purchases.update',
            'direct_lessons.create', 'direct_lessons.update',
            'plans.create', 'plans.update',
            'modalities.create', 'modalities.update',
            'products.create', 'products.update',
            'gateway_accounts.create', 'gateway_accounts.update',
            'gateway_transfers.create',
            'receivables.mark_paid', 'receivables.request_invoice',
            'coupons.create', 'coupons.update',
            'trainers.create', 'trainers.update',
            'suppliers.create', 'suppliers.update',
            'financial_categories.create', 'financial_categories.update',
            'cost_centers.create', 'cost_centers.update',
            'plan_categories.create', 'plan_categories.update',
            'financial_accounts.create', 'financial_accounts.update',
            'payables.create', 'payables.update',
            'users.update', 'settings.update',
            'hiring_leads.update',
        ];

        foreach ($allPermissions as $permission) {
            $this->givePermission($user, $permission);
        }

        $allToolNames = [];
        $cursor = null;

        do {
            $params = $cursor !== null ? ['cursor' => $cursor] : [];
            $response = $this->mcpCall($user, 'tools/list', $params);
            $response->assertOk();

            $body = $response->json();
            $pageTools = array_column($body['result']['tools'], 'name');
            $allToolNames = array_merge($allToolNames, $pageTools);

            $cursor = $body['result']['nextCursor'] ?? null;
        } while ($cursor !== null);

        $expectedTools = [
            'create-client', 'update-client',
            'create-contract', 'update-contract', 'cancel-contract', 'find-client-by-document',
            'create-sale', 'update-sale',
            'create-purchase', 'update-purchase',
            'create-direct-lesson', 'update-direct-lesson',
            'create-plan', 'update-plan',
            'create-modality', 'update-modality',
            'create-product', 'update-product',
            'create-gateway-account', 'update-gateway-account', 'configure-fiscal-data', 'create-gateway-transfer',
            'mark-receivable-paid', 'request-gateway-invoice',
            'create-coupon', 'update-coupon',
            'create-trainer', 'update-trainer',
            'create-supplier', 'update-supplier',
            'create-financial-category', 'update-financial-category',
            'create-cost-center', 'update-cost-center',
            'create-plan-category', 'update-plan-category',
            'create-financial-account', 'update-financial-account',
            'create-payable', 'update-payable',
            'save-user', 'update-role-permissions', 'update-settings',
            'convert-hiring-lead',
        ];

        $this->assertCount(44, $allToolNames, 'Expected 44 tools, got: '.implode(', ', $allToolNames));
        foreach ($expectedTools as $toolName) {
            $this->assertContains($toolName, $allToolNames, "Tool '{$toolName}' not registered");
        }
    }

    public function test_create_coupon_tool_works(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'coupons.create');

        $response = $this->mcpCall($user, 'tools/call', [
            'name' => 'create-coupon',
            'arguments' => [
                'code' => 'MCPTEST',
                'percent' => 15.0,
                'discount_limit' => 0,
                'duration' => '0',
            ],
        ]);

        $response->assertOk();

        $body = $response->json();
        $this->assertFalse($this->isMcpError($body), 'MCP error: '.$this->mcpErrorMessage($body));
        $this->assertDatabaseHas('coupons', ['code' => 'MCPTEST']);
    }

    public function test_create_coupon_tool_rejects_unauthorized_user(): void
    {
        $user = User::factory()->create();

        $response = $this->mcpCall($user, 'tools/call', [
            'name' => 'create-coupon',
            'arguments' => [
                'code' => 'NOACCESS',
                'percent' => 10.0,
            ],
        ]);

        $response->assertOk();

        $body = $response->json();
        $this->assertTrue($this->isMcpError($body) || ! isset($body['result']['content']), 'Expected error for unauthorized user. Response: '.json_encode($body));
    }

    public function test_create_coupon_tool_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'coupons.create');

        $response = $this->mcpCall($user, 'tools/call', [
            'name' => 'create-coupon',
            'arguments' => [],
        ]);

        $response->assertOk();

        $body = $response->json();
        $this->assertTrue($this->isMcpError($body), 'Expected validation error. Response: '.json_encode($body));
    }
}
