<?php

namespace Database\Factories;

use App\Models\LoyaltyLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyLevel>
 */
class LoyaltyLevelFactory extends Factory
{
    protected $model = LoyaltyLevel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'min_months' => fake()->unique()->numberBetween(0, 60),
            'color' => fake()->hexColor(),
            'description' => fake()->sentence(),
            'visibility' => 'visible',
        ];
    }
}
