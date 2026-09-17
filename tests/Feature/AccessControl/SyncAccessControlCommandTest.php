<?php

namespace Tests\Feature\AccessControl;

use App\AccessControl\AccessModule;
use App\AccessControl\AccessRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncAccessControlCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_roles_permissions_role_permissions_and_users(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->artisan('access-control:sync')
            ->assertSuccessful();

        $this->assertSame(AccessRole::cases(), array_map(
            fn (Role $role) => AccessRole::from($role->name),
            Role::query()->orderBy('id')->get()->all(),
        ));

        $this->assertDatabaseHas('permissions', [
            'name' => 'clients.view',
            'description' => 'Pessoas - Ver',
        ]);

        $administrator = Role::query()
            ->where('name', AccessRole::ADMINISTRATOR->value)
            ->firstOrFail();

        $this->assertTrue($administrator->permissions()->where('name', 'clients.delete')->exists());

        $this->assertSame(
            $administrator->id,
            $user->fresh()->role_id,
        );

        $this->assertEqualsCanonicalizing(
            $administrator->permissions()->pluck('permissions.id')->all(),
            $user->fresh()->permissions()->pluck('permissions.id')->all(),
        );
    }

    public function test_it_is_idempotent(): void
    {
        $this->artisan('access-control:sync')->assertSuccessful();

        $roleCount = Role::query()->count();
        $permissionCount = Permission::query()->count();
        $permissionRoleCount = $this->permissionRoleCount();
        $permissionUserCount = $this->permissionUserCount();

        $this->artisan('access-control:sync')->assertSuccessful();

        $this->assertSame($roleCount, Role::query()->count());
        $this->assertSame($permissionCount, Permission::query()->count());
        $this->assertSame($permissionRoleCount, $this->permissionRoleCount());
        $this->assertSame($permissionUserCount, $this->permissionUserCount());
    }

    public function test_it_can_skip_user_updates(): void
    {
        $user = User::factory()->create(['role_id' => null]);

        $this->artisan('access-control:sync --without-users')
            ->assertSuccessful();

        $this->assertNull($user->fresh()->role_id);
        $this->assertSame([], $user->fresh()->permissions()->pluck('permissions.id')->all());
    }

    public function test_it_preserves_existing_role_and_user_permissions(): void
    {
        $role = Role::query()->create([
            'name' => AccessRole::MANAGER->value,
            'description' => AccessRole::MANAGER->label(),
        ]);

        $rolePermission = Permission::query()->create([
            'name' => 'custom.role.permission',
            'description' => 'Custom role permission',
        ]);
        $userPermission = Permission::query()->create([
            'name' => 'custom.user.permission',
            'description' => 'Custom user permission',
        ]);
        $role->permissions()->attach($rolePermission);

        $user = User::factory()->create(['role_id' => $role->id]);
        $user->permissions()->attach($userPermission);

        $this->artisan('access-control:sync --without-users')
            ->assertSuccessful();

        $this->assertTrue($role->fresh()->permissions()->whereKey($rolePermission->id)->exists());
        $this->assertTrue($user->fresh()->permissions()->whereKey($userPermission->id)->exists());
    }

    public function test_it_can_reset_role_permissions_and_apply_only_the_delta_to_users(): void
    {
        $this->artisan('access-control:sync --without-users')->assertSuccessful();

        $role = Role::query()
            ->where('name', AccessRole::MANAGER->value)
            ->firstOrFail();
        $previousPermissionIds = $role->permissions()->pluck('permissions.id')->all();
        $removedPermissionId = $previousPermissionIds[0];
        $customPermission = Permission::query()->create([
            'name' => 'custom.user.permission',
            'description' => 'Custom user permission',
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);
        $user->permissions()->attach(array_values(array_filter(
            [...$previousPermissionIds, $customPermission->id],
            fn (int $permissionId): bool => $permissionId !== $removedPermissionId,
        )));
        $role->permissions()->detach($removedPermissionId);

        $this->artisan('access-control:sync --without-users --reset-role-permissions')
            ->assertSuccessful();

        $this->assertTrue($role->fresh()->permissions()->whereKey($removedPermissionId)->exists());
        $this->assertTrue($user->fresh()->permissions()->whereKey($removedPermissionId)->exists());
        $this->assertTrue($user->fresh()->permissions()->whereKey($customPermission->id)->exists());
    }

    public function test_it_fails_with_invalid_default_role(): void
    {
        $this->artisan('access-control:sync --default-role=invalid')
            ->assertFailed();
    }

    public function test_it_creates_all_module_action_permissions(): void
    {
        $this->artisan('access-control:sync --without-users')->assertSuccessful();

        $expectedPermissionNames = collect(AccessModule::cases())
            ->flatMap(fn (AccessModule $module) => collect($module->actions())
                ->map(fn ($action) => $module->value.'.'.$action->value))
            ->sort()
            ->values()
            ->all();

        $permissionNames = Permission::query()
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedPermissionNames, $permissionNames);
    }

    private function permissionRoleCount(): int
    {
        return (int) Role::query()
            ->withCount('permissions')
            ->get()
            ->sum('permissions_count');
    }

    private function permissionUserCount(): int
    {
        return (int) User::query()
            ->withCount('permissions')
            ->get()
            ->sum('permissions_count');
    }
}
