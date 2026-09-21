<?php

namespace App\Console\Commands;

use App\Services\Loyalty\ClientLoyaltyService;
use Illuminate\Console\Command;

class LoyaltyRefreshCommand extends Command
{
    protected $signature = 'loyalty:refresh';

    protected $description = 'Refresh loyalty streak and level for all active clients';

    public function handle(ClientLoyaltyService $loyaltyService): int
    {
        $count = $loyaltyService->refreshAll();

        $this->info("Loyalty refreshed for {$count} clients.");

        return Command::SUCCESS;
    }
}
