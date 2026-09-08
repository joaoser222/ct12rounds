<?php

namespace App\Services\Billing;

use App\Contracts\BillingInvoiceSource;

class DiscountCalculator
{
    /**
     * Calculate discount for each installment based on source configuration.
     *
     * @param  array<int, float>  $grossValueInstallments
     * @return array<int, float>
     */
    public function calculate(
        BillingInvoiceSource $source,
        array $grossValueInstallments,
    ): array {
        $discountPercent = $source->billingDiscountPercent();
        $discountedInstallments = $source->billingDiscountedInstallments();

        if ($discountPercent === null || $discountedInstallments === null) {
            return $this->splitAmount(
                $source->billingDiscountValue(),
                count($grossValueInstallments),
            );
        }

        $eligibleInstallments = min(count($grossValueInstallments), max(0, $discountedInstallments));
        $discounts = array_fill(0, count($grossValueInstallments), 0.0);

        if ($eligibleInstallments === 0 || $discountPercent <= 0) {
            return $discounts;
        }

        $rawEligibleDiscounts = [];

        for ($index = 0; $index < $eligibleInstallments; $index++) {
            $rawEligibleDiscounts[] = round(
                $grossValueInstallments[$index] * ($discountPercent / 100),
                4,
            );
        }

        $resolvedEligibleDiscounts = $this->applyDiscountLimit(
            $rawEligibleDiscounts,
            $source->billingDiscountLimit(),
        );

        foreach ($resolvedEligibleDiscounts as $index => $discountValue) {
            $discounts[$index] = $discountValue;
        }

        return $discounts;
    }

    /**
     * Apply discount limit by distributing proportionally.
     *
     * @param  array<int, float>  $discounts
     * @return array<int, float>
     */
    private function applyDiscountLimit(array $discounts, ?float $discountLimit): array
    {
        if ($discountLimit === null || $discountLimit <= 0) {
            return $discounts;
        }

        $rawDiscountTotal = array_sum($discounts);

        if ($rawDiscountTotal <= $discountLimit) {
            return $discounts;
        }

        return $this->splitAmount($discountLimit, count($discounts));
    }

    /**
     * Split amount into equal parts handling cents correctly.
     *
     * @return array<int, float>
     */
    private function splitAmount(float $amount, int $parts): array
    {
        $scale = 10000;
        $total = (int) round($amount * $scale);
        $basePartValue = intdiv($total, $parts);
        $remainder = $total % $parts;
        $result = [];

        for ($index = 0; $index < $parts; $index++) {
            $result[] = ($basePartValue + ($index < $remainder ? 1 : 0)) / $scale;
        }

        return $result;
    }
}
