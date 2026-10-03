<?php

namespace Database\Seeders;

use App\Enums\OperationType;
use App\Enums\Visibility;
use App\Models\CostCenter;
use App\Models\FinancialCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FinancialCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Cost center names, resolved to ids at runtime. CostCenterSeeder assigns
     * ids from the database, so they are not stable enough to hardcode here.
     *
     * @var array<string, string>
     */
    private const COST_CENTERS = [
        'receitas' => 'Receitas',
        'deducoes' => 'Deduções e Abatimentos',
        'custo_produtos' => 'Custo de Produtos',
        'despesas_administrativas' => 'Despesas Administrativas',
        'despesas_vendas' => 'Despesas com Vendas',
        'despesas_financeiras' => 'Despesas Financeiras',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $costCenters = CostCenter::query()
            ->whereIn('name', array_values(self::COST_CENTERS))
            ->pluck('id', 'name');

        $categories = [];

        foreach (self::CATEGORIES as $category) {
            $categories[] = [
                'name' => $category['name'],
                'color' => $category['color'],
                'operation_type' => $category['operation_type'],
                'cost_center_id' => $costCenters[self::COST_CENTERS[$category['cost_center']]],
                'visibility' => Visibility::VISIBLE->value,
            ];
        }

        FinancialCategory::upsert(
            $categories,
            ['name', 'cost_center_id'],
            ['color', 'operation_type'],
        );
    }

    /**
     * @var array<int, array{name: string, color: string, operation_type: string, cost_center: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Venda de Produtos', 'color' => '#1dd1a1', 'operation_type' => OperationType::RECEIVABLE->value, 'cost_center' => 'receitas'],
        ['name' => 'Prestação de Serviços', 'color' => '#1dd1a1', 'operation_type' => OperationType::RECEIVABLE->value, 'cost_center' => 'receitas'],
        ['name' => 'Outras Receitas', 'color' => '#1dd1a1', 'operation_type' => OperationType::RECEIVABLE->value, 'cost_center' => 'receitas'],
        ['name' => 'Cancelamentos', 'color' => '#feca57', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'deducoes'],
        ['name' => 'Impostos', 'color' => '#feca57', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'deducoes'],
        ['name' => 'Compra de Equipamentos', 'color' => '#5f27cd', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'custo_produtos'],
        ['name' => 'Frete', 'color' => '#5f27cd', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'custo_produtos'],
        ['name' => 'Aluguel', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Energia', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Material de Escritório', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Telefone', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Internet', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Material de Limpeza', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Material de Informática', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Serviços Contábeis', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Pagamento de Funcionários', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Vale Transporte', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Vale Alimentação', 'color' => '#B53471', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_administrativas'],
        ['name' => 'Comissões', 'color' => '#ee5253', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_vendas'],
        ['name' => 'Empréstimos', 'color' => '#006266', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_financeiras'],
        ['name' => 'Despesas Bancárias', 'color' => '#006266', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_financeiras'],
        ['name' => 'Juros E Multas', 'color' => '#006266', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_financeiras'],
        ['name' => 'Outras Despesas', 'color' => '#006266', 'operation_type' => OperationType::PAYABLE->value, 'cost_center' => 'despesas_financeiras'],
    ];
}