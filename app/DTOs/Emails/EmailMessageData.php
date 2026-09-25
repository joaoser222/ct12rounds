<?php

namespace App\DTOs\Emails;

final class EmailMessageData
{
    /**
     * @param  string  $template  Blade template path relative to resources/, e.g. templates/email/message.blade.php.
     * @param  array<string, mixed>  $data  Variables available to the Blade template.
     * @param  list<string>  $cc
     * @param  list<string>  $bcc
     * @param  list<string>  $attachments  Absolute file paths.
     */
    public function __construct(
        public readonly string $to,
        public readonly string $subject,
        public readonly string $template,
        public readonly array $data = [],
        public readonly ?string $from = null,
        public readonly ?string $replyTo = null,
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly array $attachments = [],
    ) {}
}
