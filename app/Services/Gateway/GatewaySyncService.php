<?php

namespace App\Services\Gateway;

use App\Enums\Gateway\PostbackStatus;
use App\Models\GatewayAccount;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\GatewayTransfer;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use InvalidArgumentException;

class GatewaySyncService
{
    public const SCOPES = ['payments', 'transfers', 'customers', 'postbacks'];

    public const IN_PROGRESS_MESSAGE = 'Sincronização em andamento. Aguarde a finalização para realizar um novo procedimento';

    public function __construct(
        private readonly GatewayAdapterResolver $gatewayResolver,
        private readonly GatewayCustomerSanitizer $customerSanitizer,
    ) {}

    /**
     * @return array<string, int|bool>
     */
    public function sync(GatewayAccount $account, string $scope): array
    {
        if (! in_array($scope, self::SCOPES, true)) {
            throw new InvalidArgumentException("Unsupported gateway sync scope [{$scope}].");
        }

        $adapter = $this->gatewayResolver->paymentAdapter($account);

        return match ($scope) {
            'payments' => $this->syncPayments($account, $adapter),
            'transfers' => $this->syncTransfers($account, $adapter),
            'customers' => $this->syncCustomers($adapter),
            'postbacks' => $this->syncPostbacks($account, $adapter),
        };
    }

    /**
     * @return array<string, int|bool>
     */
    private function syncPayments(GatewayAccount $account, PaymentGatewayAdapter $adapter): array
    {
        $stats = [
            'invoices_created' => 0,
            'payments_created' => 0,
            'payments_skipped' => 0,
            'payments_refreshed' => 0,
            'payments_failed' => 0,
        ];

        if ($adapter instanceof AsaasPaymentGatewayAdapter) {
            $importStats = (new AsaasInvoiceImporter($adapter, $this->customerSanitizer))->importPayments();
            $stats['invoices_created'] = $importStats['invoices_created'] ?? 0;
            $stats['payments_created'] = $importStats['payments_created'] ?? 0;
            $stats['payments_skipped'] = $importStats['payments_skipped'] ?? 0;
            $stats['payments_failed'] = $importStats['payments_failed'] ?? 0;

            GatewayPayment::query()
                ->where('gateway_account_id', $account->getKey())
                ->whereNotNull('gateway_reference_key')
                ->chunkById(50, function ($payments) use ($adapter, &$stats): void {
                    foreach ($payments as $payment) {
                        if ($adapter->refreshPayment($payment)) {
                            $stats['payments_refreshed']++;
                        }
                    }
                });
        }

        return $stats;
    }

    /**
     * @return array<string, int|bool>
     */
    private function syncTransfers(GatewayAccount $account, PaymentGatewayAdapter $adapter): array
    {
        $stats = [
            'transfers_created' => 0,
            'transfers_skipped' => 0,
            'transfers_refreshed' => 0,
        ];

        if ($adapter instanceof AsaasPaymentGatewayAdapter) {
            $importStats = (new AsaasInvoiceImporter($adapter, $this->customerSanitizer))->importTransfers();
            $stats['transfers_created'] = $importStats['transfers_created'] ?? 0;
            $stats['transfers_skipped'] = $importStats['transfers_skipped'] ?? 0;

            GatewayTransfer::query()
                ->where('gateway_account_id', $account->getKey())
                ->whereNotNull('gateway_reference_key')
                ->chunkById(50, function ($transfers) use ($adapter, &$stats): void {
                    foreach ($transfers as $transfer) {
                        if ($adapter->refreshTransfer($transfer)) {
                            $stats['transfers_refreshed']++;
                        }
                    }
                });
        }

        return $stats;
    }

    /**
     * @return array<string, int|bool>
     */
    private function syncCustomers(PaymentGatewayAdapter $adapter): array
    {
        $stats = [
            'customers_created' => 0,
            'customers_updated' => 0,
            'customers_normalized' => 0,
            'customers_skipped' => 0,
        ];

        if ($adapter instanceof AsaasPaymentGatewayAdapter) {
            $importStats = (new AsaasInvoiceImporter($adapter, $this->customerSanitizer))->importCustomers();
            $stats['customers_created'] = $importStats['customers_created'] ?? 0;
            $stats['customers_updated'] = $importStats['customers_updated'] ?? 0;
            $stats['customers_normalized'] = $importStats['customers_normalized'] ?? 0;
            $stats['customers_skipped'] = $importStats['customers_skipped'] ?? 0;
        }

        return $stats;
    }

    /**
     * @return array<string, int|bool>
     */
    private function syncPostbacks(GatewayAccount $account, PaymentGatewayAdapter $adapter): array
    {
        $stats = [
            'postbacks_reprocessed' => 0,
            'postbacks_failed' => 0,
        ];

        if (! $adapter instanceof AsaasPaymentGatewayAdapter) {
            return $stats;
        }

        GatewayPostback::query()
            ->where('gateway_account_id', $account->getKey())
            ->where('status', PostbackStatus::FAILED->value)
            ->orderBy('id')
            ->chunkById(50, function ($postbacks) use ($adapter, &$stats): void {
                foreach ($postbacks as $postback) {
                    try {
                        $adapter->reprocessPostback($postback);
                        $stats['postbacks_reprocessed']++;
                    } catch (\Throwable) {
                        $stats['postbacks_failed']++;
                    }
                }
            });

        return $stats;
    }
}
