<?php

namespace Tests\Feature;

use App\AccessControl\AccessModule;
use App\AccessControl\AccessRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * UserSeeder creates the administrator without a role, so seeding alone used to
 * leave the account with no permissions and every module answering 403. The role
 * belongs to `access-control:sync`, which DatabaseSeeder now runs.
 */
class SeededAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['seeders.admin' => [
            'name' => 'Administrador',
            'email' => 'admin@test.com',
            'password' => 'senha-de-teste',
        ]]);
    }

    public function test_seeded_administrator_gets_a_role_with_permissions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@test.com')->firstOrFail();

        $this->assertSame(AccessRole::ADMINISTRATOR->value, $admin->role->name);
        $this->assertNotEmpty($admin->permissions);
    }

    public function test_seeded_administrator_passes_the_module_gates(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@test.com')->firstOrFail();

        $this->assertTrue(Gate::forUser($admin)->allows(AccessModule::DASHBOARD->value.'.view'));
    }

    /**
     * The symptom that started this: login succeeded and the dashboard answered
     * 403 because the seeded administrator had no permission rows.
     */
    public function test_seeded_administrator_logs_in_and_opens_the_dashboard(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->post(route('login.store'), [
            'email' => 'admin@test.com',
            'password' => 'senha-de-teste',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();

        $this->get(route('dashboard'))->assertOk();
    }
}
