<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_administrator_from_the_configured_credentials(): void
    {
        config(['seeders.admin' => [
            'name' => 'Administrador',
            'email' => 'admin@ct12rounds.com',
            'password' => 'uma-senha-forte',
        ]]);

        $this->seed(UserSeeder::class);

        $user = User::query()->where('email', 'admin@ct12rounds.com')->firstOrFail();

        $this->assertSame('Administrador', $user->name);
        $this->assertTrue(Hash::check('uma-senha-forte', $user->password));
        $this->assertNotSame('uma-senha-forte', $user->password);
    }

    public function test_it_leaves_the_role_for_the_access_control_sync(): void
    {
        config(['seeders.admin' => [
            'name' => 'Administrador',
            'email' => 'admin@ct12rounds.com',
            'password' => 'uma-senha-forte',
        ]]);

        $this->seed(UserSeeder::class);

        $user = User::query()->where('email', 'admin@ct12rounds.com')->firstOrFail();

        $this->assertNull($user->role_id);
    }

    public function test_it_is_idempotent(): void
    {
        config(['seeders.admin' => [
            'name' => 'Administrador',
            'email' => 'admin@ct12rounds.com',
            'password' => 'uma-senha-forte',
        ]]);

        $this->seed(UserSeeder::class);
        $this->seed(UserSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@ct12rounds.com')->count());
    }

    public function test_it_rotates_the_password_when_it_changes(): void
    {
        config(['seeders.admin' => [
            'name' => 'Administrador',
            'email' => 'admin@ct12rounds.com',
            'password' => 'primeira-senha',
        ]]);
        $this->seed(UserSeeder::class);

        config(['seeders.admin.password' => 'segunda-senha']);
        $this->seed(UserSeeder::class);

        $user = User::query()->where('email', 'admin@ct12rounds.com')->firstOrFail();

        $this->assertTrue(Hash::check('segunda-senha', $user->password));
    }

    public function test_it_creates_nothing_without_a_password(): void
    {
        config(['seeders.admin' => [
            'name' => 'Administrador',
            'email' => 'admin@ct12rounds.com',
            'password' => '',
        ]]);

        $this->seed(UserSeeder::class);

        $this->assertSame(0, User::query()->count());
    }
}
