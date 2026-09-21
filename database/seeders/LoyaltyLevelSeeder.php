<?php

namespace Database\Seeders;

use App\Enums\Visibility;
use App\Models\LoyaltyLevel;
use Illuminate\Database\Seeder;

class LoyaltyLevelSeeder extends Seeder
{
    public function run(): void
    {
        LoyaltyLevel::upsert([
            ['name' => 'Novato', 'min_months' => 0, 'color' => '#95a5a6', 'description' => 'Nível inicial', 'visibility' => Visibility::VISIBLE->value],
            ['name' => 'Guarda', 'min_months' => 6, 'color' => '#3498db', 'description' => '6 meses de fidelidade', 'visibility' => Visibility::VISIBLE->value],
            ['name' => 'Contendor', 'min_months' => 12, 'color' => '#2ecc71', 'description' => '1 ano de fidelidade', 'visibility' => Visibility::VISIBLE->value],
            ['name' => 'Peso Médio', 'min_months' => 18, 'color' => '#f39c12', 'description' => '18 meses de fidelidade', 'visibility' => Visibility::VISIBLE->value],
            ['name' => 'Peso Pesado', 'min_months' => 24, 'color' => '#e74c3c', 'description' => '2 anos de fidelidade', 'visibility' => Visibility::VISIBLE->value],
            ['name' => 'Campeão', 'min_months' => 30, 'color' => '#9b59b6', 'description' => '2 anos e meio de fidelidade', 'visibility' => Visibility::VISIBLE->value],
            ['name' => 'Lenda do Ringue', 'min_months' => 36, 'color' => '#f1c40f', 'description' => '3 anos ou mais de fidelidade', 'visibility' => Visibility::VISIBLE->value],
        ], ['name'], ['min_months', 'color', 'description', 'visibility']);
    }
}
