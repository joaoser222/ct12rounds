<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Emails\EmailMessageData;
use App\Mail\TemplateEmail;
use App\Services\Email\Contracts\EmailSenderInterface;
use App\Services\Email\EmailService;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    public function test_sends_message_through_the_configured_transport(): void
    {
        Mail::fake();

        $sent = $this->service()->send($this->message());

        $this->assertTrue($sent);

        Mail::assertSent(TemplateEmail::class, function (TemplateEmail $email): bool {
            return $email->hasTo('cliente@example.com')
                && $email->hasSubject('Contrato disponível')
                && $email->hasCc('financeiro@example.com')
                && $email->hasBcc('arquivo@example.com')
                && $email->hasReplyTo('recepcao@example.com')
                && $email->assertSeeInHtml('Contrato disponível para assinatura')
                && $email->assertSeeInHtml('https://ct12rounds.test/registro/token-123');
        });
    }

    public function test_sends_message_with_attachments(): void
    {
        Mail::fake();

        $path = tempnam(sys_get_temp_dir(), 'email-').'.pdf';
        file_put_contents($path, '%PDF-1.4');

        $message = new EmailMessageData(
            to: 'cliente@example.com',
            subject: 'Contrato disponível',
            template: 'templates/email/message.blade.php',
            data: ['title' => 'Contrato', 'body' => 'Segue o contrato.'],
            attachments: [$path],
        );

        $this->assertTrue($this->service()->send($message));

        Mail::assertSent(TemplateEmail::class, function (TemplateEmail $email) use ($path): bool {
            $email->render();

            return $email->hasAttachment($path);
        });

        unlink($path);
    }

    public function test_queues_message_instead_of_sending_immediately(): void
    {
        Mail::fake();

        $this->assertTrue($this->service()->queue($this->message()));

        Mail::assertQueued(TemplateEmail::class, fn (TemplateEmail $email): bool => $email->hasTo('cliente@example.com'));
        Mail::assertNothingSent();
    }

    public function test_returns_false_and_logs_when_transport_fails(): void
    {
        $mailer = Mockery::mock(MailFactory::class);
        $mailer->shouldReceive('to')->once()->andThrow(new RuntimeException('smtp indisponível'));

        Log::spy();

        $this->assertFalse((new EmailService($mailer))->send($this->message()));

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message, array $context): bool => $message === 'Falha ao enviar e-mail.'
                && ($context['to'] ?? null) === 'cliente@example.com'
                && ($context['template'] ?? null) === 'templates/email/message.blade.php');
    }

    public function test_returns_false_when_queueing_fails(): void
    {
        $mailer = Mockery::mock(MailFactory::class);
        $mailer->shouldReceive('to')->once()->andThrow(new RuntimeException('fila indisponível'));

        $this->assertFalse((new EmailService($mailer))->queue($this->message()));
    }

    public function test_fails_when_template_does_not_exist(): void
    {
        Mail::fake();

        $this->expectException(RuntimeException::class);

        $this->service()->send(new EmailMessageData(
            to: 'cliente@example.com',
            subject: 'Contrato disponível',
            template: 'templates/email/inexistente.blade.php',
        ));
    }

    public function test_service_is_bound_to_the_sender_contract(): void
    {
        $this->assertInstanceOf(EmailService::class, $this->app->make(EmailSenderInterface::class));
    }

    private function service(): EmailService
    {
        return new EmailService($this->app->make(MailFactory::class));
    }

    private function message(): EmailMessageData
    {
        return new EmailMessageData(
            to: 'cliente@example.com',
            subject: 'Contrato disponível',
            template: 'templates/email/message.blade.php',
            data: [
                'title' => 'Contrato disponível para assinatura',
                'body' => 'Acesse o link para concluir sua contratação.',
                'actionUrl' => 'https://ct12rounds.test/registro/token-123',
                'actionText' => 'Visualizar contrato',
            ],
            replyTo: 'recepcao@example.com',
            cc: ['financeiro@example.com'],
            bcc: ['arquivo@example.com'],
        );
    }
}
