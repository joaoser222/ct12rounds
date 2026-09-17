<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Contracts\CancelContractDTO;
use App\Enums\BillableStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Client;
use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Carbon\CarbonImmutable;

class CancelContractAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof CancelContractDTO) {
            throw new \InvalidArgumentException('CancelContractAction requires a CancelContractDTO.');
        }

        $dto = $input;

        $contract = $this->contractRepository->findOrFail($dto->contract_id);

        $this->contractRepository->update($contract, [
            'status' => BillableStatus::CANCELED,
        ]);

        $this->invoiceRepository->newQuery()
            ->where('billable_id', $contract->id)
            ->where('billable_type', $contract->getMorphClass())
            ->where('status', '!=', InvoiceStatus::PAID->value)
            ->update(['status' => InvoiceStatus::CANCELED->value]);

        $this->createCancellationFeeInvoice($contract);

        return ActionResultDTO::success(
            null,
            'Contrato cancelado com sucesso.'
        );
    }

    private function createCancellationFeeInvoice(Contract $contract): void
    {
        if ($contract->client_id === null) {
            return;
        }

        $cancellationFee = $contract->plan?->cancellation_fee;

        if ($cancellationFee === null || $cancellationFee <= 0) {
            return;
        }

        $this->invoiceRepository->create([
            'operation_type' => $contract->billingOperationType()->value,
            'invoice_type' => InvoiceType::STANDARD->value,
            'due_date' => CarbonImmutable::today()->format('Y-m-d'),
            'payment_method' => $contract->billingPaymentMethod()->value,
            'gross_value' => $cancellationFee,
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'paid_value' => 0,
            'installment_number' => 0,
            'status' => InvoiceStatus::PENDING->value,
            'annotations' => 'Multa de cancelamento',
            'visibility' => 'visible',
            'holder_id' => $contract->client_id,
            'holder_type' => (new Client)->getMorphClass(),
            'billable_id' => $contract->id,
            'billable_type' => $contract->getMorphClass(),
        ]);
    }
}
