<?php

namespace App\Console\Commands;

use App\Models\LandingAdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateLandingAdminUser extends Command
{
    /**
     * The command name and signature.
     *
     * @var string
     */
    protected $signature = 'landing-admin:create-user
                            {email : Landing panel admin e-mail}
                            {--name= : Admin name}
                            {--password= : Admin password}
                            {--force : Force password update even if user already exists}';

    /**
     * @var string
     */
    protected $description = 'Create or update a user with access to the landing page panel';

    /**
     * Execute the command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Landing admin name');
        $email = $this->argument('email');
        $password = $this->option('password') ?? $this->secret('Landing admin password');

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

        $existingUser = LandingAdminUser::query()->where('email', $email)->first();

        if ($existingUser !== null && ! $this->option('force')) {
            $this->warn("A user with the email {$email} already exists.");

            if (! $this->confirm('Do you want to update the password for this user?')) {
                $this->error('Operation cancelled.');

                return Command::FAILURE;
            }
        }

        $landingAdmin = LandingAdminUser::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password],
        );

        $this->info('Landing panel user created/updated successfully!');
        $this->table(
            ['ID', 'Nome', 'E-mail'],
            [[$landingAdmin->id, $landingAdmin->name, $landingAdmin->email]],
        );

        return Command::SUCCESS;
    }
}