<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Modality;
use Illuminate\Database\Seeder;

class ClassScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $boxe = Modality::where('name', 'Boxe')->first();
        $kickboxing = Modality::where('name', 'Kickboxing')->first();
        $jiuJitsu = Modality::where('name', 'Jiu-jitsu')->first();

        if (! $boxe || ! $kickboxing || ! $jiuJitsu) {
            return;
        }

        $schedules = [
            // Segunda (1)
            ['modality_id' => $boxe->id, 'week_day' => 1, 'start_time' => '07:00', 'end_time' => '08:00'],
            ['modality_id' => $jiuJitsu->id, 'week_day' => 1, 'start_time' => '09:00', 'end_time' => '10:00'],
            ['modality_id' => $boxe->id, 'week_day' => 1, 'start_time' => '18:00', 'end_time' => '19:00'],
            ['modality_id' => $kickboxing->id, 'week_day' => 1, 'start_time' => '19:00', 'end_time' => '20:00'],

            // Terça (2)
            ['modality_id' => $kickboxing->id, 'week_day' => 2, 'start_time' => '07:00', 'end_time' => '08:00'],
            ['modality_id' => $jiuJitsu->id, 'week_day' => 2, 'start_time' => '18:00', 'end_time' => '19:00'],
            ['modality_id' => $boxe->id, 'week_day' => 2, 'start_time' => '19:00', 'end_time' => '20:00'],

            // Quarta (3)
            ['modality_id' => $boxe->id, 'week_day' => 3, 'start_time' => '07:00', 'end_time' => '08:00'],
            ['modality_id' => $jiuJitsu->id, 'week_day' => 3, 'start_time' => '09:00', 'end_time' => '10:00'],
            ['modality_id' => $boxe->id, 'week_day' => 3, 'start_time' => '18:00', 'end_time' => '19:00'],
            ['modality_id' => $kickboxing->id, 'week_day' => 3, 'start_time' => '19:00', 'end_time' => '20:00'],

            // Quinta (4)
            ['modality_id' => $kickboxing->id, 'week_day' => 4, 'start_time' => '07:00', 'end_time' => '08:00'],
            ['modality_id' => $jiuJitsu->id, 'week_day' => 4, 'start_time' => '18:00', 'end_time' => '19:00'],
            ['modality_id' => $boxe->id, 'week_day' => 4, 'start_time' => '19:00', 'end_time' => '20:00'],

            // Sexta (5)
            ['modality_id' => $boxe->id, 'week_day' => 5, 'start_time' => '07:00', 'end_time' => '08:00'],
            ['modality_id' => $jiuJitsu->id, 'week_day' => 5, 'start_time' => '09:00', 'end_time' => '10:00'],
            ['modality_id' => $kickboxing->id, 'week_day' => 5, 'start_time' => '18:00', 'end_time' => '19:00'],
            ['modality_id' => $jiuJitsu->id, 'week_day' => 5, 'start_time' => '19:00', 'end_time' => '20:00'],

            // Sábado (6)
            ['modality_id' => $jiuJitsu->id, 'week_day' => 6, 'start_time' => '09:00', 'end_time' => '10:00'],
        ];

        foreach ($schedules as $schedule) {
            ClassSchedule::updateOrCreate(
                ['modality_id' => $schedule['modality_id'], 'week_day' => $schedule['week_day'], 'start_time' => $schedule['start_time']],
                ['end_time' => $schedule['end_time']],
            );
        }
    }
}
