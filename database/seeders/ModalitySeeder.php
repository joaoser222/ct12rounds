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
        ['name' => 'Boxe', 'color' => '#DC2626'],
        ['name' => 'Jiu-jitsu', 'color' => '#0D9488'],
        ['name' => 'Kickboxing', 'color' => '#EA580C'],
        ['name' => 'MMA', 'color' => '#2563EB'],
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