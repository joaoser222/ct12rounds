<?php

namespace Database\Factories;

use App\Models\LandingAdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LandingAdminUser>
 */
class LandingAdminUserFactory extends Factory
{
    protected $model = LandingAdminUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
        ];
    }
}