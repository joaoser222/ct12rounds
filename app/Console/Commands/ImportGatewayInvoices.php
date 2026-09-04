<?php

namespace App\Console\Commands;

use App\Models\GatewayAccount;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use App\Services\Gateway\AsaasInvoiceImporter;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('gateway:import-invoices {--account=Asaas : Gateway account name to import from}')]
#[Description('Import already-issued Asaas payments as receivable invoices into Ct12rounds')]
class ImportGatewayInvoices extends Command
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

        $this->components->info("Importing Asaas payments into invoices from account \"{$accountName}\"...");

        $stats = (new AsaasInvoiceImporter($adapter))->importPayments();

        $this->components->twoColumnDetail('Invoices created', (string) $stats['invoices_created']);
        $this->components->twoColumnDetail('Payments created', (string) $stats['payments_created']);
        $this->components->twoColumnDetail('Payments skipped (existing)', (string) $stats['payments_skipped']);
        $this->components->twoColumnDetail('Payments failed (no customer)', (string) $stats['payments_failed']);

        return self::SUCCESS;
    }
}
