<?php

namespace Database\Seeders;

use App\Models\PlanCategory;
use Illuminate\Database\Seeder;

class PlanCategorySeeder extends Seeder
{
    /**
     * Categorias de planos pré-definidas.
     *
     * @var list<string>
     */
    private const CATEGORIES = [
        'Adulto',
        'Infantil',
    ];

    /**
     * Run the database seeds.
     *
     * @return array<string, PlanCategory>
     */
    public function run(): array
    {
        $categories = [];

        foreach (self::CATEGORIES as $name) {
            $categories[$name] = PlanCategory::firstOrCreate(['name' => $name]);
        }

        return $categories;
    }
}
