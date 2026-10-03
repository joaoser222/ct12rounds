<?php

namespace Database\Seeders;

use App\Enums\OperationType;
use App\Enums\Visibility;
use App\Models\CostCenter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Matched by name, never by id: the database assigns ids so the sequence
     * stays aligned with max(id) and inserts from the application cannot collide.
     *
     * @var array<int, array{name: string, color: string, operation_type: string}>
     */
    private const COST_CENTERS = [
        ['name' => 'Receitas', 'color' => '#1dd1a1', 'operation_type' => OperationType::RECEIVABLE->value],
        ['name' => 'Deduções e Abatimentos', 'color' => '#feca57', 'operation_type' => OperationType::PAYABLE->value],
        ['name' => 'Custo de Produtos', 'color' => '#5f27cd', 'operation_type' => OperationType::PAYABLE->value],
        ['name' => 'Despesas Administrativas', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value],
        ['name' => 'Despesas com Vendas', 'color' => '#ee5253', 'operation_type' => OperationType::PAYABLE->value],
        ['name' => 'Despesas Financeiras', 'color' => '#006266', 'operation_type' => OperationType::PAYABLE->value],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::COST_CENTERS as $costCenter) {
            CostCenter::updateOrCreate(
                ['name' => $costCenter['name']],
                [
                    'color' => $costCenter['color'],
                    'operation_type' => $costCenter['operation_type'],
                    'visibility' => Visibility::VISIBLE->value,
                ],
            );
        }
    }
}
