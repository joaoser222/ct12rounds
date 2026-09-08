<?php

namespace App\Services\Billing;

class InstallmentSplitter
{
    /**
     * Split amount into equal installments handling cents correctly.
     *
     * @return array<int, float>
     */
    public function split(float $amount, int $installments): array
    {
        $scale = 10000;
        $total = (int) round($amount * $scale);
        $baseInstallmentValue = intdiv($total, $installments);
        $remainder = $total % $installments;
        $parts = [];

        for ($index = 0; $index < $installments; $index++) {
            $parts[] = ($baseInstallmentValue + ($index < $remainder ? 1 : 0)) / $scale;
        }

        return $parts;
    }
}
