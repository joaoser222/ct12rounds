<?php

namespace Database\Seeders;

use App\Models\Modality;
use Illuminate\Database\Seeder;

class ModalitySeeder extends Seeder
{
    /**
     * Predefined modalities.
     *
     * @var array<string, string>
     */
    private const MODALITIES = [
        'Boxe',
        'Jiu-jitsu',
        'Kickboxing',
        'MMA',
    ];

    /**
     * Run the database seeds.
     *
     * @return array<string, Modality>
     */
    public function run(): array
    {
        $modalities = [];

        foreach (self::MODALITIES as $name) {
            $modalities[$name] = Modality::firstOrCreate(['name' => $name]);
        }

        return $modalities;
    }
}
