<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\Modality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modality>
 */
class ModalityFactory extends Factory
{
    protected $model = Modality::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'color' => fake()->hexColor(),
            'visibility' => Visibility::VISIBLE,
        ];
    }
}
