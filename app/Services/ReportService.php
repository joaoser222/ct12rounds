<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;
use App\Reports\Definitions\MonthlyBirthdaysReport;
use App\Reports\ReportDefinition;
use App\Reports\ReportRegistry;
use App\Reports\ReportResult;
use InvalidArgumentException;

class ReportService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function definitions(): array
    {
        return ReportRegistry::options();
    }

    public function find(string $key): ?ReportDefinition
    {
        return ReportRegistry::find($key);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function run(string $key, array $filters = [], ?User $user = null): ReportResult
    {
        $definition = $this->find($key);

        if ($definition === null) {
            throw new InvalidArgumentException("Report [{$key}] is not registered.");
        }

        return new ReportResult(
            definition: $definition,
            columns: $definition->columns,
            rows: $this->rows($key, $filters),
            meta: [
                'filters' => [
                    ...$filters,
                    'month' => $this->resolveMonth($filters['month'] ?? null),
                ],
                'user_id' => $user?->id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function rows(string $key, array $filters): array
    {
        return match ($key) {
            MonthlyBirthdaysReport::KEY => $this->monthlyBirthdayRows($filters),
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    private function monthlyBirthdayRows(array $filters): array
    {
        $month = $this->resolveMonth($filters['month'] ?? null);

        return Client::query()
            ->whereNotNull('birth_date')
            ->whereMonth('birth_date', $month)
            ->orderBy('name')
            ->get(['id', 'name', 'birth_date', 'phone', 'email'])
            ->sortBy(fn (Client $client): array => [$client->birth_date?->day ?? 0, (string) $client->name])
            ->values()
            ->map(fn (Client $client): array => [
                'id' => $client->getKey(),
                'name' => $client->name,
                'birth_date' => $client->birth_date?->format('Y-m-d'),
                'birth_day' => $client->birth_date?->day,
                'age' => $client->birth_date?->age,
                'phone' => $client->phone,
                'email' => $client->email,
            ])
            ->all();
    }

    private function resolveMonth(mixed $value): int
    {
        $month = (int) $value;

        return $month >= 1 && $month <= 12 ? $month : (int) now()->month;
    }
}
