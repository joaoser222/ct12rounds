<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Models\Contract;
use App\Models\Invoice;
use Carbon\CarbonImmutable;

class DashboardService
{
    /**
     * @return array{labels: array<int, string>, contracts: array<int, int>}
     */
    public function contractsByMonth(int $months = 12): array
    {
        [$start, $labels, $keys] = $this->monthWindow($months);

        $counts = Contract::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("to_char(created_at, 'YYYY-MM') as period, count(*) as aggregate")
            ->groupBy('period')
            ->pluck('aggregate', 'period');

        return [
            'labels' => $labels,
            'contracts' => array_map(
                fn (string $key): int => (int) $counts->get($key, 0),
                $keys
            ),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public function receivedByMonth(int $months = 12): array
    {
        [$start, $labels, $keys] = $this->monthWindow($months);

        $totals = Invoice::query()
            ->where('operation_type', OperationType::RECEIVABLE->value)
            ->where('status', InvoiceStatus::PAID->value)
            ->where('payment_date', '>=', $start)
            ->selectRaw("to_char(payment_date, 'YYYY-MM') as period, coalesce(sum(paid_value), 0) as aggregate")
            ->groupBy('period')
            ->pluck('aggregate', 'period');

        return [
            'labels' => $labels,
            'values' => array_map(
                fn (string $key): float => round((float) $totals->get($key, 0), 2),
                $keys
            ),
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    public function contractsByPlan(): array
    {
        $rows = Contract::query()
            ->leftJoin('plans', 'plans.id', '=', 'contracts.plan_id')
            ->selectRaw('coalesce(plans.name, contracts.plan_name) as label, count(*) as aggregate')
            ->groupBy('label')
            ->orderByDesc('aggregate')
            ->get();

        return [
            'labels' => $rows->pluck('label')->all(),
            'values' => $rows->pluck('aggregate')->map(fn ($value): int => (int) $value)->all(),
        ];
    }

    /**
     * @return array{pending: float, overdue: float}
     */
    public function receivableOutstanding(): array
    {
        $totals = Invoice::query()
            ->where('operation_type', OperationType::RECEIVABLE->value)
            ->whereIn('status', [
                InvoiceStatus::PENDING->value,
                InvoiceStatus::WAITING->value,
                InvoiceStatus::OVERDUED->value,
            ])
            ->selectRaw('status, coalesce(sum(total - paid_value), 0) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $pending = (float) $totals->get(InvoiceStatus::PENDING->value, 0)
            + (float) $totals->get(InvoiceStatus::WAITING->value, 0);

        return [
            'pending' => round($pending, 2),
            'overdue' => round((float) $totals->get(InvoiceStatus::OVERDUED->value, 0), 2),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: array<int, string>, 2: array<int, string>}
     */
    private function monthWindow(int $months): array
    {
        $start = CarbonImmutable::today()->startOfMonth()->subMonths($months - 1);

        $labels = [];
        $keys = [];

        for ($offset = 0; $offset < $months; $offset++) {
            $month = $start->addMonthsNoOverflow($offset);

            $keys[] = $month->format('Y-m');
            $labels[] = $month->format('m/Y');
        }

        return [$start, $labels, $keys];
    }
}
