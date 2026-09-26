<?php

namespace App\DTOs\Emails;

use App\Models\Client;
use App\Models\Contract;

/**
 * Payload for the e-mail sent to the company when a public registration cannot
 * be billed, either by card decline or by invoice issuance failure.
 */
final class BillingFailureData
{
    public const REASON_CARD_TOKENIZATION = 'card_tokenization';

    public const REASON_INVOICE_ISSUANCE = 'invoice_issuance';

    private function __construct(
        public readonly string $recipient,
        public readonly string $reason,
        public readonly int $contractId,
        public readonly ?string $planName,
        public readonly ?string $clientName,
        public readonly ?string $clientDocument,
        public readonly ?string $clientEmail,
        public readonly ?string $total,
        public readonly ?string $detail,
        public readonly ?string $actionUrl,
        public readonly bool $queue,
    ) {}

    public static function fromFailure(
        string $recipient,
        string $reason,
        Contract $contract,
        ?Client $client = null,
        ?string $detail = null,
        ?string $actionUrl = null,
        bool $queue = true,
    ): self {
        $contract->loadMissing('plan');

        return new self(
            recipient: $recipient,
            reason: $reason,
            contractId: (int) $contract->getKey(),
            planName: $contract->plan?->name,
            clientName: $client?->name,
            clientDocument: $client?->document,
            clientEmail: $client?->email,
            total: $contract->total !== null
                ? 'R$ '.number_format((float) $contract->total, 2, ',', '.')
                : null,
            detail: $detail,
            actionUrl: $actionUrl,
            queue: $queue,
        );
    }

    public function toMessage(): EmailMessageData
    {
        return new EmailMessageData(
            to: $this->recipient,
            subject: 'Falha na cobrança do contrato #'.$this->contractId,
            template: 'templates/email/billing_failure.blade.php',
            data: [
                'title' => 'Não foi possível concluir a cobrança',
                'reason' => $this->reason,
                'contractId' => $this->contractId,
                'planName' => $this->planName,
                'clientName' => $this->clientName,
                'clientDocument' => $this->clientDocument,
                'clientEmail' => $this->clientEmail,
                'total' => $this->total,
                'detail' => $this->detail,
                'actionUrl' => $this->actionUrl,
            ],
        );
    }
}
