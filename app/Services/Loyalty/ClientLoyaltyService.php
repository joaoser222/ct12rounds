<?php

namespace App\Services\Loyalty;

use App\Models\Client;
use App\Models\LoyaltyLevel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ClientLoyaltyService
{
    private const GAP_THRESHOLD_MONTHS = 3;

    /**
     * Calculate the current streak months for a client based on invoice continuity.
     *
     * A gap of >= 3 consecutive months without any invoice means the contract
     * ended and the streak resets to level 1 (Novato).
     * Unpaid invoices do NOT break the streak — only the absence of invoices matters.
     *
     * @return array{streak_months: int, loyalty_level_id: int|null, loyalty_since: string|null}
     */
    public function calculateStreak(Client $client): array
    {
        $months = $this->getInvoicedMonths($client);

        if ($months->isEmpty()) {
            return [
                'streak_months' => 0,
                'loyalty_level_id' => null,
                'loyalty_since' => null,
            ];
        }

        $streak = 0;
        $gap = 0;
        $streakStart = null;
        $now = Carbon::now()->startOfMonth();

        for ($offset = 0; $offset < 240; $offset++) {
            $month = $now->copy()->subMonths($offset)->format('Y-m');

            if ($months->contains($month)) {
                $streak++;
                $gap = 0;
                $streakStart = $month;
            } else {
                $gap++;
                if ($gap >= self::GAP_THRESHOLD_MONTHS) {
                    break;
                }
            }
        }

        $level = $this->resolveLevel($streak);

        return [
            'streak_months' => $streak,
            'loyalty_level_id' => $level?->id,
            'loyalty_since' => $streak > 0 ? $streakStart.'-01' : null,
        ];
    }

    /**
     * Refresh a single client's loyalty data.
     */
    public function refreshClient(Client $client): void
    {
        $data = $this->calculateStreak($client);

        $client->update([
            'loyalty_streak_months' => $data['streak_months'],
            'loyalty_level_id' => $data['loyalty_level_id'],
            'loyalty_since' => $data['loyalty_since'],
        ]);
    }

    /**
     * Refresh all active clients' loyalty data.
     */
    public function refreshAll(): int
    {
        $clients = Client::query()
            ->where('status', 'active')
            ->get();

        $count = 0;

        foreach ($clients as $client) {
            $this->refreshClient($client);
            $count++;
        }

        return $count;
    }

    /**
     * Get distinct months (Y-m) where the client has at least one invoice.
     */
    private function getInvoicedMonths(Client $client): Collection
    {
        return $client->invoices()
            ->selectRaw('DISTINCT TO_CHAR(due_date, \'YYYY-MM\') as month')
            ->pluck('month');
    }

    /**
     * Resolve the highest loyalty level for the given streak months.
     */
    private function resolveLevel(int $streakMonths): ?LoyaltyLevel
    {
        return LoyaltyLevel::query()
            ->where('min_months', '<=', $streakMonths)
            ->orderByDesc('min_months')
            ->first();
    }
}
