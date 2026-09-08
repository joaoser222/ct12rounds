<?php

namespace App\AccessControl;

use App\Traits\HasMetadata;

enum AccessModule: string
{
    use HasMetadata;

    // Viewing
    case DASHBOARD = 'dashboard';
    case REPORT = 'reports';

    // People
    case CLIENT = 'clients';
    case TRAINER = 'trainers';
    case SUPPLIER = 'suppliers';
    case HIRING_LEAD = 'hiring_leads';

    // Catalog
    case PRODUCT = 'products';
    case MODALITY = 'modalities';
    case PLAN = 'plans';
    case PLAN_CATEGORY = 'plan_categories';

    // Billing
    case CONTRACT = 'contracts';
    case COUPON = 'coupons';
    case SALE = 'sales';
    case PURCHASE = 'purchases';
    case DIRECT_LESSON = 'direct_lessons';

    // Financial
    case COST_CENTER = 'cost_centers';
    case FINANCIAL_CATEGORY = 'financial_categories';
    case PAYABLE = 'payables';
    case RECEIVABLE = 'receivables';
    case MOVEMENT = 'movements';

    // Payment Gateway
    case GATEWAY_PAYMENT = 'gateway_payments';
    case GATEWAY_TRANSFER = 'gateway_transfers';
    case GATEWAY_TRANSFER_RECIPIENT = 'gateway_transfer_recipients';
    case GATEWAY_POSTBACK = 'gateway_postbacks';
    case GATEWAY_CUSTOMER = 'gateway_customers';
    case GATEWAY_CREDIT_CARD = 'gateway_credit_cards';
    case GATEWAY_INVOICE = 'gateway_invoices';

    // Advanced
    case FINANCIAL_ACCOUNT = 'financial_accounts';
    case GATEWAY_ACCOUNT = 'gateway_accounts';
    case USER = 'users';
    case SETTING = 'settings';
    case CHAT = 'chat';

    public function label(): string
    {
        return match ($this) {
            self::DASHBOARD => 'Dashboard',
            self::REPORT => 'Relatórios',
            self::CLIENT => 'Pessoas',
            self::TRAINER => 'Treinadores',
            self::SUPPLIER => 'Fornecedores',
            self::HIRING_LEAD => 'Pré-cadastro de Clientes',
            self::PRODUCT => 'Produtos',
            self::MODALITY => 'Modalidades',
            self::PLAN => 'Planos',
            self::PLAN_CATEGORY => 'Categorias de Planos',
            self::CONTRACT => 'Contratos',
            self::COUPON => 'Cupons',
            self::SALE => 'Vendas',
            self::PURCHASE => 'Compras',
            self::DIRECT_LESSON => 'Aulas Diretas',
            self::COST_CENTER => 'Centros de Custo',
            self::FINANCIAL_CATEGORY => 'Categorias Financeiras',
            self::PAYABLE => 'Contas a Pagar',
            self::RECEIVABLE => 'Contas a Receber',
            self::MOVEMENT => 'Fluxo de Caixa',
            self::GATEWAY_PAYMENT => 'Gateway de Pagamentos',
            self::GATEWAY_TRANSFER => 'Transferências Gateway',
            self::GATEWAY_TRANSFER_RECIPIENT => 'Destinatários de Transferência',
            self::GATEWAY_POSTBACK => 'Postbacks Gateway',
            self::GATEWAY_CUSTOMER => 'Clientes Gateway',
            self::GATEWAY_CREDIT_CARD => 'Cartões Gateway',
            self::GATEWAY_INVOICE => 'Notas Fiscais',
            self::FINANCIAL_ACCOUNT => 'Contas Financeiras',
            self::GATEWAY_ACCOUNT => 'Contas Gateway',
            self::USER => 'Usuários',
            self::SETTING => 'Configurações',
            self::CHAT => 'Chat'
        };
    }

    public function actions(): array
    {
        $default_actions = [
            AccessAction::VIEW,
            AccessAction::CREATE,
            AccessAction::UPDATE,
            AccessAction::DELETE,
            AccessAction::VISIBILITY,
        ];

        return match ($this) {
            self::DASHBOARD => [AccessAction::VIEW],
            self::REPORT => [AccessAction::VIEW],
            self::CLIENT => $default_actions,
            self::TRAINER => $default_actions,
            self::SUPPLIER => $default_actions,
            self::HIRING_LEAD => [
                AccessAction::VIEW,
                AccessAction::UPDATE,
                AccessAction::DELETE,
                AccessAction::VISIBILITY,
            ],
            self::PRODUCT => $default_actions,
            self::MODALITY => $default_actions,
            self::PLAN => $default_actions,
            self::PLAN_CATEGORY => $default_actions,
            self::CONTRACT => [
                ...$default_actions,
                AccessAction::CANCEL,
            ],
            self::COUPON => $default_actions,
            self::SALE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::PURCHASE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::DIRECT_LESSON => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::COST_CENTER => $default_actions,
            self::FINANCIAL_CATEGORY => $default_actions,
            self::PAYABLE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
            ],
            self::RECEIVABLE => [
                ...$default_actions,
                AccessAction::MARK_PAID,
                AccessAction::MARK_UNPAID,
                AccessAction::REQUEST_INVOICE,
            ],
            self::MOVEMENT => [AccessAction::VIEW],
            self::GATEWAY_PAYMENT => [AccessAction::VIEW],
            self::GATEWAY_TRANSFER => [AccessAction::VIEW, AccessAction::CREATE],
            self::GATEWAY_TRANSFER_RECIPIENT => $default_actions,
            self::GATEWAY_POSTBACK => [AccessAction::VIEW],
            self::GATEWAY_CUSTOMER => [AccessAction::VIEW],
            self::GATEWAY_CREDIT_CARD => [AccessAction::VIEW],
            self::GATEWAY_INVOICE => [AccessAction::VIEW],
            self::FINANCIAL_ACCOUNT => $default_actions,
            self::GATEWAY_ACCOUNT => $default_actions,
            self::USER => $default_actions,
            self::SETTING => [
                AccessAction::VIEW,
                AccessAction::UPDATE,
            ],
            self::CHAT => [AccessAction::VIEW]
        };
    }
}
