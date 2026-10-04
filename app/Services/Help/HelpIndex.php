<?php

declare(strict_types=1);

namespace App\Services\Help;

/**
 * Concept → screen map. The chat never fetches data; it hands the user a link
 * to a screen that already applies the filter, and that screen enforces its own
 * permission.
 *
 * `searchField` is required on every filtered link. Without it
 * `CrudModuleController::defaultSearchField()` falls back to the first
 * searchable field, which is `due_date` on receivables, so a status filter
 * would silently search the wrong column and return the whole list.
 *
 * Values are the raw enum values (`overdued`, not `overdue`), because
 * `CrudModuleController` compares the search term literally.
 */
final class HelpIndex
{
    /**
     * @return array<string, array{label: string, url: string}>
     */
    public static function links(): array
    {
        return [
            'clientes' => [
                'label' => 'Clientes',
                'url' => '/clients',
            ],
            'clientes_por_nome' => [
                'label' => 'Buscar cliente por nome',
                'url' => '/clients?searchField=name',
            ],
            'contratos' => [
                'label' => 'Contratos',
                'url' => '/contracts',
            ],
            'contratos_abertos' => [
                'label' => 'Contratos em aberto',
                'url' => '/contracts?searchField=status&search=open',
            ],
            'receivables' => [
                'label' => 'Contas a Receber',
                'url' => '/receivables',
            ],
            'receivables_overdued' => [
                'label' => 'Contas a Receber vencidas',
                'url' => '/receivables?searchField=status&search=overdued',
            ],
            'receivables_waiting' => [
                'label' => 'Contas a Receber aguardando pagamento',
                'url' => '/receivables?searchField=status&search=waiting',
            ],
            'payables' => [
                'label' => 'Contas a Pagar',
                'url' => '/payables',
            ],
            'movements' => [
                'label' => 'Movimentos financeiros',
                'url' => '/movements',
            ],
            'financial_categories' => [
                'label' => 'Categorias financeiras',
                'url' => '/financial-categories',
            ],
            'cost_centers' => [
                'label' => 'Centros de custo',
                'url' => '/cost-centers',
            ],
            'plans' => [
                'label' => 'Planos',
                'url' => '/plans',
            ],
            'plan_categories' => [
                'label' => 'Categorias de plano',
                'url' => '/plan-categories',
            ],
            'products' => [
                'label' => 'Produtos',
                'url' => '/products',
            ],
            'direct_lessons' => [
                'label' => 'Aulas Diretas',
                'url' => '/direct-lessons',
            ],
            'class_schedules' => [
                'label' => 'Turmas',
                'url' => '/class-schedules',
            ],
            'loyalty_levels' => [
                'label' => 'Fidelidade',
                'url' => '/loyalty-levels',
            ],
            'modalities' => [
                'label' => 'Modalidades',
                'url' => '/modalities',
            ],
            'gateway_accounts' => [
                'label' => 'Contas do Gateway',
                'url' => '/gateway-accounts',
            ],
            'gateway_invoices' => [
                'label' => 'Faturas do Gateway',
                'url' => '/gateway-invoices',
            ],
            'gateway_payments' => [
                'label' => 'Pagamentos do Gateway',
                'url' => '/gateway-payments',
            ],
            'gateway_transfers' => [
                'label' => 'Transferências',
                'url' => '/gateway-transfers',
            ],
            'gateway_transfer_recipients' => [
                'label' => 'Destinatários de transferência',
                'url' => '/gateway-transfer-recipients',
            ],
            'gateway_customers' => [
                'label' => 'Clientes do Gateway',
                'url' => '/gateway-customers',
            ],
            'gateway_credit_cards' => [
                'label' => 'Cartões de crédito',
                'url' => '/gateway-credit-cards',
            ],
            'gateway_postbacks' => [
                'label' => 'Postbacks',
                'url' => '/gateway-postbacks',
            ],
            'hiring_leads' => [
                'label' => 'Leads',
                'url' => '/hiring-leads',
            ],
            'users' => [
                'label' => 'Usuários',
                'url' => '/users',
            ],
            'settings' => [
                'label' => 'Configurações',
                'url' => '/settings',
            ],
            'dashboard' => [
                'label' => 'Dashboard',
                'url' => '/dashboard',
            ],
            'reports' => [
                'label' => 'Relatórios',
                'url' => '/reports',
            ],
            'financial_accounts' => [
                'label' => 'Contas financeiras',
                'url' => '/financial-accounts',
            ],
            'trainers' => [
                'label' => 'Treinadores',
                'url' => '/trainers',
            ],
            'sales' => [
                'label' => 'Vendas',
                'url' => '/sales',
            ],
            'purchases' => [
                'label' => 'Compras',
                'url' => '/purchases',
            ],
            'coupons' => [
                'label' => 'Cupons',
                'url' => '/coupons',
            ],
            'suppliers' => [
                'label' => 'Fornecedores',
                'url' => '/suppliers',
            ],
        ];
    }

    /**
     * Render the map as instructions for the LLM. Links are a menu, not a tool:
     * the model picks one and writes it into the answer as markdown.
     */
    public static function linkInstructions(): string
    {
        $lines = [];

        foreach (self::links() as $link) {
            $lines[] = sprintf('- %s → %s', $link['label'], $link['url']);
        }

        return implode("\n", $lines);
    }
}