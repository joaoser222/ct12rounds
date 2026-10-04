<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Services\Help\HelpService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Help chat. Sends the question plus the matching guides and a screen menu, and
 * returns prose with links. There is no tool-calling loop: no `tools` key is
 * ever sent, so a provider that answers with a tool call gets nothing to call.
 */
class ChatService
{
    public function __construct(
        private readonly HelpService $help,
    ) {}

    /**
     * Stream the assistant reply as Server-Sent Events. The callback echoes
     * `meta`, `token`, and `done` events; $onComplete receives the final text
     * so the caller can persist the assistant message. When the client
     * disconnects (or $shouldInterrupt returns true), the upstream LLM stream
     * is abandoned so no further tokens are generated, and only the partial
     * text received so far is reported to $onComplete.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function streamAsk(
        string $message,
        array $history = [],
        ?callable $onComplete = null,
        ?int $conversationId = null,
        ?string $promptName = null,
        ?callable $shouldInterrupt = null,
    ): StreamedResponse {
        $config = config('mcp_chat');
        $providers = $this->orderedProviders($config);
        $messages = $this->buildMessages($history, $message, $promptName);

        return response()->stream(function () use ($providers, $config, $messages, $onComplete, $conversationId, $shouldInterrupt): void {
            $isStopped = $shouldInterrupt ?? static fn (): bool => connection_aborted() !== 0;

            if ($conversationId !== null) {
                echo $this->sseEvent(['type' => 'meta', 'conversation_id' => $conversationId]);
                flush();
            }

            if ($isStopped()) {
                return;
            }

            $body = null;

            foreach ($providers as $provider) {
                $body = $this->streamProvider($provider, $config, $messages);

                if ($body !== null) {
                    break;
                }
            }

            if ($body === null) {
                $failure = 'Falha ao chamar os provedores de LLM.';
                echo $this->sseEvent(['type' => 'done', 'content' => $failure]);
                flush();

                if ($onComplete !== null) {
                    ($onComplete)($failure);
                }

                return;
            }

            $content = '';

            $this->streamOneCompletion($body, function (string $token) use (&$content): void {
                $content .= $token;
                echo $this->sseEvent(['type' => 'token', 'content' => $token]);
                flush();
            }, $isStopped);

            $full = trim($content);

            if ($isStopped()) {
                $this->finishInterrupted($full, $onComplete);

                return;
            }

            if ($full === '') {
                $full = 'Não foi possível concluir a resposta a tempo. Tente novamente.';
            }

            echo $this->sseEvent(['type' => 'done', 'content' => $full]);
            flush();

            if ($onComplete !== null) {
                ($onComplete)($full);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Send a message and return the assistant text.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function ask(string $message, array $history = [], ?string $promptName = null): string
    {
        $config = config('mcp_chat');
        $providers = $this->orderedProviders($config);
        $messages = $this->buildMessages($history, $message, $promptName);

        $response = $this->completeChat($providers, $config, $messages);

        $content = (string) ($response->json()['choices'][0]['message']['content'] ?? '');
        $content = trim($content);

        return $content !== ''
            ? $content
            : 'Não foi possível concluir a resposta a tempo. Tente novamente.';
    }

    /**
     * Suggested questions shown as chips in the chat UI. These are prompts for
     * the user, not hidden instructions injected into the model context.
     *
     * @return array<int, array{name: string, label: string, question: string}>
     */
    public function suggestions(): array
    {
        return [
            ['name' => 'vendas-vencidas', 'label' => 'Contas vencidas', 'question' => 'Quais clientes têm contas a receber vencidas?'],
            ['name' => 'baixa-parcela', 'label' => 'Registrar pagamento', 'question' => 'Como registro o pagamento de uma parcela?'],
            ['name' => 'novo-cliente', 'label' => 'Cadastrar cliente', 'question' => 'Como cadastro um novo cliente?'],
            ['name' => 'nova-turma', 'label' => 'Criar turma', 'question' => 'Como crio uma turma e monto o horário?'],
            ['name' => 'nota-fiscal', 'label' => 'Emitir nota fiscal', 'question' => 'Como emito nota fiscal de uma fatura paga?'],
            ['name' => 'sync-gateway', 'label' => 'Sincronizar gateway', 'question' => 'Como sincronizo os dados do gateway?'],
            ['name' => 'permissao', 'label' => 'Liberar acesso', 'question' => 'Como dou permissão de acesso a um usuário?'],
        ];
    }

    /**
     * Try each provider in order and return the first successful completion.
     *
     * @param  array<int, array{base_url: string, api_key: string, model: string}>  $providers
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function completeChat(array $providers, array $config, array $messages): Response
    {
        $lastError = null;

        foreach ($providers as $provider) {
            $payload = [
                'model' => $provider['model'],
                'messages' => $messages,
                'temperature' => (float) $config['temperature'],
                'max_tokens' => (int) $config['max_tokens'],
            ];

            if (is_array($config['chat_template_kwargs'] ?? null) && $config['chat_template_kwargs'] !== []) {
                $payload['chat_template_kwargs'] = $config['chat_template_kwargs'];
            }

            $response = $this->postWithRetry($provider, $config, $payload, false);

            if ($response->successful()) {
                return $response;
            }

            $lastError = $response->body();
        }

        throw new \RuntimeException('Falha ao chamar os provedores de LLM: '.$lastError);
    }

    /**
     * POST to the provider, retrying with backoff while it answers with HTTP 429
     * (rate limit). The Groq "try again in Xs" hint is honored when present.
     */
    private function postWithRetry(array $provider, array $config, array $payload, bool $stream): Response
    {
        $maxAttempts = (int) ($config['retry_attempts'] ?? 3);
        $delaySeconds = (float) ($config['retry_base_delay'] ?? 2);

        $response = $this->post($provider, $config, $payload, $stream);

        for ($attempt = 1; $attempt < $maxAttempts && $response->status() === 429; $attempt++) {
            $wait = $this->retryDelaySeconds($response, $delaySeconds);
            $delaySeconds = min($delaySeconds * 2, 15);
            usleep((int) ($wait * 1000000));

            $response = $this->post($provider, $config, $payload, $stream);
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $provider
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $payload
     */
    private function post(array $provider, array $config, array $payload, bool $stream): Response
    {
        return Http::withToken((string) $provider['api_key'])
            ->withOptions(['stream' => $stream])
            ->timeout((int) $config['request_timeout'])
            ->connectTimeout((int) ($config['connect_timeout'] ?? 10))
            ->post((string) $provider['base_url'], $payload);
    }

    /**
     * Parse the Groq "try again in Xs" hint, falling back to the provided delay.
     */
    private function retryDelaySeconds(Response $response, float $fallback): float
    {
        $message = (string) (json_decode($response->body(), true)['error']['message'] ?? '');

        if (preg_match('/try again in ([0-9]+(?:\.[0-9]+)?)s/', $message, $matches) === 1) {
            return (float) $matches[1] + 0.5;
        }

        return $fallback;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array{base_url: string, api_key: string, model: string}>
     */
    private function orderedProviders(array $config): array
    {
        $baseUrl = (string) ($config['base_url'] ?? '');
        $apiKey = (string) ($config['api_key'] ?? '');
        $models = $config['providers'] ?? [];

        if ($models === [] && isset($config['model'])) {
            $models = [$config['model']];
        }

        return collect($models)
            ->filter(fn ($model): bool => is_string($model) && $model !== '')
            ->map(fn (string $model): array => [
                'base_url' => $baseUrl,
                'api_key' => $apiKey,
                'model' => $model,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array<string, mixed>>
     */
    private function buildMessages(array $history, string $message, ?string $promptName = null): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->help->systemPrompt()],
        ];

        foreach ($history as $entry) {
            $messages[] = [
                'role' => $entry['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $entry['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $this->questionWithGuides($message, $promptName),
        ];

        return $messages;
    }

    private function questionWithGuides(string $message, ?string $promptName = null): string
    {
        if ($promptName !== null) {
            foreach ($this->suggestions() as $suggestion) {
                if ($suggestion['name'] === $promptName) {
                    $message = $suggestion['question'];

                    break;
                }
            }
        }

        $guides = $this->help->contextFor($message);

        return $guides === ''
            ? $message
            : $message."\n\n[Material interno de apoio]\n\n".$guides;
    }

    /**
     * Open a streaming completion against one provider and return its raw body
     * stream, or null when the provider fails.
     *
     * @param  array{base_url: string, api_key: string, model: string}  $provider
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function streamProvider(array $provider, array $config, array $messages): ?StreamInterface
    {
        $payload = [
            'model' => $provider['model'],
            'messages' => $messages,
            'temperature' => (float) $config['temperature'],
            'max_tokens' => (int) $config['max_tokens'],
            'stream' => true,
        ];

        if (is_array($config['chat_template_kwargs'] ?? null) && $config['chat_template_kwargs'] !== []) {
            $payload['chat_template_kwargs'] = $config['chat_template_kwargs'];
        }

        $response = $this->postWithRetry($provider, $config, $payload, true);

        if (! $response->successful()) {
            return null;
        }

        return $response->toPsrResponse()->getBody();
    }

    /**
     * Report the partial text received before an interruption so the caller can
     * persist it; no further LLM calls happen after this point.
     */
    private function finishInterrupted(string $partial, ?callable $onComplete): void
    {
        if ($partial !== '' && $onComplete !== null) {
            ($onComplete)($partial);
        }
    }

    /**
     * Read an SSE stream, invoking $onToken for each text delta. When
     * $shouldStop returns true, the loop stops reading so the upstream connection
     * is closed.
     */
    private function streamOneCompletion(StreamInterface $body, callable $onToken, ?callable $shouldStop = null): void
    {
        $buffer = '';

        while (! $body->eof()) {
            if ($shouldStop !== null && $shouldStop()) {
                break;
            }

            $buffer .= $body->read(8192);

            while (($pos = strpos($buffer, "\n\n")) !== false) {
                $eventBlock = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 2);

                $data = '';
                foreach (explode("\n", $eventBlock) as $line) {
                    if (str_starts_with($line, 'data:')) {
                        $data .= trim(substr($line, 5));
                    }
                }

                if ($data === '' || $data === '[DONE]') {
                    continue;
                }

                $json = json_decode($data, true);

                if (! is_array($json)) {
                    continue;
                }

                $content = $json['choices'][0]['delta']['content'] ?? null;

                if (is_string($content) && $content !== '') {
                    $onToken($content);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sseEvent(array $payload): string
    {
        return 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
    }
}