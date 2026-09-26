<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Emails\BillingFailureData;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Setting;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use InvalidArgumentException;

/**
 * Reverts a public registration when the gateway refuses to charge the first
 * installment: the contract goes back to pending terms and the client returns to
 * pending, so the contract is not left accepted without a payment.
 *
 * The generated invoices are kept, because the unpaid ones are replaced on the next
 * contract application and paid ones must never be touched.
 */
class RevertContractAcceptanceAction extends BaseAction
{
    /** Module access is enforced by the caller (gateway sync job or command). */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    private const string NOTIFICATION_SETTING = 'billing_failure_notification_email';

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly NotifyContractBillingFailureAction $notifyBillingFailure,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof Invoice) {
            throw new InvalidArgumentException('RevertContractAcceptanceAction requires an Invoice.');
        }

        $invoice = $input;
        $contract = $invoice->billable;

        if (! $contract instanceof Contract) {
            return $this->skipped('A fatura não pertence a um contrato.');
        }

        if ($contract->accepted_terms !== 'accepted') {
            return $this->skipped('O contrato não está com os termos aceitos.');
        }

        if ($this->hasPaidInvoice($contract)) {
            return $this->skipped('O contrato possui fatura paga e não pode ser revertido.');
        }

        // O cliente e lido antes do desvinculo para ainda poder ser revertido.
        $client = $this->revertClient($contract);

        $this->contractRepository->update($contract, [
            'accepted_terms' => 'pending',
            // Sem o vinculo o contrato volta a aceitar cadastro e nova tentativa.
            'client_id' => null,
        ]);

        $this->sendNotification($contract, $client, $invoice);

        return ActionResultDTO::success([
            'contract_id' => $contract->getKey(),
            'accepted_terms' => $contract->accepted_terms,
            'client_id' => $contract->client_id,
            'client_status' => $client?->status?->value,
        ], 'Contrato revertido para pendente após recusa do gateway.');
    }

    private function hasPaidInvoice(Contract $contract): bool
    {
        return $contract->invoices()
            ->where('status', 'paid')
            ->exists();
    }

    private function revertClient(Contract $contract): ?Client
    {
        $client = $contract->client;

        if (! $client instanceof Client) {
            return null;
        }

        if ($client->status === ClientStatus::ACTIVE) {
            $this->clientRepository->update($client, [
                'status' => ClientStatus::PENDING->value,
            ]);
        }

        return $client;
    }

    private function sendNotification(Contract $contract, ?Client $client, Invoice $invoice): void
    {
        try {
            $this->notifyBillingFailure->execute(
                BillingFailureData::fromFailure(
                    recipient: $this->recipient(),
                    reason: BillingFailureData::REASON_INVOICE_ISSUANCE,
                    contract: $contract,
                    client: $client,
                    detail: 'O gateway recusou a cobrança da fatura #'.$invoice->getKey().'. O aceite do contrato foi revertido.',
                    actionUrl: route('contracts.show', $contract->getKey()),
                ),
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function recipient(): string
    {
        $configured = Setting::query()
            ->where('name', self::NOTIFICATION_SETTING)
            ->value('content');

        if (is_string($configured) && filter_var(trim($configured), FILTER_VALIDATE_EMAIL) !== false) {
            return trim($configured);
        }

        $fallback = (string) config('mail.from.address');

        return filter_var($fallback, FILTER_VALIDATE_EMAIL) !== false ? $fallback : '';
    }

    private function skipped(string $reason): ActionResultDTO
    {
        return ActionResultDTO::success(['reverted' => false], $reason);
    }
}
