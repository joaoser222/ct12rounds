<?php

namespace App\Reports;

use App\Reports\Definitions\MonthlyBirthdaysReport;

class ReportRegistry
{
    /**
     * @return array<int, ReportDefinition>
     */
    public static function all(): array
    {
        return [
            MonthlyBirthdaysReport::definition(),
        ];
    }

    public static function find(string $key): ?ReportDefinition
    {
        return collect(self::all())
            ->first(fn (ReportDefinition $definition): bool => $definition->key === $key);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function options(): array
    {
        return collect(self::all())
            ->map(fn (ReportDefinition $definition): array => $definition->toArray())
            ->values()
            ->all();
    }
}
