<?php

namespace App\Console\Commands;

use App\Models\GatewayAccount;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use App\Services\Gateway\AsaasInvoiceImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gateway:import-transfers {--account=Asaas : Gateway account name to import from}')]
#[Description('Import Asaas transfers into Ct12rounds gateway transfers')]
class ImportGatewayTransfers extends Command
{
    public function handle(): int
    {
        $accountName = $this->option('account');

        $account = GatewayAccount::where('name', $accountName)->first();

        if ($account === null) {
            $this->components->error("Gateway account \"{$accountName}\" not found. Create a GatewayAccount with this name and configure its settings.");

            return self::FAILURE;
        }

        $adapter = app(AsaasPaymentGatewayAdapter::class, ['gatewayAccount' => $account]);

        $this->components->info("Importing Asaas transfers from account \"{$accountName}\"...");

        $stats = (new AsaasInvoiceImporter($adapter))->importTransfers();

        $this->components->twoColumnDetail('Transfers created', (string) $stats['transfers_created']);
        $this->components->twoColumnDetail('Transfers skipped (existing)', (string) $stats['transfers_skipped']);

        return self::SUCCESS;
    }
}
