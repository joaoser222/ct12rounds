<?php

namespace App\Mail;

use App\DTOs\Emails\EmailMessageData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        private readonly EmailMessageData $message,
        private readonly string $bodyHtml,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->message->subject,
            from: $this->message->from,
            replyTo: $this->message->replyTo !== null ? [$this->message->replyTo] : [],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->bodyHtml,
        );
    }

    /**
     * @return list<Attachment>
     */
    public function attachments(): array
    {
        return array_map(
            fn (string $path): Attachment => Attachment::fromPath($path),
            $this->message->attachments,
        );
    }
}
