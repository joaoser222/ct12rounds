<?php

namespace Database\Factories;

use App\Models\ClassSchedule;
use App\Models\Modality;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSchedule>
 */
class ClassScheduleFactory extends Factory
{
    protected $model = ClassSchedule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(7, 19);

        return [
            'modality_id' => Modality::factory(),
            'week_day' => fake()->numberBetween(1, 6),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $startHour + 1),
            'visibility' => 'visible',
        ];
    }
}
