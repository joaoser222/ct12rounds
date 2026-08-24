<?php

namespace App\Services\Gateway;

use App\Enums\Gateway\TransactionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OperationType;
use App\Models\Client;
use App\Models\GatewayCustomer;
use App\Models\GatewayPayment;
use App\Models\GatewayTransfer;
use App\Models\Invoice;
use App\PaymentGateways\Adapters\AsaasPaymentGatewayAdapter;
use Carbon\CarbonImmutable;

/**
 * Imports data already issued in Asaas into Gymnamite.
 *
 * Each concern is exposed as an independent method so it can be run on its own
 * command: customers, payments (as receivable invoices) and transfers.
 * Every method is idempotent on the Asaas reference key.
 */
class AsaasInvoiceImporter
{
    private const PAGE_LIMIT = 100;

    public function __construct(private readonly AsaasPaymentGatewayAdapter $adapter) {}

    /**
     * @return array<string, int>
     */
    public function importCustomers(): array
    {
        $stats = ['customers_created' => 0, 'customers_skipped' => 0];

        $this->syncCustomers($stats);

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    public function importPayments(): array
    {
        $stats = [
            'invoices_created' => 0,
            'payments_created' => 0,
            'payments_skipped' => 0,
            'payments_failed' => 0,
        ];

        $this->syncPayments($stats);

        return $stats;
    }

    /**
     * @return array<string, int>
     */
    public function importTransfers(): array
    {
        $stats = ['transfers_created' => 0, 'transfers_skipped' => 0];

        $this->syncTransfers($stats);

        return $stats;
    }

    /**
     * Imports everything in dependency order (customers, then payments).
     *
     * @return array<string, int>
     */
    public function import(): array
    {
        return array_merge(
            $this->importCustomers(),
            $this->importPayments(),
            $this->importTransfers(),
        );
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function syncCustomers(array &$stats): void
    {
        $offset = 0;

        do {
            $response = $this->adapter->listCustomers([
                'limit' => self::PAGE_LIMIT,
                'offset' => $offset,
            ]);

            $customers = $response['data'] ?? [];

            foreach ($customers as $body) {
                $this->importCustomer($body, $stats);
            }

            $offset += self::PAGE_LIMIT;
        } while (($response['hasMore'] ?? false) === true);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, int>  $stats
     */
    private function importCustomer(array $body, array &$stats): void
    {
        $referenceKey = $body['id'] ?? null;

        if ($referenceKey === null) {
            return;
        }

        $accountId = $this->adapter->gatewayAccount()->id;

        if (GatewayCustomer::where('gateway_reference_key', $referenceKey)
            ->where('gateway_account_id', $accountId)
            ->exists()) {
            $stats['customers_skipped']++;

            return;
        }

        $client = $this->resolveClient($body);

        GatewayCustomer::create([
            'gateway_reference_key' => $referenceKey,
            'holder_id' => $client->id,
            'holder_type' => $client->getMorphClass(),
            'gateway_account_id' => $accountId,
        ]);

        $stats['customers_created']++;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function resolveClient(array $body): Client
    {
        $referenceKey = $body['id'] ?? null;
        $document = preg_replace('/\D/', '', (string) ($body['cpfCnpj'] ?? ''));
        $phone = preg_replace('/\D/', '', (string) ($body['phone'] ?? ''));

        if ($document !== '') {
            $existing = Client::where('document', $document)->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        return Client::create([
            'name' => $body['name'] ?? 'Cliente Asaas',
            'email' => $body['email'] ?? null,
            'phone' => $phone !== '' ? $phone : $this->placeholderDocument($referenceKey, 11),
            'document' => $document !== '' ? $document : $this->placeholderDocument($referenceKey, 11),
            'birth_date' => isset($body['birthDate']) ? CarbonImmutable::parse($body['birthDate']) : null,
        ]);
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function syncPayments(array &$stats): void
    {
        $offset = 0;

        do {
            $response = $this->adapter->listPayments([
                'limit' => self::PAGE_LIMIT,
                'offset' => $offset,
            ]);

            $payments = $response['data'] ?? [];

            foreach ($payments as $body) {
                $this->importPayment($body, $stats);
            }

            $offset += self::PAGE_LIMIT;
        } while (($response['hasMore'] ?? false) === true);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, int>  $stats
     */
    private function importPayment(array $body, array &$stats): void
    {
        $referenceKey = $body['id'] ?? null;

        if ($referenceKey === null) {
            return;
        }

        $accountId = $this->adapter->gatewayAccount()->id;

        if (GatewayPayment::where('gateway_reference_key', $referenceKey)
            ->where('gateway_account_id', $accountId)
            ->exists()) {
            $stats['payments_skipped']++;

            return;
        }

        $customerReference = $body['customer'] ?? null;

        if (is_array($customerReference)) {
            $customerReference = $customerReference['id'] ?? null;
        }

        $gatewayCustomer = GatewayCustomer::where('gateway_reference_key', $customerReference)
            ->where('gateway_account_id', $accountId)
            ->first();

        if ($gatewayCustomer === null || ! $gatewayCustomer->holder instanceof Client) {
            $stats['payments_failed']++;

            return;
        }

        $client = $gatewayCustomer->holder;

        $invoice = Invoice::create([
            'operation_type' => OperationType::RECEIVABLE,
            'invoice_type' => InvoiceType::STANDARD,
            'due_date' => isset($body['dueDate'])
                ? CarbonImmutable::parse($body['dueDate'])
                : CarbonImmutable::today(),
            'payment_method' => $this->adapter->paymentMethodFromBillingType(
                (string) ($body['billingType'] ?? 'UNDEFINED'),
            ),
            'gross_value' => (float) ($body['value'] ?? 0),
            'discount_value' => 0,
            'interest_value' => 0,
            'fine_value' => 0,
            'status' => InvoiceStatus::PENDING,
            'holder_id' => $client->id,
            'holder_type' => $client->getMorphClass(),
            'external_reference' => $referenceKey,
            'annotations' => $body['description'] ?? null,
        ]);

        $stats['invoices_created']++;

        $this->adapter->importPayment($body, $gatewayCustomer, $invoice);

        $this->applyInvoiceStatus($invoice, $body);

        $stats['payments_created']++;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function applyInvoiceStatus(Invoice $invoice, array $body): void
    {
        $transactionStatus = $this->adapter->transactionStatusFromAsaas(
            (string) ($body['status'] ?? 'PENDING'),
        );

        $invoiceStatus = match ($transactionStatus) {
            TransactionStatus::PAID => InvoiceStatus::PAID,
            TransactionStatus::OVERDUE => InvoiceStatus::OVERDUED,
            TransactionStatus::CANCELED => InvoiceStatus::CANCELED,
            default => InvoiceStatus::WAITING,
        };

        $invoice->update([
            'status' => $invoiceStatus,
            'payment_date' => $transactionStatus === TransactionStatus::PAID && isset($body['paymentDate'])
                ? CarbonImmutable::parse($body['paymentDate'])
                : $invoice->payment_date,
            'paid_value' => $transactionStatus === TransactionStatus::PAID
                ? (float) ($body['value'] ?? $invoice->total)
                : 0,
        ]);
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function syncTransfers(array &$stats): void
    {
        $offset = 0;

        do {
            $response = $this->adapter->listTransfers([
                'limit' => self::PAGE_LIMIT,
                'offset' => $offset,
            ]);

            $transfers = $response['data'] ?? [];

            foreach ($transfers as $body) {
                $this->importTransfer($body, $stats);
            }

            $offset += self::PAGE_LIMIT;
        } while (($response['hasMore'] ?? false) === true);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, int>  $stats
     */
    private function importTransfer(array $body, array &$stats): void
    {
        $referenceKey = $body['id'] ?? null;

        if ($referenceKey === null) {
            return;
        }

        $accountId = $this->adapter->gatewayAccount()->id;

        if (GatewayTransfer::where('gateway_reference_key', $referenceKey)
            ->where('gateway_account_id', $accountId)
            ->exists()) {
            $stats['transfers_skipped']++;

            return;
        }

        $this->adapter->importTransfer($body);

        $stats['transfers_created']++;
    }

    private function placeholderDocument(?string $referenceKey, int $length): string
    {
        return str_pad(
            (string) (abs(crc32((string) $referenceKey)) % (10 ** $length)),
            $length,
            '0',
            STR_PAD_LEFT,
        );
    }
}
