<?php

namespace App\PaymentGateways\Adapters;

use App\Enums\Gateway\PostbackStatus;
use App\Enums\Gateway\TransactionStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Client;
use App\Models\GatewayAccount;
use App\Models\GatewayCreditCard;
use App\Models\GatewayCustomer;
use App\Models\GatewayInvoice;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\GatewayTransfer;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\Trainer;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\PaymentGateways\Contracts\PaymentGatewayInvoicingAdapter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class AsaasPaymentGatewayAdapter implements PaymentGatewayAdapter, PaymentGatewayInvoicingAdapter
{
    private const ASAAS_STATUS_MAP = [
        'PENDING' => TransactionStatus::PENDING,
        'RECEIVED' => TransactionStatus::PAID,
        'CONFIRMED' => TransactionStatus::PAID,
        'OVERDUE' => TransactionStatus::OVERDUE,
        'REFUNDED' => TransactionStatus::REFUNDED,
        'RECEIVED_IN_CASH' => TransactionStatus::PAID,
        'REFUND_REQUESTED' => TransactionStatus::PENDING,
        'CHARGEBACK_REQUESTED' => TransactionStatus::FAILED,
        'CHARGEBACK_DISPUTE' => TransactionStatus::PENDING,
        'AWAITING_CHARGEBACK_REVERSAL' => TransactionStatus::PENDING,
        'DUNNING_REQUESTED' => TransactionStatus::PENDING,
        'DUNNING_RECEIVED' => TransactionStatus::PAID,
        'AWAITING_RISK_ANALYSIS' => TransactionStatus::PENDING,
    ];

    private const BILLING_TYPE_MAP = [
        'BOLETO' => 'boleto',
        'PIX' => 'pix',
        'CREDIT_CARD' => 'credit_card',
    ];

    public function __construct(
        private ?GatewayAccount $gatewayAccount = null,
    ) {
        $this->gatewayAccount ??= GatewayAccount::where('name', 'Asaas')->first();

        if ($this->gatewayAccount === null) {
            throw new RuntimeException(
                'No Asaas gateway account found. Create a GatewayAccount with name "Asaas" and configure its settings.',
            );
        }
    }

    public function gatewayAccount(): GatewayAccount
    {
        return $this->gatewayAccount;
    }

    public function createCustomer(Model $holder): GatewayCustomer
    {
        $existingCustomer = $this->findExistingCustomer($holder);

        if ($existingCustomer !== null) {
            return $existingCustomer;
        }

        $customerData = $this->buildCustomerPayload($holder);

        $response = $this->client()->post('/customers', $customerData);

        $body = $response->throw()->json();

        /** @var GatewayCustomer $customer */
        $customer = new GatewayCustomer;

        $customer->fill([
            'gateway_reference_key' => $body['id'],
            'holder_id' => $holder->getKey(),
            'holder_type' => $holder->getMorphClass(),
            'gateway_account_id' => $this->gatewayAccount->id,
        ]);

        $customer->save();

        return $customer;
    }

    public function findCustomer(GatewayCustomer $customer): ?array
    {
        return $this->getOrNull("/customers/{$customer->gateway_reference_key}");
    }

    public function syncCustomer(GatewayCustomer $customer): bool
    {
        $holder = $customer->holder;

        if ($holder === null) {
            throw new InvalidArgumentException('GatewayCustomer has no associated holder.');
        }

        $payload = $this->buildCustomerPayload($holder);

        try {
            $this->client()->put(
                "/customers/{$customer->gateway_reference_key}",
                $payload,
            )->throw();

            return true;
        } catch (RequestException) {
            return false;
        }
    }

    public function createPayment(Invoice $invoice, GatewayCustomer $customer, array $options = []): GatewayPayment
    {
        $billingType = $this->resolveBillingType($invoice->payment_method?->value);

        $payload = $this->sanitizePayload([
            'customer' => $customer->gateway_reference_key,
            'billingType' => $billingType,
            'value' => $invoice->total,
            'dueDate' => $invoice->due_date->format('Y-m-d'),
            'description' => $options['description'] ?? null,
            'externalReference' => (string) $invoice->id,
            'installmentCount' => $options['installment_count'] ?? null,
            'installmentValue' => $options['installment_value'] ?? null,
            'discount' => $this->buildDiscountPayload($invoice),
            'fine' => $options['fine'] ?? null,
            'interest' => $options['interest'] ?? null,
            'postalService' => $options['postal_service'] ?? false,
        ]);

        $response = $this->client()->post('/payments', $payload)->throw();

        $body = $response->json();

        return $this->storeGatewayPayment($body, $invoice, $customer);
    }

    public function findPayment(GatewayPayment $payment): ?array
    {
        return $this->getOrNull("/payments/{$payment->gateway_reference_key}");
    }

    public function payWithCreditCard(GatewayPayment $payment, array $creditCardData): GatewayPayment
    {
        $payload = [
            'creditCard' => $creditCardData['creditCard'],
        ];

        if (isset($creditCardData['creditCardHolderInfo'])) {
            $payload['creditCardHolderInfo'] = $creditCardData['creditCardHolderInfo'];
        }

        $response = $this->client()->post(
            "/payments/{$payment->gateway_reference_key}/payWithCreditCard",
            $payload,
        )->throw();

        $body = $response->json();

        $this->updateGatewayPaymentFromResponse($payment, $body);

        if (isset($body['creditCard'])) {
            $this->storeCreditCard($body['creditCard'], $payment->gatewayCustomer);
        }

        return $payment->fresh();
    }

    public function refundPayment(GatewayPayment $payment, ?int $value = null): GatewayPayment
    {
        $payload = $value !== null
            ? ['value' => $value]
            : [];

        $response = $this->client()->post(
            "/payments/{$payment->gateway_reference_key}/refund",
            $payload,
        )->throw();

        $body = $response->json();

        $this->updateGatewayPaymentFromResponse($payment, $body);

        return $payment->fresh();
    }

    public function getPixQrCode(GatewayPayment $payment): ?array
    {
        try {
            $response = $this->client()->get(
                "/payments/{$payment->gateway_reference_key}/pixQrCode",
            );

            return $response->throw()->json();
        } catch (RequestException $e) {
            if ($e->response->status() === 404) {
                return null;
            }

            throw $e;
        }
    }

    public function tokenizeCreditCard(array $cardData): ?array
    {
        $response = $this->client()->post('/creditCard/tokenize', $cardData)->throw();

        return $response->json();
    }

    public function createTransfer(array $data): GatewayTransfer
    {
        $payload = $this->sanitizePayload([
            'value' => $data['value'],
            'walletId' => $data['wallet_id'] ?? $this->gatewayAccount->settings['wallet_id'] ?? null,
            'pixAddressKey' => $data['pix_address_key'] ?? null,
            'pixAddressKeyType' => $data['pix_address_key_type'] ?? null,
            'bankAccount' => $data['bank_account'] ?? null,
            'operationType' => $data['operation_type'] ?? 'PIX',
            'description' => $data['description'] ?? null,
        ]);

        $response = $this->client()->post('/transfers', $payload)->throw();

        $body = $response->json();

        /** @var GatewayTransfer $transfer */
        $transfer = GatewayTransfer::create([
            'gateway_reference_key' => $body['id'],
            'gross_value' => $body['value'],
            'fee_value' => ($body['value'] - ($body['netValue'] ?? $body['value'])),
            'status' => $this->mapTransferStatus($body['status']),
            'gateway_account_id' => $this->gatewayAccount->id,
            'gateway_transfer_recipient_id' => $data['gateway_transfer_recipient_id'] ?? null,
        ]);

        return $transfer;
    }

    public function findTransfer(GatewayTransfer $transfer): ?array
    {
        return $this->getOrNull("/transfers/{$transfer->gateway_reference_key}");
    }

    public function processPostback(array $payload): GatewayPostback
    {
        $event = $payload['event'] ?? 'UNKNOWN';
        $externalEventKey = $payload['id'] ?? null;

        if (is_string($externalEventKey) && filled($externalEventKey)) {
            $existingPostback = GatewayPostback::query()
                ->where('gateway_account_id', $this->gatewayAccount->id)
                ->where('external_event_key', $externalEventKey)
                ->first();

            if ($existingPostback !== null) {
                return $existingPostback;
            }
        }

        try {
            /** @var GatewayPostback $postback */
            $postback = GatewayPostback::create([
                'postback_event' => $event,
                'postback_type' => $this->resolvePostbackType($event),
                'external_event_key' => $externalEventKey,
                'payload' => $payload,
                'status' => PostbackStatus::PENDING,
                'gateway_account_id' => $this->gatewayAccount->id,
            ]);
        } catch (QueryException $exception) {
            if (! is_string($externalEventKey) || ! str_contains($exception->getMessage(), 'unique')) {
                throw $exception;
            }

            return GatewayPostback::query()
                ->where('gateway_account_id', $this->gatewayAccount->id)
                ->where('external_event_key', $externalEventKey)
                ->firstOrFail();
        }

        try {
            $this->handlePostbackEvent($event, $payload, $postback);

            $postback->update(['status' => PostbackStatus::SUCCESS]);
        } catch (\Throwable $e) {
            $postback->update(['status' => PostbackStatus::FAILED]);

            throw $e;
        }

        return $postback->fresh();
    }

    public function getBalance(): ?array
    {
        try {
            $response = $this->client()->get('/finance/balance');

            return $response->throw()->json();
        } catch (RequestException) {
            return null;
        }
    }

    public function requestInvoice(GatewayPayment $payment, array $configuration, ?GatewayInvoice $invoice = null): GatewayInvoice
    {
        $payload = $this->sanitizePayload([
            'customer' => $payment->gatewayCustomer?->gateway_reference_key,
            'payment' => $payment->gateway_reference_key,
            'value' => $payment->gross_value,
            'serviceDescription' => $configuration['service_description'] ?? null,
            'municipalServiceId' => $configuration['municipal_service_id'] ?? null,
            'municipalServiceCode' => $configuration['municipal_service_code'] ?? null,
            'deductions' => $configuration['deductions'] ?? null,
            'observations' => $configuration['observations'] ?? null,
            'externalReference' => (string) $payment->invoice_id,
        ]);

        $body = $this->client()->post('/invoices', $payload)->throw()->json();

        $invoice ??= new GatewayInvoice([
            'gateway_account_id' => $this->gatewayAccount->id,
            'gateway_payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
        ]);

        $this->updateGatewayInvoice($invoice, $body);

        return $invoice->fresh();
    }

    private function invoiceAttributes(array $body): array
    {
        return [
            'gateway_reference_key' => $body['id'] ?? null,
            'status' => $this->mapInvoiceStatus($body['status'] ?? 'PENDING'),
            'status_description' => $body['statusDescription'] ?? $body['status_description'] ?? null,
            'invoice_number' => $body['number'] ?? $body['invoiceNumber'] ?? null,
            'validation_code' => $body['validationCode'] ?? null,
            'service_description' => $body['serviceDescription'] ?? null,
            'observations' => $body['observations'] ?? null,
            'value' => $body['value'] ?? null,
            'deductions' => $body['deductions'] ?? null,
            'effective_date' => $body['effectiveDate'] ?? null,
            'pdf_url' => $body['pdfUrl'] ?? $body['pdf_url'] ?? null,
            'xml_url' => $body['xmlUrl'] ?? $body['xml_url'] ?? null,
            'municipal_service_id' => $body['municipalServiceId'] ?? null,
            'municipal_service_code' => $body['municipalServiceCode'] ?? null,
            'municipal_service_description' => $body['municipalServiceDescription'] ?? null,
            'external_reference' => $body['externalReference'] ?? null,
            'payload' => $body,
        ];
    }

    public function getMunicipalOptions(): array
    {
        return $this->client()->get('/invoices/municipalOptions')->throw()->json();
    }

    public function configureFiscalData(array $data): array
    {
        return $this->client()->put('/invoices/municipalConfiguration', $data)->throw()->json();
    }

    public function getMunicipalServices(array $filters = []): array
    {
        return $this->client()->get('/invoices/municipalServices', $filters)->throw()->json();
    }

    public function scheduleInvoice(GatewayInvoice $invoice): GatewayInvoice
    {
        $body = $this->client()->post("/invoices/{$invoice->gateway_reference_key}/schedule")->throw()->json();

        return $this->updateGatewayInvoice($invoice, $body);
    }

    public function findInvoice(GatewayInvoice $invoice): ?array
    {
        return $this->getOrNull("/invoices/{$invoice->gateway_reference_key}");
    }

    public function syncInvoice(GatewayInvoice $invoice, bool $force = false): ?GatewayInvoice
    {
        $body = $this->findInvoice($invoice);

        if ($body === null) {
            return null;
        }

        $currentStatus = $invoice->status instanceof \App\Enums\Gateway\InvoiceStatus
            ? $invoice->status
            : \App\Enums\Gateway\InvoiceStatus::UNKNOWN;
        $newStatus = $this->mapInvoiceStatus($body['status'] ?? 'PENDING');

        // Idempotency: without --force, do not overwrite the record when the
        // status reported by the provider has not changed from what we already have.
        if (! $force && $currentStatus === $newStatus) {
            return $invoice->fresh();
        }

        return $this->updateGatewayInvoice($invoice, $body);
    }

    public function authorizeInvoice(GatewayInvoice $invoice): GatewayInvoice
    {
        $body = $this->client()->post("/invoices/{$invoice->gateway_reference_key}/authorize")->throw()->json();

        return $this->updateGatewayInvoice($invoice, $body);
    }

    public function cancelInvoice(GatewayInvoice $invoice, ?string $reason = null): GatewayInvoice
    {
        $body = $this->client()->delete("/invoices/{$invoice->gateway_reference_key}", $this->sanitizePayload([
            'reason' => $reason,
        ]))->throw()->json();

        return $this->updateGatewayInvoice($invoice, $body);
    }

    public function registerWebhook(string $url, string $type = 'NONE', array $events = []): array
    {
        $payload = [
            'url' => $url,
            'email' => $type,
            'enabled' => true,
            'interrupted' => false,
            'apiVersion' => 3,
            'events' => $events,
        ];

        $response = $this->client()->post('/webhook', $payload)->throw();

        return $response->json();
    }

    public function unregisterWebhook(string $webhookId): bool
    {
        try {
            $this->client()->delete("/webhook/{$webhookId}")->throw();

            return true;
        } catch (RequestException) {
            return false;
        }
    }

    public function configureNotifications(array $data): array
    {
        $response = $this->client()->post('/notifications', $data)->throw();

        return $response->json();
    }

    public function createSubscription(array $data): array
    {
        $payload = $this->sanitizePayload([
            'customer' => $data['customer'],
            'billingType' => $data['billing_type'],
            'value' => $data['value'],
            'nextDueDate' => $data['next_due_date'],
            'description' => $data['description'] ?? null,
            'cycle' => $data['cycle'],
            'maxPayments' => $data['max_payments'] ?? null,
            'endDate' => $data['end_date'] ?? null,
            'externalReference' => $data['external_reference'] ?? null,
            'discount' => $data['discount'] ?? null,
            'fine' => $data['fine'] ?? null,
            'interest' => $data['interest'] ?? null,
        ]);

        $response = $this->client()->post('/subscriptions', $payload)->throw();

        return $response->json();
    }

    public function findSubscription(string $asaasSubscriptionId): ?array
    {
        try {
            $response = $this->client()->get(
                "/subscriptions/{$asaasSubscriptionId}",
            );

            return $response->throw()->json();
        } catch (RequestException $e) {
            if ($e->response->status() === 404) {
                return null;
            }

            throw $e;
        }
    }

    public function cancelSubscription(string $asaasSubscriptionId): array
    {
        $response = $this->client()->delete(
            "/subscriptions/{$asaasSubscriptionId}",
        )->throw();

        return $response->json();
    }

    public function listCustomers(array $filters = []): array
    {
        $response = $this->client()->get('/customers', $filters)->throw();

        return $response->json();
    }

    public function listPayments(array $filters = []): array
    {
        $response = $this->client()->get('/payments', $filters)->throw();

        return $response->json();
    }

    /**
     * Persist an Asaas payment payload as a local GatewayPayment bound to the
     * given invoice and gateway customer. Reuses the existing storage logic so
     * status, fees and invoice transitions stay consistent with outbound sync.
     */
    public function importPayment(array $body, GatewayCustomer $customer, Invoice $invoice): GatewayPayment
    {
        return $this->storeGatewayPayment($body, $invoice, $customer);
    }

    public function paymentMethodFromBillingType(string $billingType): PaymentMethod
    {
        return PaymentMethod::from($this->mapBillingType($billingType));
    }

    public function transactionStatusFromAsaas(string $status): TransactionStatus
    {
        return $this->mapTransactionStatus($status);
    }

    public function listTransfers(array $filters = []): array
    {
        $response = $this->client()->get('/transfers', $filters)->throw();

        return $response->json();
    }

    public function importTransfer(array $body): GatewayTransfer
    {
        $status = $this->mapTransferStatus($body['status'] ?? 'PENDING');
        $grossValue = (float) ($body['value'] ?? 0);
        $netValue = (float) ($body['netValue'] ?? $grossValue);

        return GatewayTransfer::create([
            'gateway_reference_key' => $body['id'],
            'gross_value' => $grossValue,
            'fee_value' => max(0, $grossValue - $netValue),
            'status' => $status,
            'gateway_account_id' => $this->gatewayAccount->id,
        ]);
    }

    private function client(): PendingRequest
    {
        $settings = $this->gatewayAccount->settings ?? [];
        $apiKey = $settings['api_key'] ?? '';
        $baseUrl = $settings['base_url'] ?? 'https://sandbox.asaas.com/api/v3';

        if (blank($apiKey)) {
            throw new RuntimeException('Asaas API key is not configured in gateway account settings.');
        }

        return Http::baseUrl($baseUrl)
            ->withHeader('access_token', $apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10)
            ->retry(3, 100, fn ($e, $request) => $e instanceof ConnectionException)
            ->throw();
    }

    private function buildCustomerPayload(Model $holder): array
    {
        $name = match (true) {
            $holder instanceof Client => $holder->name,
            $holder instanceof Supplier => $holder->name,
            $holder instanceof Trainer => $holder->name,
            default => throw new InvalidArgumentException('Unsupported holder type: '.$holder::class),
        };

        $cpfCnpj = $holder->document ?? '';
        $email = $holder->email ?? '';
        $phone = $holder->phone ?? '';

        $payload = [
            'name' => $name,
            'cpfCnpj' => preg_replace('/\D/', '', $cpfCnpj),
            'email' => $email,
            'phone' => preg_replace('/\D/', '', $phone),
            'externalReference' => (string) $holder->getKey(),
        ];

        if (property_exists($holder, 'address') && filled($holder->address)) {
            $payload['address'] = $holder->address;
        }

        if (property_exists($holder, 'address_number') && filled($holder->address_number)) {
            $payload['addressNumber'] = $holder->address_number;
        }

        if (property_exists($holder, 'address_complement') && filled($holder->address_complement)) {
            $payload['complement'] = $holder->address_complement;
        }

        if (property_exists($holder, 'address_district') && filled($holder->address_district)) {
            $payload['province'] = $holder->address_district;
        }

        if (property_exists($holder, 'address_postal_code') && filled($holder->address_postal_code)) {
            $payload['postalCode'] = preg_replace('/\D/', '', $holder->address_postal_code);
        }

        if (property_exists($holder, 'address_city') && filled($holder->address_city)) {
            $payload['city'] = $holder->address_city;
        }

        if (property_exists($holder, 'address_state') && filled($holder->address_state)) {
            $payload['state'] = $holder->address_state;
        }

        if ($holder instanceof Client && $holder->legal_representative) {
            $payload['foreignCustomer'] = [
                'name' => $holder->legal_representative_name,
                'cpfCnpj' => preg_replace('/\D/', '', $holder->legal_representative_document ?? ''),
                'birthDate' => $holder->legal_representative_birth_date?->format('Y-m-d'),
            ];
        }

        return $payload;
    }

    private function findExistingCustomer(Model $holder): ?GatewayCustomer
    {
        return GatewayCustomer::where('holder_id', $holder->getKey())
            ->where('holder_type', $holder->getMorphClass())
            ->where('gateway_account_id', $this->gatewayAccount->id)
            ->first();
    }

    private function resolveBillingType(?string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'boleto' => 'BOLETO',
            'pix' => 'PIX',
            'credit_card' => 'CREDIT_CARD',
            default => 'UNDEFINED',
        };
    }

    private function buildDiscountPayload(Invoice $invoice): ?array
    {
        if ($invoice->discount_value <= 0) {
            return null;
        }

        return [
            'value' => $invoice->discount_value,
            'dueDateLimitDays' => 0,
            'type' => 'FIXED',
        ];
    }

    private function storeGatewayPayment(array $body, Invoice $invoice, GatewayCustomer $customer): GatewayPayment
    {
        $status = $this->mapTransactionStatus($body['status'] ?? 'PENDING');
        $grossValue = (float) ($body['value'] ?? $invoice->total);
        $feeValue = (float) ($body['netValue'] ?? $grossValue);
        $feeValue = $grossValue - $feeValue;

        /** @var GatewayPayment $payment */
        $payment = GatewayPayment::create([
            'gateway_reference_key' => $body['id'],
            'payment_method' => $this->mapBillingType($body['billingType'] ?? 'UNDEFINED'),
            'payment_date' => isset($body['paymentDate'])
                ? CarbonImmutable::parse($body['paymentDate'])
                : null,
            'status' => $status,
            'gross_value' => $grossValue,
            'fee_value' => max(0, $feeValue),
            'gateway_account_id' => $this->gatewayAccount->id,
            'gateway_customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
        ]);

        if (isset($body['creditCard'])) {
            $this->storeCreditCard($body['creditCard'], $customer);
        }

        if ($invoice->usesGatewayPaymentMethod() && $invoice->status === InvoiceStatus::PENDING) {
            $invoice->update([
                'status' => InvoiceStatus::WAITING,
            ]);
        }

        return $payment;
    }

    private function updateGatewayPaymentFromResponse(GatewayPayment $payment, array $body): void
    {
        $grossValue = (float) ($body['value'] ?? $payment->gross_value);
        $netValue = (float) ($body['netValue'] ?? $grossValue);

        $payment->update([
            'status' => $this->mapTransactionStatus($body['status'] ?? $payment->status->value),
            'payment_method' => $this->mapBillingType($body['billingType'] ?? $payment->payment_method->value),
            'payment_date' => isset($body['paymentDate'])
                ? CarbonImmutable::parse($body['paymentDate'])
                : $payment->payment_date,
            'gross_value' => $grossValue,
            'fee_value' => max(0, $grossValue - $netValue),
        ]);
    }

    private function mapTransactionStatus(string $asaasStatus): TransactionStatus
    {
        return self::ASAAS_STATUS_MAP[$asaasStatus] ?? TransactionStatus::PENDING;
    }

    private function mapTransferStatus(string $asaasStatus): string
    {
        return match ($asaasStatus) {
            'BANK_PROCESSING' => TransactionStatus::PENDING->value,
            'DONE' => TransactionStatus::PAID->value,
            'CANCELED' => TransactionStatus::CANCELED->value,
            'FAILED' => TransactionStatus::FAILED->value,
            default => (self::ASAAS_STATUS_MAP[$asaasStatus] ?? TransactionStatus::PENDING)->value,
        };
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws RequestException
     */
    private function getOrNull(string $path): ?array
    {
        try {
            return $this->client()->get($path)->throw()->json();
        } catch (RequestException $exception) {
            if ($exception->response?->status() === 404) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        return array_filter($payload, static fn (mixed $value): bool => $value !== null);
    }

    private function mapBillingType(string $billingType): string
    {
        return self::BILLING_TYPE_MAP[$billingType] ?? 'boleto';
    }

    private function storeCreditCard(array $cardData, ?GatewayCustomer $customer): void
    {
        $lastDigits = $cardData['creditCardNumber'] ?? $cardData['lastFourDigits'] ?? null;

        if ($lastDigits === null) {
            return;
        }

        GatewayCreditCard::create([
            'gateway_card_token' => $cardData['creditCardToken'] ?? $cardData['creditCardNumber'] ?? null,
            'gateway_reference_key' => $cardData['creditCardNumber'] ?? null,
            'card_brand' => $cardData['creditCardBrand'] ?? $cardData['brand'] ?? null,
            'last_digits' => $lastDigits,
            'gateway_account_id' => $this->gatewayAccount->id,
            'gateway_customer_id' => $customer?->id,
        ]);
    }

    private function resolvePostbackType(string $event): string
    {
        if (str_starts_with($event, 'INVOICE')) {
            return 'invoice';
        }

        if (str_starts_with($event, 'PAYMENT')) {
            return 'payment';
        }

        if (str_starts_with($event, 'SUBSCRIPTION')) {
            return 'subscription';
        }

        if (str_starts_with($event, 'TRANSFER')) {
            return 'transfer';
        }

        if (str_starts_with($event, 'CUSTOMER')) {
            return 'customer';
        }

        return 'unknown';
    }

    private function mapInvoiceStatus(string $status): \App\Enums\Gateway\InvoiceStatus
    {
        return match (strtoupper($status)) {
            'AUTHORIZED', 'ISSUED' => \App\Enums\Gateway\InvoiceStatus::AUTHORIZED,
            'SYNCHRONIZED' => \App\Enums\Gateway\InvoiceStatus::SYNCHRONIZED,
            'CANCELED', 'CANCELLED' => \App\Enums\Gateway\InvoiceStatus::CANCELED,
            'CANCELLATION_PROCESSING', 'PROCESSING_CANCELLATION' => \App\Enums\Gateway\InvoiceStatus::PROCESSING_CANCELLATION,
            'CANCELLATION_DENIED' => \App\Enums\Gateway\InvoiceStatus::CANCELLATION_DENIED,
            'PROCESSING' => \App\Enums\Gateway\InvoiceStatus::PROCESSING,
            'SCHEDULED' => \App\Enums\Gateway\InvoiceStatus::SCHEDULED,
            'ERROR', 'FAILED' => \App\Enums\Gateway\InvoiceStatus::ERROR,
            'PENDING' => \App\Enums\Gateway\InvoiceStatus::PENDING,
            default => \App\Enums\Gateway\InvoiceStatus::UNKNOWN,
        };
    }

    private function updateGatewayInvoice(GatewayInvoice $invoice, array $body): GatewayInvoice
    {
        $invoice->fill($this->invoiceAttributes($body));
        $invoice->save();

        return $invoice->fresh();
    }

    private function handlePostbackEvent(string $event, array $payload, GatewayPostback $postback): void
    {
        if (str_starts_with($event, 'PAYMENT')) {
            $this->handlePaymentPostback($payload, $postback);

            return;
        }

        if (str_starts_with($event, 'INVOICE')) {
            $this->handleInvoicePostback($payload);
        }

        if (str_starts_with($event, 'TRANSFER')) {
            $this->handleTransferPostback($payload, $postback);

            return;
        }
    }

    private function handleInvoicePostback(array $payload): void
    {
        $invoiceData = $payload['invoice'] ?? $payload;
        $reference = $invoiceData['id'] ?? null;

        if (! is_string($reference)) {
            return;
        }

        $paymentReference = $invoiceData['payment'] ?? $invoiceData['paymentId'] ?? null;
        $payment = is_string($paymentReference)
            ? GatewayPayment::query()
                ->where('gateway_account_id', $this->gatewayAccount->id)
                ->where('gateway_reference_key', $paymentReference)
                ->first()
            : null;

        if ($payment === null && isset($invoiceData['externalReference'])) {
            $payment = GatewayPayment::query()
                ->where('gateway_account_id', $this->gatewayAccount->id)
                ->where('invoice_id', (int) $invoiceData['externalReference'])
                ->latest('id')
                ->first();
        }

        $invoice = GatewayInvoice::query()
            ->where('gateway_account_id', $this->gatewayAccount->id)
            ->where('gateway_reference_key', $reference)
            ->first();

        if ($invoice === null && $payment !== null) {
            $invoice = GatewayInvoice::query()
                ->where('gateway_account_id', $this->gatewayAccount->id)
                ->where('gateway_payment_id', $payment->id)
                ->first();

            if ($invoice === null) {
                $invoice = new GatewayInvoice([
                    'gateway_account_id' => $this->gatewayAccount->id,
                    'gateway_payment_id' => $payment->id,
                    'invoice_id' => $payment->invoice_id,
                ]);
            }
        }

        if ($invoice === null) {
            return;
        }

        $invoice->fill($this->invoiceAttributes($invoiceData));
        $invoice->save();
    }

    private function handlePaymentPostback(array $payload, GatewayPostback $postback): void
    {
        $paymentData = $payload['payment'] ?? $payload;

        $payment = GatewayPayment::where(
            'gateway_reference_key',
            $paymentData['id'],
        )->first();

        if ($payment === null) {
            return;
        }

        $this->updateGatewayPaymentFromResponse($payment, $paymentData);
    }

    private function handleTransferPostback(array $payload, GatewayPostback $postback): void
    {
        $transferData = $payload['transfer'] ?? $payload;

        $transfer = GatewayTransfer::where(
            'gateway_reference_key',
            $transferData['id'],
        )->first();

        if ($transfer === null) {
            return;
        }

        $transfer->update([
            'status' => $this->mapTransferStatus($transferData['status'] ?? 'PENDING'),
        ]);
    }
}
