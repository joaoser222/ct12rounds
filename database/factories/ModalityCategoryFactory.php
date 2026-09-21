<?php

namespace Database\Factories;

use App\Enums\Audience;
use App\Models\ModalityCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModalityCategory>
 */
class ModalityCategoryFactory extends Factory
{
    protected $model = ModalityCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'audience' => fake()->randomElement(Audience::cases()),
            'visibility' => 'visible',
        ];
    }
}
