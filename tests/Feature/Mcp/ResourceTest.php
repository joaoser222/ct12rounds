<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Models\Client;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ResourceTest extends TestCase
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

    private function listAllResourceNames(User $user): array
    {
        $concrete = $this->mcpCall($user, 'resources/list')->json('result.resources', []);
        $templates = $this->mcpCall($user, 'resources/templates/list')->json('result.resourceTemplates', []);

        return array_merge(
            array_column($concrete, 'name'),
            array_column($templates, 'name'),
        );
    }

    public function test_list_resources_returns_all_registered_resources(): void
    {
        $user = User::factory()->create();

        $viewPermissions = [
            'clients.view', 'contracts.view', 'gateway_invoices.view', 'sales.view',
            'purchases.view', 'direct_lessons.view', 'plans.view', 'modalities.view',
            'products.view', 'receivables.view', 'payables.view', 'movements.view',
            'gateway_accounts.view', 'hiring_leads.view',
        ];

        foreach ($viewPermissions as $permission) {
            $this->givePermission($user, $permission);
        }

        $names = $this->listAllResourceNames($user);

        $expected = [
            'client', 'contract', 'invoice', 'sale', 'purchase', 'direct-lesson',
            'plan', 'modality', 'product', 'receivable', 'payable', 'movement', 'gateway-account',
            'clients', 'contracts', 'invoices', 'sales', 'purchases',
            'receivables-pending', 'payables-pending', 'receivables-overdue', 'movements-range', 'hiring-leads',
        ];

        $this->assertCount(23, $names, 'Expected 23 resources, got: '.implode(', ', $names));
        foreach ($expected as $name) {
            $this->assertContains($name, $names, "Resource '{$name}' not registered");
        }
    }

    public function test_client_resource_reads_record(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'clients.view');

        $client = Client::factory()->create();

        $response = $this->mcpCall($user, 'resources/read', [
            'uri' => "gymnamite://clients/{$client->id}",
        ]);

        $response->assertOk();

        $body = $response->json();
        $contents = $body['result']['contents'] ?? [];

        $this->assertNotEmpty($contents, 'Expected resource content');
        $data = json_decode($contents[0]['text'], true);
        $this->assertEquals($client->id, $data['id']);
    }

    public function test_movements_range_resource_accepts_date_template(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'movements.view');

        $response = $this->mcpCall($user, 'resources/read', [
            'uri' => 'gymnamite://movements/range/2026-01-01/2026-01-31',
        ]);

        $response->assertOk();

        $body = $response->json();
        $contents = $body['result']['contents'] ?? [];

        $this->assertNotEmpty($contents);
        $this->assertIsArray(json_decode($contents[0]['text'], true));
    }

    public function test_resource_is_hidden_without_permission(): void
    {
        $user = User::factory()->create();

        $names = $this->listAllResourceNames($user);

        $this->assertNotContains('client', $names);
        $this->assertNotContains('clients', $names);
    }

    public function test_resource_read_rejects_unauthorized_user(): void
    {
        $user = User::factory()->create();

        $response = $this->mcpCall($user, 'resources/read', [
            'uri' => 'gymnamite://clients/1',
        ]);

        $response->assertOk();

        $body = $response->json();
        $this->assertArrayHasKey('error', $body, 'Expected JSON-RPC error for unauthorized resource read');
    }
}
