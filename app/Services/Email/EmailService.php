<?php

namespace App\Services\Email;

use App\DTOs\Emails\EmailMessageData;
use App\Mail\TemplateEmail;
use App\Services\Email\Contracts\EmailSenderInterface;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Mail\PendingMail;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Throwable;

/**
 * Sends templated e-mail messages through the configured mail transport.
 */
class EmailService implements EmailSenderInterface
{
    public function __construct(private readonly MailFactory $mailer) {}

    public function send(EmailMessageData $message): bool
    {
        $mailable = $this->mailable($message);

        return $this->deliver($message, fn (PendingMail $mail) => $mail->send($mailable));
    }

    public function queue(EmailMessageData $message): bool
    {
        $mailable = $this->mailable($message);

        return $this->deliver($message, fn (PendingMail $mail) => $mail->queue($mailable));
    }

    private function mailable(EmailMessageData $message): TemplateEmail
    {
        $templatePath = resource_path($message->template);

        if (! File::exists($templatePath)) {
            throw new RuntimeException("Email template [{$message->template}] was not found.");
        }

        $html = View::file($templatePath, [
            'subject' => $message->subject,
            ...$message->data,
        ])->render();

        return new TemplateEmail($message, $html);
    }

    /**
     * @param  callable(PendingMail): mixed  $deliver
     */
    private function deliver(EmailMessageData $message, callable $deliver): bool
    {
        try {
            $deliver(
                $this->mailer
                    ->to($message->to)
                    ->cc($message->cc)
                    ->bcc($message->bcc),
            );

            return true;
        } catch (Throwable $e) {
            Log::error('Falha ao enviar e-mail.', [
                'to' => $message->to,
                'subject' => $message->subject,
                'template' => $message->template,
                'exception' => $e,
            ]);

            return false;
        }
    }
}
