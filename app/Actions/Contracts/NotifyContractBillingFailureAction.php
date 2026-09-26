<?php

namespace App\Actions\Contracts;

use App\Actions\BaseAction;
use App\DTOs\Contracts\ActionResultDTO;
use App\DTOs\Emails\BillingFailureData;
use App\Services\Email\Contracts\EmailSenderInterface;
use InvalidArgumentException;

/**
 * Notifies the company by e-mail when the public registration cannot be billed,
 * either because the credit card was declined or because the invoices could not
 * be issued, so the pending contract can be finished manually.
 *
 * The contract itself is left untouched: it stays pending until the terms are
 * accepted, which is the responsibility of ApplyContractAction.
 */
class NotifyContractBillingFailureAction extends BaseAction
{
    /** Module access is enforced by the HTTP controller's permission check. */
    protected string $ability = '';

    protected string $modelClass = '';

    public function __construct(
        private readonly EmailSenderInterface $emailSender,
    ) {}

    protected function handle(mixed $input): ActionResultDTO
    {
        if (! $input instanceof BillingFailureData) {
            throw new InvalidArgumentException('NotifyContractBillingFailureAction requires a BillingFailureData.');
        }

        $data = $input;

        if ($data->recipient === '') {
            return ActionResultDTO::success(
                ['sent' => false, 'reason' => 'no_recipient_configured'],
                'Nenhum destinatário configurado para o aviso de cobrança.',
            );
        }

        $sent = $data->queue
            ? $this->emailSender->queue($data->toMessage())
            : $this->emailSender->send($data->toMessage());

        return ActionResultDTO::success(
            ['sent' => $sent, 'to' => $data->recipient],
            $sent ? 'Aviso de falha de cobrança enviado.' : 'Falha ao enviar o aviso de cobrança.',
        );
    }
}
