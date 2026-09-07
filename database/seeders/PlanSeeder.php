<?php

namespace Database\Seeders;

use App\Models\Modality;
use App\Models\Plan;
use App\Models\PlanCategory;
use App\Models\PlanModality;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Planos pré-definidos com suas configurações.
     *
     * @var array<string, array{
     *     name: string,
     *     description: string,
     *     category: string,
     *     price: float,
     *     duration_months: int,
     *     modalities: list<string>
     * }>
     */
    private const PLANS = [
        [
            'name' => 'MMA TUDÃO - 1 Mês',
            'description' => 'Acesso ilimitado a todas as modalidades por 1 mês',
            'category' => 'Adulto',
            'price' => 149.90,
            'duration_months' => 1,
            'modalities' => ['Boxe', 'Jiu-jitsu', 'Kickboxing', 'MMA'],
        ],
        [
            'name' => 'MMA TUDÃO - 3 Meses',
            'description' => 'Acesso ilimitado a todas as modalidades por 3 meses',
            'category' => 'Adulto',
            'price' => 139.90,
            'duration_months' => 3,
            'modalities' => ['Boxe', 'Jiu-jitsu', 'Kickboxing', 'MMA'],
        ],
        [
            'name' => 'MMA TUDÃO - 6 Meses',
            'description' => 'Acesso ilimitado a todas as modalidades por 6 meses',
            'category' => 'Adulto',
            'price' => 129.90,
            'duration_months' => 6,
            'modalities' => ['Boxe', 'Jiu-jitsu', 'Kickboxing', 'MMA'],
        ],
        [
            'name' => 'MMA TUDÃO - 12 Meses',
            'description' => 'Acesso ilimitado a todas as modalidades por 12 meses',
            'category' => 'Adulto',
            'price' => 119.90,
            'duration_months' => 12,
            'modalities' => ['Boxe', 'Jiu-jitsu', 'Kickboxing', 'MMA'],
        ],
        [
            'name' => 'Boxe Essential - 1 Mês',
            'description' => 'Acesso a aulas de boxe por 1 mês',
            'category' => 'Adulto',
            'price' => 89.90,
            'duration_months' => 1,
            'modalities' => ['Boxe'],
        ],
        [
            'name' => 'Boxe Essential - 3 Meses',
            'description' => 'Acesso a aulas de boxe por 3 meses',
            'category' => 'Adulto',
            'price' => 84.90,
            'duration_months' => 3,
            'modalities' => ['Boxe'],
        ],
        [
            'name' => 'Jiu-jitsu Pro - 1 Mês',
            'description' => 'Acesso a aulas de jiu-jitsu por 1 mês',
            'category' => 'Adulto',
            'price' => 99.90,
            'duration_months' => 1,
            'modalities' => ['Jiu-jitsu'],
        ],
        [
            'name' => 'Jiu-jitsu Pro - 3 Meses',
            'description' => 'Acesso a aulas de jiu-jitsu por 3 meses',
            'category' => 'Adulto',
            'price' => 94.90,
            'duration_months' => 3,
            'modalities' => ['Jiu-jitsu'],
        ],
        [
            'name' => 'Kickboxing Plus - 1 Mês',
            'description' => 'Acesso a aulas de kickboxing por 1 mês',
            'category' => 'Adulto',
            'price' => 94.90,
            'duration_months' => 1,
            'modalities' => ['Kickboxing'],
        ],
        [
            'name' => 'Kickboxing Plus - 3 Meses',
            'description' => 'Acesso a aulas de kickboxing por 3 meses',
            'category' => 'Adulto',
            'price' => 89.90,
            'duration_months' => 3,
            'modalities' => ['Kickboxing'],
        ],
        [
            'name' => 'MMA Basic - 1 Mês',
            'description' => 'Acesso a aulas de MMA por 1 mês',
            'category' => 'Adulto',
            'price' => 89.90,
            'duration_months' => 1,
            'modalities' => ['MMA'],
        ],
        [
            'name' => 'MMA Basic - 3 Meses',
            'description' => 'Acesso a aulas de MMA por 3 meses',
            'category' => 'Adulto',
            'price' => 84.90,
            'duration_months' => 3,
            'modalities' => ['MMA'],
        ],
        [
            'name' => 'Infantil Boxe - 1 Mês',
            'description' => 'Aulas de boxe para crianças por 1 mês',
            'category' => 'Infantil',
            'price' => 69.90,
            'duration_months' => 1,
            'modalities' => ['Boxe'],
        ],
        [
            'name' => 'Infantil Boxe - 3 Meses',
            'description' => 'Aulas de boxe para crianças por 3 meses',
            'category' => 'Infantil',
            'price' => 64.90,
            'duration_months' => 3,
            'modalities' => ['Boxe'],
        ],
        [
            'name' => 'Infantil Jiu-jitsu - 1 Mês',
            'description' => 'Aulas de jiu-jitsu para crianças por 1 mês',
            'category' => 'Infantil',
            'price' => 79.90,
            'duration_months' => 1,
            'modalities' => ['Jiu-jitsu'],
        ],
        [
            'name' => 'Infantil Jiu-jitsu - 3 Meses',
            'description' => 'Aulas de jiu-jitsu para crianças por 3 meses',
            'category' => 'Infantil',
            'price' => 74.90,
            'duration_months' => 3,
            'modalities' => ['Jiu-jitsu'],
        ],
        [
            'name' => 'Infantil Combo - 1 Mês',
            'description' => 'Acesso a boxe e jiu-jitsu para crianças por 1 mês',
            'category' => 'Infantil',
            'price' => 109.90,
            'duration_months' => 1,
            'modalities' => ['Boxe', 'Jiu-jitsu'],
        ],
        [
            'name' => 'Infantil Combo - 3 Meses',
            'description' => 'Acesso a boxe e jiu-jitsu para crianças por 3 meses',
            'category' => 'Infantil',
            'price' => 99.90,
            'duration_months' => 3,
            'modalities' => ['Boxe', 'Jiu-jitsu'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PLANS as $planData) {
            $this->createPlan($planData);
        }
    }

    /**
     * Cria um plano com sua categoria e modalidades.
     *
     * @param  array{name: string, description: string, category: string, price: float, duration_months: int, modalities: list<string>}  $planData
     */
    private function createPlan(array $planData): void
    {
        $category = PlanCategory::firstOrCreate(['name' => $planData['category']]);

        $plan = Plan::updateOrCreate(
            ['name' => $planData['name']],
            [
                'description' => $planData['description'],
                'price' => $planData['price'],
                'duration_months' => $planData['duration_months'],
                'plan_category_id' => $category->id,
            ],
        );

        $this->attachModalities($plan, $planData['modalities']);
    }

    /**
     * Associa modalidades ao plano.
     *
     * @param  list<string>  $modalityNames
     */
    private function attachModalities(Plan $plan, array $modalityNames): void
    {
        foreach ($modalityNames as $modalityName) {
            $modality = Modality::where('name', $modalityName)->first();

            if ($modality) {
                PlanModality::updateOrCreate(
                    ['plan_id' => $plan->id, 'modality_id' => $modality->id],
                );
            }
        }
    }
}
