<?php

namespace App\Services\Gateway;

use App\Contracts\BillingInvoiceSource;
use App\Actions\Contracts\RevertContractAcceptanceAction;
use App\Enums\InvoiceStatus;
use App\Enums\OperationType;
use App\Enums\PaymentMethod;
use App\Models\Contract;
use App\Models\Invoice;
use App\PaymentGateways\Contracts\PaymentGatewayAdapter;
use App\Repositories\Contracts\GatewayPaymentRepositoryInterface;
use App\Services\Billing\InvoiceGenerator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class GatewayBillingOrchestrator
{
    public function __construct(
        private readonly InvoiceGenerator $invoiceGenerator,
        private readonly PaymentGatewayAdapter $gateway,
        private readonly GatewayPaymentRepositoryInterface $gatewayPaymentRepository,
        private readonly RevertContractAcceptanceAction $revertContract,
    ) {}

    /**
     * Generate invoices and attempt to sync with gateway.
     *
     * @return Collection<int, Invoice>
     */
    public function generateAndSync(BillingInvoiceSource&Model $source): Collection
    {
        $invoices = $this->invoiceGenerator->generate($source);

        try {
            $this->syncInvoices($invoices);
        } catch (\Throwable $e) {
            report($e);
        }

        return $invoices;
    }

    /**
     * Sync pending invoices with gateway.
     */
    public function syncPendingInvoices(): int
    {
        $synced = 0;

        foreach ($this->invoicesEligibleForSync() as $invoice) {
            if ($this->syncInvoice($invoice)) {
                $synced++;
            }
        }

        return $synced;
    }

    /**
     * @return Collection<int, Invoice>
     */
    private function invoicesEligibleForSync(): Collection
    {
        return Invoice::query()
            ->where('operation_type', OperationType::RECEIVABLE)
            ->where(function ($query) {
                $query->where('payment_method', PaymentMethod::BOLETO)
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereIn('payment_method', [
                            PaymentMethod::PIX,
                            PaymentMethod::CREDIT_CARD,
                        ])->whereDate('due_date', now()->toDateString());
                    });
            })
            ->where('status', InvoiceStatus::PENDING)
            ->whereDoesntHave('gatewayPayment')
            ->get();
    }

    public function syncInvoice(Invoice $invoice): bool
    {
        $source = $invoice->billable;

        if ($invoice->holder === null) {
            return false;
        }

        if ($invoice->operation_type !== OperationType::RECEIVABLE) {
            return false;
        }

        if (! $invoice->usesGatewayPaymentMethod()) {
            return false;
        }

        if (! $invoice->shouldGenerateGatewayTransaction()) {
            return false;
        }

        if ($this->gatewayPaymentRepository->existsWhere(['invoice_id' => $invoice->id])) {
            return false;
        }

        if ($this->contractIsNotAccepted($source)) {
            return false;
        }

        $customer = $this->gateway->createCustomer(
            $source instanceof BillingInvoiceSource && $source instanceof Model
                ? $source->billingHolder()
                : $invoice->holder,
        );

        try {
            $this->gateway->createPayment($invoice, $customer, [
                'description' => $this->buildDescription($source, $invoice),
            ]);
        } catch (\Throwable $e) {
            $this->revertContractAcceptance($invoice, $e);

            throw $e;
        }

        $invoice->update([
            'status' => InvoiceStatus::WAITING,
        ]);

        return true;
    }

    /**
     * A contract whose acceptance was reverted must never be charged, otherwise a
     * queued sync would collect a payment the customer refused.
     */
    private function contractIsNotAccepted(mixed $source): bool
    {
        return $source instanceof Contract && $source->accepted_terms !== 'accepted';
    }

    /**
     * A gateway refusal leaves the contract accepted without any payment, so the
     * acceptance is reverted and the company is notified. Failures while reverting
     * never mask the original gateway error.
     */
    private function revertContractAcceptance(Invoice $invoice, \Throwable $gatewayError): void
    {
        try {
            $this->revertContract->execute($invoice);
        } catch (\Throwable $e) {
            report($e);
        }

        report($gatewayError);
    }

    private function buildDescription(?Model $source, Invoice $invoice): string
    {
        if (! $source instanceof BillingInvoiceSource) {
            return "Fatura #{$invoice->getKey()}";
        }

        $className = class_basename($source::class);

        return match ($className) {
            'Contract' => "Contrato #{$source->getKey()}",
            'DirectLesson' => "Aula avulsa #{$source->getKey()}",
            'Sale' => "Venda #{$source->getKey()}",
            'Purchase' => "Compra #{$source->getKey()}",
            default => "Fatura #{$source->getKey()}",
        };
    }
}
