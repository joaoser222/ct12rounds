<?php

namespace App\PaymentGateways\Contracts;

use App\Models\GatewayCustomer;
use App\Models\GatewayPayment;
use App\Models\GatewayPostback;
use App\Models\GatewayTransfer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;

interface PaymentGatewayAdapter
{
    public function createCustomer(Model $holder): GatewayCustomer;

    public function findCustomer(GatewayCustomer $customer): ?array;

    public function syncCustomer(GatewayCustomer $customer): bool;

    /**
     * @param  array<string, string>  $data
     */
    public function syncCustomerData(GatewayCustomer $customer, array $data): bool;

    public function createPayment(Invoice $invoice, GatewayCustomer $customer, array $options = []): GatewayPayment;

    public function findPayment(GatewayPayment $payment): ?array;

    public function payWithCreditCard(GatewayPayment $payment, array $creditCardData): GatewayPayment;

    public function refundPayment(GatewayPayment $payment, ?int $value = null): GatewayPayment;

    public function getPixQrCode(GatewayPayment $payment): ?array;

    public function tokenizeCreditCard(array $cardData): ?array;

    public function createTransfer(array $data): GatewayTransfer;

    public function findTransfer(GatewayTransfer $transfer): ?array;

    public function processPostback(array $payload): GatewayPostback;

    public function getBalance(): ?array;
}
