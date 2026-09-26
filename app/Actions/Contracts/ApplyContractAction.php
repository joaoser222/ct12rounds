<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\Actions\HiringLeads\ConvertHiringLeadAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\ApplyContractDTO;
use App\DTOs\Contracts\ContractResultDTO;
use App\DTOs\Contracts\GenerateContractInvoicesDTO;
use App\Enums\Gateway\GatewaySyncMode;
use App\Models\Contract;
use App\Models\HiringLead;
use App\Repositories\Contracts\ContractRepositoryInterface;

class ApplyContractAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly ConvertHiringLeadAction $convertHiringLead,
        private readonly GenerateContractInvoicesAction $generateContractInvoices,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        [$contractId, $syncMode] = $this->resolveInput($input);

        $contract = $this->contractRepository->findOrFail($contractId);

        if ($contract->client_id !== null || $contract->accepted_terms !== 'pending') {
            return ActionResultDTO::failure(
                'Este contrato já foi aplicado.',
                ['contract' => 'Este contrato já foi aplicado.']
            );
        }

        /** @var HiringLead|null $lead */
        $lead = HiringLead::query()
            ->where('contract_id', $contract->getKey())
            ->latest('id')
            ->first();

        if ($lead === null) {
            return ActionResultDTO::failure(
                'O contrato ainda não possui um cadastro vinculado.',
                ['contract' => 'Aguardando o cliente preencher o cadastro pelo QR Code.']
            );
        }

        if ($lead->client_id === null) {
            $converted = $this->convertHiringLead->execute($lead);

            if (! $converted->success) {
                return ActionResultDTO::failure($converted->message, $converted->errors);
            }

            $lead->refresh();
        }

        $this->contractRepository->update($contract, [
            'client_id' => $lead->client_id,
        ]);

        $invoices = $this->generateContractInvoices->execute(
            new GenerateContractInvoicesDTO(contractId: $contract->id, syncMode: $syncMode),
        );

        if (! $invoices->success) {
            return ActionResultDTO::failure($invoices->message, $invoices->errors, $invoices->data);
        }

        return ActionResultDTO::success(
            ContractResultDTO::fromModel($contract->refresh()),
            'Contrato aplicado com sucesso: cliente criado, termos aceitos e faturas geradas.'
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

        if ($input instanceof ApplyContractDTO) {
            return [$input->contractId, $input->syncMode];
        }

        throw new \InvalidArgumentException('ApplyContractAction requires a contract ID or an ApplyContractDTO.');
    }
}