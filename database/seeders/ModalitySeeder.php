<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModalitySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Modalidades pré-definidas com cor.
     *
     * @var array<int, array{name: string, color: string}>
     */
    private const MODALITIES = [
        ['name' => 'Boxe', 'color' => '#5d00ff'],
        ['name' => 'Jiu-jitsu', 'color' => '#7400e2'],
        ['name' => 'Kickboxing', 'color' => '#ff9c00'],
        ['name' => 'MMA', 'color' => '#db0000'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::MODALITIES as $modalityData) {
            Modality::updateOrCreate(
                ['name' => $modalityData['name']],
                ['color' => $modalityData['color']]
            );
        }
    }
}
