<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Services\Billing\DiscountCalculator;
use App\Services\Billing\InstallmentSplitter;
use InvalidArgumentException;

/**
 * Applies the coupon discount and the resulting total to a contract that is still
 * pending terms acceptance, so the clauses shown to the client already match the
 * amount that will be invoiced once the registration is finalized.
 *
 * It never generates invoices and never changes `accepted_terms`: both belong to
 * GenerateContractInvoicesAction, which only runs after the terms are accepted.
 * The calculation is idempotent, so previewing the clauses repeatedly is safe.
 */
class ApplyContractDiscountAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = Contract::class;

    public function __construct(
        private readonly ContractRepositoryInterface $contractRepository,
        private readonly InstallmentSplitter $installmentSplitter,
        private readonly DiscountCalculator $discountCalculator,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! is_int($input)) {
            throw new InvalidArgumentException('ApplyContractDiscountAction requires a contract ID.');
        }

        $contract = $this->contractRepository->findOrFail($input);
        $contract->loadMissing('coupon');

        if ($contract->accepted_terms !== 'pending') {
            return $this->result($contract, false, 'Contrato já aceito, valores preservados.');
        }

        $grossValue = $contract->billingGrossValue();
        $discountTotal = round(array_sum($this->discountCalculator->calculate(
            $contract,
            $this->installmentSplitter->split($grossValue, $contract->billingInstallments()),
        )), 4);
        $total = round($grossValue - $discountTotal, 4);

        if ($contract->discount_value === $discountTotal && $contract->total === $total) {
            return $this->result($contract, false, 'Valores do contrato já estão aplicados.');
        }

        $contract->update([
            'discount_value' => $discountTotal,
            'total' => $total,
        ]);

        return $this->result($contract, true, 'Valores do contrato atualizados com o desconto do cupom.');
    }

    /**
     * @return ActionResultDTO
     */
    private function result(Contract $contract, bool $applied, string $message): ActionResultDTO
    {
        return ActionResultDTO::success([
            'discount_value' => $contract->discount_value,
            'total' => $contract->total,
            'accepted_terms' => $contract->accepted_terms,
            'applied' => $applied,
        ], $message);
    }
}
