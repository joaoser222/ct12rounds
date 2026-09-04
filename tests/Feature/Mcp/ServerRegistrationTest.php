<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ServerRegistrationTest extends TestCase
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
        return $this->actingAs($user)->postJson('/mcp/ct12rounds', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ]);
    }

    public function test_server_registers_all_tools_and_resources(): void
    {
        $user = User::factory()->create();

        $permissions = [
            'clients.create', 'clients.update', 'clients.view',
            'contracts.create', 'contracts.update', 'contracts.cancel', 'contracts.view',
            'sales.create', 'sales.update', 'sales.view',
            'purchases.create', 'purchases.update', 'purchases.view',
            'direct_lessons.create', 'direct_lessons.update', 'direct_lessons.view',
            'plans.create', 'plans.update', 'plans.view',
            'modalities.create', 'modalities.update', 'modalities.view',
            'products.create', 'products.update', 'products.view',
            'gateway_accounts.create', 'gateway_accounts.update', 'gateway_accounts.view',
            'gateway_transfers.create',
            'receivables.mark_paid', 'receivables.request_invoice', 'receivables.view',
            'payables.create', 'payables.update', 'payables.view',
            'coupons.create', 'coupons.update',
            'trainers.create', 'trainers.update',
            'suppliers.create', 'suppliers.update',
            'financial_categories.create', 'financial_categories.update',
            'cost_centers.create', 'cost_centers.update',
            'plan_categories.create', 'plan_categories.update',
            'financial_accounts.create', 'financial_accounts.update',
            'users.update', 'settings.update',
            'gateway_invoices.view',
            'movements.view',
            'hiring_leads.update', 'hiring_leads.view',
        ];

        foreach ($permissions as $permission) {
            $this->givePermission($user, $permission);
        }

        $toolNames = [];
        $cursor = null;
        do {
            $params = $cursor !== null ? ['cursor' => $cursor] : [];
            $response = $this->mcpCall($user, 'tools/list', $params);
            $response->assertOk();
            $body = $response->json();
            $toolNames = array_merge($toolNames, array_column($body['result']['tools'], 'name'));
            $cursor = $body['result']['nextCursor'] ?? null;
        } while ($cursor !== null);

        $this->assertCount(44, $toolNames, 'Expected 44 tools, got: '.implode(', ', $toolNames));

        $concrete = $this->mcpCall($user, 'resources/list')->json('result.resources', []);
        $templates = $this->mcpCall($user, 'resources/templates/list')->json('result.resourceTemplates', []);
        $resourceNames = array_merge(
            array_column($concrete, 'name'),
            array_column($templates, 'name'),
        );

        $this->assertCount(23, $resourceNames, 'Expected 23 resources, got: '.implode(', ', $resourceNames));
    }

    public function test_server_registers_prompts_for_permitted_user(): void
    {
        $user = User::factory()->create();

        foreach ([
            'clients.create',
            'sales.create',
            'receivables.mark_paid',
            'receivables.view',
            'trainers.create',
        ] as $permission) {
            $this->givePermission($user, $permission);
        }

        $response = $this->mcpCall($user, 'prompts/list');
        $response->assertOk();

        $promptNames = array_column($response->json('result.prompts', []), 'name');

        $expected = [
            'onboard-client',
            'register-sale',
            'collect-receivable',
            'financial-overview',
            'register-trainer',
        ];

        $this->assertCount(5, $promptNames, 'Expected 5 prompts, got: '.implode(', ', $promptNames));
        $this->assertEqualsCanonicalizing($expected, $promptNames);
    }

    public function test_server_hides_prompts_without_permission(): void
    {
        $user = User::factory()->create();

        $response = $this->mcpCall($user, 'prompts/list');
        $response->assertOk();

        $this->assertCount(0, $response->json('result.prompts', []));
    }
}
