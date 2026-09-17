<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Seeder;

class ModalitySeeder extends Seeder
{
    /**
     * Predefined modalities.
     *
     * @var array<int, array{name: string, color: string}>
     */
    private const MODALITIES = [
        ['name' => 'Boxe', 'color' => '#DC2626'],
        ['name' => 'Jiu-jitsu', 'color' => '#0D9488'],
        ['name' => 'Kickboxing', 'color' => '#EA580C'],
        ['name' => 'MMA', 'color' => '#2563EB'],
    ];

    /**
     * Run the database seeds.
     *
     * @return array<string, Modality>
     */
    public function run(): array
    {
        $modalities = [];

        foreach (self::MODALITIES as $modalityData) {
            $modalities[$modalityData['name']] = Modality::updateOrCreate(
                ['name' => $modalityData['name']],
                ['color' => $modalityData['color']],
            );
        }

        return $modalities;
    }
}
