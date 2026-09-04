<?php

namespace Database\Seeders;

use App\Models\Modality;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanModality;
use App\Models\PlanTier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = PlanCategory::firstOrCreate(['name' => 'Adulto']);

        $plan = Plan::updateOrCreate(
            ['name' => 'MMA TUDÃO'],
            [
                'description' => 'Acesso a uma modalidade com até 4 aulas por mês',
                'modality_quantity' => 1,
                'plan_category_id' => $category->id,
            ],
        );

        PlanTier::updateOrCreate(
            ['plan_id' => $plan->id, 'quantity' => 1],
            ['price' => 89.90],
        );

        $modality = Modality::firstOrCreate(
            ['name' => 'Boxe'],
            ['color' => '#5d00ff'],
        );

        PlanModality::updateOrCreate(
            ['plan_id' => $plan->id, 'modality_id' => $modality->id],
        );
    }
}
