<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Prompts\CollectReceivablePrompt;
use App\Mcp\Prompts\CreateCostCenterPrompt;
use App\Mcp\Prompts\CreateFinancialCategoryPrompt;
use App\Mcp\Prompts\CreateProductPrompt;
use App\Mcp\Prompts\FinancialOverviewPrompt;
use App\Mcp\Prompts\OnboardClientPrompt;
use App\Mcp\Prompts\RegisterTrainerPrompt;
use App\Mcp\Resources\ClientResource;
use App\Mcp\Resources\ClientsListResource;
use App\Mcp\Resources\ContractResource;
use App\Mcp\Resources\ContractsListResource;
use App\Mcp\Resources\DirectLessonResource;
use App\Mcp\Resources\GatewayAccountResource;
use App\Mcp\Resources\HiringLeadsListResource;
use App\Mcp\Resources\InvoiceResource;
use App\Mcp\Resources\InvoicesListResource;
use App\Mcp\Resources\ModalityResource;
use App\Mcp\Resources\MovementResource;
use App\Mcp\Resources\MovementsByDateResource;
use App\Mcp\Resources\OverdueReceivablesResource;
use App\Mcp\Resources\PayableResource;
use App\Mcp\Resources\PayablesListResource;
use App\Mcp\Resources\PlanResource;
use App\Mcp\Resources\ProductResource;
use App\Mcp\Resources\PurchaseResource;
use App\Mcp\Resources\PurchasesListResource;
use App\Mcp\Resources\ReceivableResource;
use App\Mcp\Resources\ReceivablesListResource;
use App\Mcp\Resources\SaleResource;
use App\Mcp\Resources\SalesListResource;
use App\Mcp\Tools\CancelContractTool;
use App\Mcp\Tools\ConfigureFiscalDataTool;
use App\Mcp\Tools\ConvertHiringLeadTool;
use App\Mcp\Tools\CreateClientTool;
use App\Mcp\Tools\CreateContractTool;
use App\Mcp\Tools\CreateCostCenterTool;
use App\Mcp\Tools\CreateCouponTool;
use App\Mcp\Tools\CreateDirectLessonTool;
use App\Mcp\Tools\CreateFinancialAccountTool;
use App\Mcp\Tools\CreateFinancialCategoryTool;
use App\Mcp\Tools\CreateGatewayAccountTool;
use App\Mcp\Tools\CreateGatewayTransferTool;
use App\Mcp\Tools\CreateModalityTool;
use App\Mcp\Tools\CreatePayableTool;
use App\Mcp\Tools\CreatePlanCategoryTool;
use App\Mcp\Tools\CreatePlanTool;
use App\Mcp\Tools\CreateProductTool;
use App\Mcp\Tools\CreatePurchaseTool;
use App\Mcp\Tools\CreateSaleTool;
use App\Mcp\Tools\CreateSupplierTool;
use App\Mcp\Tools\CreateTrainerTool;
use App\Mcp\Tools\FindClientByDocumentTool;
use App\Mcp\Tools\MarkReceivablePaidTool;
use App\Mcp\Tools\RequestGatewayInvoiceTool;
use App\Mcp\Tools\SaveUserTool;
use App\Mcp\Tools\UpdateClientTool;
use App\Mcp\Tools\UpdateContractTool;
use App\Mcp\Tools\UpdateCostCenterTool;
use App\Mcp\Tools\UpdateCouponTool;
use App\Mcp\Tools\UpdateDirectLessonTool;
use App\Mcp\Tools\UpdateFinancialAccountTool;
use App\Mcp\Tools\UpdateFinancialCategoryTool;
use App\Mcp\Tools\UpdateGatewayAccountTool;
use App\Mcp\Tools\UpdateModalityTool;
use App\Mcp\Tools\UpdatePayableTool;
use App\Mcp\Tools\UpdatePlanCategoryTool;
use App\Mcp\Tools\UpdatePlanTool;
use App\Mcp\Tools\UpdateProductTool;
use App\Mcp\Tools\UpdatePurchaseTool;
use App\Mcp\Tools\UpdateRolePermissionsTool;
use App\Mcp\Tools\UpdateSaleTool;
use App\Mcp\Tools\UpdateSettingsTool;
use App\Mcp\Tools\UpdateSupplierTool;
use App\Mcp\Tools\UpdateTrainerTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Tool;

#[Name('Gymnamite')]
#[Version('1.0.0')]
#[Instructions('Gestão de academia: clientes, contratos, planos, modalidades, produtos, vendas, compras, aulas diretas, cupons, fornecedores, treinadores, categorias financeiras, centros de custo, contas financeiras, contas a pagar, contas a receber, movimentações e gateway de pagamento.')]
class GymnamiteServer extends Server
{
    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        // Clients
        CreateClientTool::class,
        UpdateClientTool::class,
        // Contracts
        CreateContractTool::class,
        UpdateContractTool::class,
        CancelContractTool::class,
        FindClientByDocumentTool::class,
        // Sales
        CreateSaleTool::class,
        UpdateSaleTool::class,
        // Purchases
        CreatePurchaseTool::class,
        UpdatePurchaseTool::class,
        // Direct Lessons
        CreateDirectLessonTool::class,
        UpdateDirectLessonTool::class,
        // Plans
        CreatePlanTool::class,
        UpdatePlanTool::class,
        // Modalities
        CreateModalityTool::class,
        UpdateModalityTool::class,
        // Products
        CreateProductTool::class,
        UpdateProductTool::class,
        // Gateway
        CreateGatewayAccountTool::class,
        UpdateGatewayAccountTool::class,
        ConfigureFiscalDataTool::class,
        CreateGatewayTransferTool::class,
        // Receivables
        MarkReceivablePaidTool::class,
        RequestGatewayInvoiceTool::class,
        // Reference Data
        CreateCouponTool::class,
        UpdateCouponTool::class,
        CreateTrainerTool::class,
        UpdateTrainerTool::class,
        CreateSupplierTool::class,
        UpdateSupplierTool::class,
        CreateFinancialCategoryTool::class,
        UpdateFinancialCategoryTool::class,
        CreateCostCenterTool::class,
        UpdateCostCenterTool::class,
        CreatePlanCategoryTool::class,
        UpdatePlanCategoryTool::class,
        CreateFinancialAccountTool::class,
        UpdateFinancialAccountTool::class,
        // Payables
        CreatePayableTool::class,
        UpdatePayableTool::class,
        // Admin
        SaveUserTool::class,
        UpdateRolePermissionsTool::class,
        UpdateSettingsTool::class,
        // Hiring Leads
        ConvertHiringLeadTool::class,
    ];

    /**
     * @var array<int, class-string<Server\Resource>>
     */
    protected array $resources = [
        // Detail resources
        ClientResource::class,
        ContractResource::class,
        InvoiceResource::class,
        SaleResource::class,
        PurchaseResource::class,
        DirectLessonResource::class,
        PlanResource::class,
        ModalityResource::class,
        ProductResource::class,
        ReceivableResource::class,
        PayableResource::class,
        MovementsByDateResource::class,
        MovementResource::class,
        GatewayAccountResource::class,
        // List resources
        ClientsListResource::class,
        ContractsListResource::class,
        InvoicesListResource::class,
        SalesListResource::class,
        PurchasesListResource::class,
        ReceivablesListResource::class,
        PayablesListResource::class,
        OverdueReceivablesResource::class,
        HiringLeadsListResource::class,
    ];

    /**
     * @var array<int, class-string<Prompt>>
     */
    protected array $prompts = [
        OnboardClientPrompt::class,
        CollectReceivablePrompt::class,
        FinancialOverviewPrompt::class,
        RegisterTrainerPrompt::class,
        CreateFinancialCategoryPrompt::class,
        CreateCostCenterPrompt::class,
        CreateProductPrompt::class,
    ];
}
