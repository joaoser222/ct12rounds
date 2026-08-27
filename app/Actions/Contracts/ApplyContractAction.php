<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\Actions\HiringLeads\ConvertHiringLeadAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\ContractResultDTO;
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
        if (! is_int($input)) {
            throw new \InvalidArgumentException('ApplyContractAction requires a contract ID.');
        }

        $contract = $this->contractRepository->findOrFail($input);

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

        $invoices = $this->generateContractInvoices->execute($contract->id);

        if (! $invoices->success) {
            return ActionResultDTO::failure($invoices->message, $invoices->errors);
        }

        return ActionResultDTO::success(
            ContractResultDTO::fromModel($contract->refresh()),
            'Contrato aplicado com sucesso: cliente criado, termos aceitos e faturas geradas.'
        );
    }
}