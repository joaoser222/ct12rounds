<?php

namespace App\Console\Commands;

use App\AccessControl\AccessRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The command name and signature.
     *
     * @var string
     */
    protected $signature = 'user:create-admin
                            {--email= : Administrator email}
                            {--name= : Administrator name}
                            {--password= : Administrator password}
                            {--force : Force creation even if user already exists}';

    /**
     * @var string
     */
    protected $description = 'Create an administrator user in the system';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $this->info('Creating administrator user...');
        $this->newLine();

        $this->callSilently('access-control:sync', [
            '--without-users' => true,
        ]);

        // Collect data
        $name = $this->option('name') ?? $this->ask('Administrator name');
        $email = $this->option('email') ?? $this->ask('Administrator email');
        $password = $this->option('password') ?? $this->secret('Administrator password');
        $role = Role::query()->where('name', AccessRole::ADMINISTRATOR->value)->first();

        if ($role === null) {
            $this->error('Administrator profile not found after synchronization.');

            return Command::FAILURE;
        }

        // Validation
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return Command::FAILURE;
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            if (! $this->option('force')) {
                $this->warn("User with email {$email} already exists!");

                if (! $this->confirm('Do you want to update this user to administrator?')) {
                    $this->error('Operation cancelled.');

                    return Command::FAILURE;
                }
            }

            // Update existing user
            $existingUser->update([
                'name' => $name,
                'password' => Hash::make($password),
                'role_id' => $role->id,
            ]);
            $existingUser->permissions()->sync($role->permissions()->pluck('permissions.id')->all());

            $this->info('User updated to administrator successfully!');
            $this->table(
                ['ID', 'Name', 'E-mail', 'Admin'],
                [[$existingUser->id, $existingUser->name, $existingUser->email, 'Yes']]
            );

            return Command::SUCCESS;
        }

        // Create new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
        ]);
        $user->permissions()->sync($role->permissions()->pluck('permissions.id')->all());

        $this->info('Administrator user created successfully!');
        $this->newLine();

        $this->table(
            ['ID', 'Name', 'E-mail', 'Admin'],
            [[$user->id, $user->name, $user->email, 'Yes']]
        );

        return Command::SUCCESS;
    }
}
