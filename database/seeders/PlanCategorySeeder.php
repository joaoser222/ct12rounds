<?php

namespace Database\Seeders;

use App\Models\PlanCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Categorias pré-definidas
     *
     * @var array<int, array{name: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Adulto'],
        ['name' => 'Infantil'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $categoryData) {
            PlanCategory::updateOrCreate(
                ['name' => $categoryData['name']],
            );
        }
    }
}
