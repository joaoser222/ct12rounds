<?php

namespace Database\Seeders;

use App\Enums\AudienceCategory;
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
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = $this->getCategories();
        $modalities = $this->getModalities();
        $plans = $this->getPlans($categories, $modalities);

        foreach ($plans as $planData) {
            $this->createPlan($planData);
        }
    }

    /**
     * Retrieves the categories created by PlanCategorySeeder.
     *
     * @return array<string, PlanCategory>
     */
    private function getCategories(): array
    {
        return [
            'Geral' => PlanCategory::where('name', 'Geral')->firstOrFail(),
        ];
    }

    /**
     * Retrieves the modalities created by ModalitySeeder.
     *
     * @return array<string, Modality>
     */
    private function getModalities(): array
    {
        return [
            'Boxe' => Modality::where('name', 'Boxe')->firstOrFail(),
            'Jiu-jitsu' => Modality::where('name', 'Jiu-jitsu')->firstOrFail(),
            'Kickboxing' => Modality::where('name', 'Kickboxing')->firstOrFail(),
            'MMA' => Modality::where('name', 'MMA')->firstOrFail(),
        ];
    }

    /**
     * Returns the list of plans with category and modality IDs.
     *
     * @param  array<string, PlanCategory>  $categories
     * @param  array<string, Modality>  $modalities
     * @return list<array{name: string, description: string, category_id: int, price: float, duration_months: int, modalities: list<int>}>
     */
    private function getPlans(array $categories, array $modalities): array
    {
        $geral = $categories['Geral']->id;

        $boxe = $modalities['Boxe']->id;
        $jj = $modalities['Jiu-jitsu']->id;
        $kb = $modalities['Kickboxing']->id;
        $mma = $modalities['MMA']->id;

        return [
            [
                'name' => 'MMA - Tudão',
                'description' => 'Acesso a todas as modalidades por 1 mês',
                'category_id' => $geral,
                'price' => 239.99,
                'duration_months' => 1,
                'audience' => AudienceCategory::ADULT,
                'modalities' => [$boxe, $jj, $kb, $mma],
            ],
            [
                'name' => 'MMA - Desafiante',
                'description' => 'Acesso a aulas de MMA por 4 meses',
                'category_id' => $geral,
                'price' => 199.99,
                'duration_months' => 4,
                'audience' => AudienceCategory::ADULT,
                'modalities' => [$mma],
            ],
            [
                'name' => 'MMA - Dominador',
                'description' => 'Acesso a aulas de MMA por 6 meses',
                'category_id' => $geral,
                'price' => 189.99,
                'duration_months' => 6,
                'audience' => AudienceCategory::ADULT,
                'modalities' => [$mma],
            ],
            [
                'name' => 'MMA - Campeão',
                'description' => 'Acesso a aulas de MMA por 12 meses',
                'category_id' => $geral,
                'price' => 179.99,
                'duration_months' => 12,
                'audience' => AudienceCategory::ADULT,
                'modalities' => [$mma],
            ],
            [
                'name' => 'KIDS - Mensal',
                'description' => 'Acesso a aulas de jiu-jitsu por 1 meses',
                'category_id' => $geral,
                'price' => 164.99,
                'duration_months' => 1,
                'audience' => AudienceCategory::CHILD,
                'modalities' => [$jj],
            ],
            [
                'name' => 'KIDS - Mirim',
                'description' => 'Acesso a aulas de jiu-jitsu por 4 meses',
                'category_id' => $geral,
                'price' => 164.99,
                'duration_months' => 4,
                'audience' => AudienceCategory::CHILD,
                'modalities' => [$jj],
            ],
            [
                'name' => 'KIDS - Dominador Mirim',
                'description' => 'Acesso a aulas de jiu-jitsu por 6 meses',
                'category_id' => $geral,
                'price' => 164.99,
                'duration_months' => 6,
                'audience' => AudienceCategory::CHILD,
                'modalities' => [$jj],
            ],
        ];
    }

    /**
     * Creates a plan with its category and modalities.
     *
     * @param  array{name: string, description: string, category_id: int, price: float, duration_months: int, modalities: list<int>}  $planData
     */
    private function createPlan(array $planData): void
    {
        $plan = Plan::updateOrCreate(
            ['name' => $planData['name']],
            [
                'description' => $planData['description'],
                'price' => $planData['price'],
                'duration_months' => $planData['duration_months'],
                'plan_category_id' => $planData['category_id'],
                'audience' => $planData['audience'] ?? AudienceCategory::ADULT,
            ],
        );

        $this->attachModalities($plan, $planData['modalities']);
    }

    /**
     * Attaches modalities to the plan.
     *
     * @param  list<int>  $modalityIds
     */
    private function attachModalities(Plan $plan, array $modalityIds): void
    {
        foreach ($modalityIds as $modalityId) {
            PlanModality::updateOrCreate(
                ['plan_id' => $plan->id, 'modality_id' => $modalityId],
            );
        }
    }
}
