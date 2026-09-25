<?php

namespace App\Services\Email\Contracts;

use App\DTOs\Emails\EmailMessageData;

interface EmailSenderInterface
{
    /**
     * Sends the message immediately, returning false when the transport fails.
     */
    public function send(EmailMessageData $message): bool;

    /**
     * Hands the message over to the mail queue, returning false when it cannot be queued.
     */
    public function queue(EmailMessageData $message): bool;
}
