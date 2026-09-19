<?php

namespace Database\Seeders;

use App\Models\Report;
use App\Reports\ReportRegistry;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (ReportRegistry::all() as $definition) {
            Report::query()->updateOrCreate(
                ['name' => $definition->key],
                [
                    'label' => $definition->label,
                    'description' => $definition->description,
                ],
            );
        }
    }
}
