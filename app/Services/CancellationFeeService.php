<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Resolves the cancellation fee percentage for contracts, applying the
 * configured percentage to the total contract value.
 */
class CancellationFeeService
{
    private const DEFAULT_PERCENTAGE = 25.0;

    private const PERCENTAGE_SETTING = 'cancellation_fee_percentage';

    public function configuredPercentage(): float
    {
        $percentage = Setting::query()
            ->where('name', self::PERCENTAGE_SETTING)
            ->value('content');

        if (! is_numeric($percentage)) {
            return self::DEFAULT_PERCENTAGE;
        }

        $percentage = (float) $percentage;

        return $percentage > 0 ? $percentage : self::DEFAULT_PERCENTAGE;
    }

    public function effectivePercentage(?float $planPercentage): float
    {
        if ($planPercentage !== null && $planPercentage > 0) {
            return $planPercentage;
        }

        return $this->configuredPercentage();
    }

    public function feeValue(float $total, ?float $planPercentage): ?float
    {
        if ($total <= 0) {
            return null;
        }

        $percentage = $this->effectivePercentage($planPercentage);

        if ($percentage <= 0) {
            return null;
        }

        return round($total * ($percentage / 100), 2);
    }
}