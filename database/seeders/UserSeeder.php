<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the administrator account from the ADMIN_* environment variables.
 *
 * The role and the permission rows are intentionally left to
 * `php artisan access-control:sync`, which owns that data and assigns the
 * default role to every user that has none.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array{name: string, email: string, password: string} $admin */
        $admin = config('seeders.admin');

        if ($admin['password'] === '') {
            $this->command?->warn('ADMIN_PASSWORD is empty: skipping the administrator user.');

            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => Hash::make($admin['password']),
                'visibility' => 'visible',
                'profile_image' => '',
            ]
        );

        $this->command?->info(sprintf('Administrator ready: %s (#%d)', $user->email, $user->id));
    }
}
