<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Enums\HiringLeadStatus;
use App\Models\HiringLead;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ConvertHiringLeadToolTest extends TestCase
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

    private function listToolNames(User $user): array
    {
        $names = [];
        $cursor = null;

        do {
            $params = $cursor !== null ? ['cursor' => $cursor] : [];
            $body = $this->mcpCall($user, 'tools/list', $params)->json();
            $names = array_merge($names, array_column($body['result']['tools'], 'name'));
            $cursor = $body['result']['nextCursor'] ?? null;
        } while ($cursor !== null);

        return $names;
    }

    public function test_convert_tool_requires_update_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'hiring_leads.view');

        $this->assertNotContains('convert-hiring-lead', $this->listToolNames($user));
    }

    public function test_convert_tool_registers_with_update_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'hiring_leads.update');

        $this->assertContains('convert-hiring-lead', $this->listToolNames($user));
    }

    public function test_convert_tool_converts_lead_into_client(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'hiring_leads.update');

        $lead = HiringLead::factory()->contractFlow()->create();

        $response = $this->mcpCall($user, 'tools/call', [
            'name' => 'convert-hiring-lead',
            'arguments' => [
                'id' => $lead->id,
            ],
        ]);

        $response->assertOk();

        $contents = $response->json('result.content', []);
        $this->assertNotEmpty($contents);

        $text = is_array($contents) && isset($contents[0]['text']) ? $contents[0]['text'] : '';
        $payload = json_decode((string) $text, true);

        $this->assertArrayHasKey('client_id', $payload);

        $lead->refresh();
        $this->assertEquals(HiringLeadStatus::CONVERTED, $lead->status);
        $this->assertNotNull($lead->client_id);
    }

    public function test_convert_tool_rejects_missing_lead(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'hiring_leads.update');

        $response = $this->mcpCall($user, 'tools/call', [
            'name' => 'convert-hiring-lead',
            'arguments' => [
                'id' => 999999,
            ],
        ]);

        $response->assertOk();

        $body = $response->json();
        $this->assertTrue($body['result']['isError'] ?? false, 'Expected MCP error for missing lead. Response: '.json_encode($body));
    }
}
