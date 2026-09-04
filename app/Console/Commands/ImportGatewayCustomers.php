<?php

namespace App\Console\Commands;

use App\Models\GatewayAccount;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use App\Services\Gateway\AsaasInvoiceImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gateway:import-customers {--account=Asaas : Gateway account name to import from}')]
#[Description('Import Asaas customers into Ct12rounds clients and gateway_customers')]
class ImportGatewayCustomers extends Command
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

        $this->components->info("Importing Asaas customers from account \"{$accountName}\"...");

        $stats = (new AsaasInvoiceImporter($adapter))->importCustomers();

        $this->components->twoColumnDetail('Customers created', (string) $stats['customers_created']);
        $this->components->twoColumnDetail('Customers skipped (existing)', (string) $stats['customers_skipped']);

        return self::SUCCESS;
    }
}
