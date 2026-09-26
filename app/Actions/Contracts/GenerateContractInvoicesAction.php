<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\GenerateContractInvoicesDTO;
use App\DTOs\Invoices\InvoiceResultDTO;
use App\Enums\Gateway\GatewaySyncMode;
use App\Models\Contract;
use App\Models\Invoice;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\Billing\InvoiceGenerator;
use App\Services\Gateway\GatewayBillingOrchestrator;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;

class GenerateContractInvoicesAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceGenerator $invoiceGenerator,
        private readonly Container $container,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        [$contractId, $syncMode] = $this->resolveInput($input);

        $contract = $this->contractRepository->findOrFail($contractId);

        // Delete existing unpaid invoices
        $this->invoiceRepository->newQuery()
            ->where('billable_id', $contract->id)
            ->where('billable_type', $contract->getMorphClass())
            ->where('status', '!=', 'paid')
            ->delete();

        $invoices = $this->invoiceGenerator->generate($contract);
        $discountTotal = round($invoices->sum('discount_value'), 4);

        $this->contractRepository->update($contract, [
            'discount_value' => $discountTotal,
            'total' => round($contract->gross_value - $discountTotal, 4),
            'accepted_terms' => 'accepted',
        ]);

        $gatewayFailure = $this->syncGatewayInvoices($invoices, $syncMode);

        $invoiceDtos = $invoices->map(fn (Invoice $invoice) => InvoiceResultDTO::fromModel($invoice))->all();

        if ($gatewayFailure !== null) {
            return ActionResultDTO::failure(
                'Não foi possível concluir a cobrança no gateway. O contrato voltou para pendente.',
                ['card_number' => 'Não foi possível concluir a cobrança com os dados do cartão informados. O contrato continua pendente e nossa equipe entrará em contato.'],
                ['gateway_refused' => true, 'error' => $gatewayFailure],
            );
        }

        return ActionResultDTO::success(
            $invoiceDtos,
            'Faturas geradas com sucesso.'
        );
    }

    /**
     * @return array{0: int, 1: GatewaySyncMode}
     */
    private function resolveInput(mixed $input): array
    {
        if (is_int($input)) {
            return [$input, GatewaySyncMode::QUEUE];
        }

        if ($input instanceof GenerateContractInvoicesDTO) {
            return [$input->contractId, $input->syncMode];
        }

        throw new \InvalidArgumentException('GenerateContractInvoicesAction requires a contract ID or a GenerateContractInvoicesDTO.');
    }

    /**
     * A gateway refusal reverts the contract acceptance inside the orchestrator, which
     * already notifies the company, so the failure is reported instead of rethrown to
     * avoid a second notification.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @return string|null The gateway error message when the charge was refused.
     */
    private function syncGatewayInvoices(Collection $invoices, GatewaySyncMode $syncMode): ?string
    {
        $eligible = $invoices->filter(
            fn (Invoice $invoice): bool => $invoice->shouldGenerateGatewayTransaction(),
        );

        if ($eligible->isEmpty()) {
            return null;
        }

        if ($syncMode === GatewaySyncMode::QUEUE) {
            Artisan::queue('gateway:sync-invoices', [
                '--invoice' => $eligible->modelKeys(),
            ])->afterCommit();

            return null;
        }

        $orchestrator = $this->container->make(GatewayBillingOrchestrator::class);

        foreach ($eligible as $invoice) {
            try {
                $orchestrator->syncInvoice($invoice);
            } catch (\Throwable $e) {
                return $e->getMessage();
            }
        }

        return null;
    }
}
