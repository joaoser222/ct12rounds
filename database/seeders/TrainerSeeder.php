<?php

namespace Database\Seeders;

use App\Enums\GenderType;
use App\Enums\Visibility;
use App\Models\Trainer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TrainerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Matched by name, never by id: the database assigns ids so the sequence
     * stays aligned with max(id) and inserts from the application cannot collide.
     */
    public function run(): void
    {
        Trainer::updateOrCreate(
            ['name' => 'Treinador Padrão'],
            [
                'document' => '00000000000',
                'phone' => '99999999999',
                'gender' => GenderType::MALE->value,
                'visibility' => Visibility::VISIBLE->value,
            ],
        );
    }
}
