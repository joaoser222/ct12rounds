<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response as McpResponse;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Server\Tool;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ChatService
{
    public function __construct(
        private readonly ChatToolSchemaProvider $schemaProvider,
        private readonly ChatPromptProvider $promptProvider,
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
        ['tools' => $resourceTools, 'map' => $resourceMap] = $this->schemaProvider->readOnlyResourcesForCurrentUser();
        ['tools' => $writeTools, 'map' => $writeMap] = $this->schemaProvider->writableToolsForCurrentUser();

        $tools = array_merge($resourceTools, $writeTools);
        $map = $resourceMap + $writeMap;

        $promptText = $promptName !== null ? $this->promptProvider->promptTextByName($promptName) : null;
        $messages = $this->buildInitialMessages($history, $message, $promptText);

        $config = config('mcp_chat');
        $maxIterations = (int) $config['max_tool_iterations'];
        $providers = $this->orderedProviders($config);

        return response()->stream(function () use ($providers, $config, $tools, $messages, $map, $maxIterations, $onComplete, $conversationId, $shouldInterrupt): void {
            $isStopped = $shouldInterrupt ?? static fn (): bool => connection_aborted() !== 0;

            if ($conversationId !== null) {
                echo $this->sseEvent(['type' => 'meta', 'conversation_id' => $conversationId]);
                flush();
            }

            if ($isStopped()) {
                return;
            }

            $activeIndex = 0;
            $full = '';

            for ($iteration = 0; $iteration <= $maxIterations; $iteration++) {
                if ($isStopped()) {
                    $this->finishInterrupted($full, $onComplete);

                    return;
                }

                $body = null;

                for ($i = $activeIndex; $i < count($providers); $i++) {
                    $body = $this->streamProvider($providers[$i], $config, $tools, $messages);

                    if ($body !== null) {
                        $activeIndex = $i;
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

                $pending = '';
                $result = $this->streamOneCompletion($body, function (string $token) use (&$pending): void {
                    $pending .= $token;
                    echo $this->sseEvent(['type' => 'token', 'content' => $token]);
                    flush();
                }, $isStopped);

                if ($isStopped()) {
                    $this->finishInterrupted($pending !== '' ? $pending : $full, $onComplete);

                    return;
                }

                $assistantMessage = ['role' => 'assistant', 'content' => $result['content']];

                if ($result['tool_calls'] !== []) {
                    $assistantMessage['tool_calls'] = array_values($result['tool_calls']);
                }

                $messages[] = $assistantMessage;
                $full = $result['content'];

                if ($result['tool_calls'] === []) {
                    break;
                }

                foreach ($result['tool_calls'] as $toolCall) {
                    $function = $toolCall['function'] ?? [];
                    $name = (string) ($function['name'] ?? '');
                    $arguments = json_decode((string) ($function['arguments'] ?? '{}'), true) ?? [];

                    $resultText = $this->executeToolCall($name, $arguments, $map);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => (string) ($toolCall['id'] ?? ''),
                        'content' => $resultText,
                    ];
                }
            }

            if ($isStopped()) {
                $this->finishInterrupted($full, $onComplete);

                return;
            }

            if ($full === '') {
                $full = $this->synthesizeFinalAnswer($messages, $providers, $activeIndex, $config);
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
     * Send a message to the LLM, executing MCP resource reads when the model
     * requests them, and return the final assistant text.
     *
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function ask(string $message, array $history = [], ?string $promptName = null): string
    {
        ['tools' => $resourceTools, 'map' => $resourceMap] = $this->schemaProvider->readOnlyResourcesForCurrentUser();
        ['tools' => $writeTools, 'map' => $writeMap] = $this->schemaProvider->writableToolsForCurrentUser();

        $tools = array_merge($resourceTools, $writeTools);
        $map = $resourceMap + $writeMap;

        $promptText = $promptName !== null ? $this->promptProvider->promptTextByName($promptName) : null;
        $messages = $this->buildInitialMessages($history, $message, $promptText);

        $config = config('mcp_chat');
        $maxIterations = (int) $config['max_tool_iterations'];
        $providers = $this->orderedProviders($config);
        $activeIndex = 0;

        for ($iteration = 0; $iteration <= $maxIterations; $iteration++) {
            $response = $this->completeChat($providers, $activeIndex, $config, $tools, $messages);

            $data = $response->json();
            $assistantMessage = $data['choices'][0]['message'] ?? null;

            if ($assistantMessage === null) {
                throw new \RuntimeException('Resposta inesperada do provedor de LLM.');
            }

            $messages[] = $assistantMessage;

            $toolCalls = $assistantMessage['tool_calls'] ?? [];

            if ($toolCalls !== []) {
                foreach ($toolCalls as $toolCall) {
                    $function = $toolCall['function'] ?? [];
                    $name = (string) ($function['name'] ?? '');
                    $arguments = json_decode((string) ($function['arguments'] ?? '{}'), true) ?? [];

                    $resultText = $this->executeToolCall($name, $arguments, $map);

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => (string) ($toolCall['id'] ?? ''),
                        'content' => $resultText,
                    ];
                }

                continue;
            }

            $content = (string) ($assistantMessage['content'] ?? '');

            if ($content !== '') {
                return $content;
            }
        }

        $full = $this->synthesizeFinalAnswer($messages, $providers, $activeIndex, $config);

        return $full !== ''
            ? $full
            : 'Não foi possível concluir a resposta a tempo. Tente novamente.';
    }

    /**
     * Try each provider starting at $activeIndex and return the first
     * successful completion, keeping the chosen provider for later calls.
     *
     * @param  array<int, array{base_url: string, api_key: string, model: string}>  $providers
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function completeChat(array $providers, int &$activeIndex, array $config, array $tools, array $messages): Response
    {
        $lastError = null;

        for ($i = $activeIndex; $i < count($providers); $i++) {
            $response = $this->callProvider($providers[$i], $config, $tools, $messages);

            if ($response->successful()) {
                $activeIndex = $i;

                return $response;
            }

            $lastError = $response->body();
        }

        throw new \RuntimeException('Falha ao chamar os provedores de LLM: '.$lastError);
    }

    /**
     * @param  array{base_url: string, api_key: string, model: string}  $provider
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function callProvider(array $provider, array $config, array $tools, array $messages): Response
    {
        $payload = [
            'model' => $provider['model'],
            'messages' => $messages,
            'temperature' => (float) $config['temperature'],
            'max_tokens' => (int) $config['max_tokens'],
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        return $this->postWithRetry($provider, $config, $payload, false);
    }

    /**
     * POST to the provider, retrying with backoff while it answers with HTTP 429
     * (rate limit). The Groq "try again in Xs" hint is honored when present.
     */
    private function postWithRetry(array $provider, array $config, array $payload, bool $stream): Response
    {
        $maxAttempts = (int) ($config['retry_attempts'] ?? 3);
        $delaySeconds = (float) ($config['retry_base_delay'] ?? 2);

        $response = Http::withToken((string) $provider['api_key'])
            ->withOptions(['stream' => $stream])
            ->timeout((int) $config['request_timeout'])
            ->post((string) $provider['base_url'], $payload);

        for ($attempt = 1; $attempt < $maxAttempts && $response->status() === 429; $attempt++) {
            $wait = $this->retryDelaySeconds($response, $delaySeconds);
            $delaySeconds = min($delaySeconds * 2, 15);
            usleep((int) ($wait * 1000000));

            $response = Http::withToken((string) $provider['api_key'])
                ->withOptions(['stream' => $stream])
                ->timeout((int) $config['request_timeout'])
                ->post((string) $provider['base_url'], $payload);
        }

        return $response;
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
    private function buildInitialMessages(array $history, string $message, ?string $promptText = null): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'Você é o assistente virtual da academia Gymnamite. Responda sempre em '
                    .'português do Brasil. Use as ferramentas disponíveis apenas quando forem '
                    .'necessárias para obter ou registrar dados. Após coletar as informações '
                    .'necessárias, forneça uma resposta final e objetiva ao usuário, sem chamar '
                    .'ferramentas adicionais.',
            ],
        ];

        if ($promptText !== null && $promptText !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => 'Siga estritamente estas instruções para a tarefa solicitada: '.$promptText,
            ];
        }

        foreach ($history as $entry) {
            $messages[] = [
                'role' => $entry['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $entry['content'],
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message,
        ];

        return $messages;
    }

    /**
     * When the tool-calling loop ends without a final textual answer (reasoning
     * models often emit empty content after using tools, or the iteration cap
     * is reached mid-workflow), ask the model once more without tools so it must
     * produce a plain answer from the accumulated context.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @param  array<int, array{base_url: string, api_key: string, model: string}>  $providers
     * @param  array<string, mixed>  $config
     */
    private function synthesizeFinalAnswer(array $messages, array $providers, int &$activeIndex, array $config): string
    {
        $clean = $this->stripToolInteractions($messages);

        try {
            $response = $this->completeChat($providers, $activeIndex, $config, [], $clean);
        } catch (Throwable $exception) {
            return '';
        }

        $data = $response->json();
        $content = $data['choices'][0]['message']['content'] ?? '';

        return is_string($content) ? trim($content) : '';
    }

    /**
     * Remove tool-result messages and tool_call markers so the final synthesis
     * call is a clean conversational transcript the model can summarize.
     *
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    private function stripToolInteractions(array $messages): array
    {
        $clean = [];

        foreach ($messages as $message) {
            if (($message['role'] ?? null) === 'tool') {
                continue;
            }

            unset($message['tool_calls']);

            $clean[] = $message;
        }

        return $clean;
    }

    /**
     * @param  array<string, array{resource: \Laravel\Mcp\Server\Resource, params: array<int, string>}>  $map
     */
    private function executeResource(array $map, string $name, array $arguments): string
    {
        $entry = $map[$name] ?? null;

        if ($entry === null) {
            return json_encode(
                ['error' => "Recurso {$name} não disponível para este usuário."],
                JSON_UNESCAPED_UNICODE,
            );
        }

        /** @var \Laravel\Mcp\Server\Resource $resource */
        $resource = $entry['resource'];
        $params = $entry['params'];

        $requestArguments = [];
        foreach ($params as $param) {
            $requestArguments[$param] = $arguments[$param] ?? null;
        }

        try {
            $request = new Request($requestArguments);
            $result = $resource->handle($request);
        } catch (Throwable $exception) {
            return json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        return $this->responseToText($result);
    }

    /**
     * Execute a writable MCP tool for the current user and return its result
     * as text for the LLM. Validation or runtime failures are returned as an
     * error payload so the model can recover instead of aborting the turn.
     *
     * @param  class-string<Tool>  $class
     */
    private function executeTool(string $class, array $arguments): string
    {
        try {
            $tool = app($class);
            $result = $tool->handle(new Request($arguments));
        } catch (ValidationException $exception) {
            return json_encode(
                ['error' => 'Validação falhou: '.implode('; ', Arr::flatten($exception->errors()))],
                JSON_UNESCAPED_UNICODE,
            );
        } catch (Throwable $exception) {
            return json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
        }

        return $this->responseToText($result);
    }

    private function responseToText(mixed $result): string
    {
        if ($result instanceof ResponseFactory) {
            $structured = $result->getStructuredContent();

            if ($structured !== null) {
                return json_encode($structured, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            $text = '';
            foreach ($result->responses() as $response) {
                $text .= (string) $response->content();
            }

            return $text;
        }

        if ($result instanceof McpResponse) {
            return (string) $result->content();
        }

        return (string) $result;
    }

    /**
     * Resolve and execute a resource read or writable tool by name for the
     * current user, returning the result as text (errors are returned as
     * payloads so the model can recover).
     *
     * @param  array<string, array{resource: \Laravel\Mcp\Server\Resource, params: array<int, string>, tool: class-string<Tool>}|null>  $map
     */
    private function executeToolCall(string $name, array $arguments, array $map): string
    {
        $entry = $map[$name] ?? null;

        if ($entry === null) {
            return json_encode(
                ['error' => "Ferramenta {$name} não disponível para este usuário."],
                JSON_UNESCAPED_UNICODE,
            );
        }

        if (isset($entry['tool'])) {
            return $this->executeTool($entry['tool'], $arguments);
        }

        return $this->executeResource($map, $name, $arguments);
    }

    /**
     * Open a streaming completion against one provider and return its raw body
     * stream, or null when the provider fails.
     *
     * @param  array{base_url: string, api_key: string, model: string}  $provider
     * @param  array<string, mixed>  $config
     * @param  array<int, array<string, mixed>>  $tools
     * @param  array<int, array<string, mixed>>  $messages
     */
    private function streamProvider(array $provider, array $config, array $tools, array $messages): ?StreamInterface
    {
        $payload = [
            'model' => $provider['model'],
            'messages' => $messages,
            'temperature' => (float) $config['temperature'],
            'max_tokens' => (int) $config['max_tokens'],
            'stream' => true,
        ];

        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
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
     * Read an SSE stream, invoking $onToken for each text delta, and return the
     * accumulated text and reconstructed tool_calls. When $shouldStop returns
     * true, the loop stops reading so the upstream connection is closed.
     *
     * @return array{content: string, tool_calls: array<int, array{id: string, type: string, function: array{name: string, arguments: string}}>}
     */
    private function streamOneCompletion(StreamInterface $body, callable $onToken, ?callable $shouldStop = null): array
    {
        $content = '';
        $toolCalls = [];
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

                if ($data === '') {
                    continue;
                }

                if ($data === '[DONE]') {
                    return ['content' => $content, 'tool_calls' => $toolCalls];
                }

                $json = json_decode($data, true);

                if (! is_array($json)) {
                    continue;
                }

                $delta = $json['choices'][0]['delta'] ?? [];

                if (isset($delta['content']) && $delta['content'] !== '') {
                    $content .= (string) $delta['content'];
                    $onToken((string) $delta['content']);
                }

                if (isset($delta['tool_calls']) && is_array($delta['tool_calls'])) {
                    foreach ($delta['tool_calls'] as $tc) {
                        $index = (int) ($tc['index'] ?? 0);

                        if (! isset($toolCalls[$index])) {
                            $toolCalls[$index] = [
                                'id' => '',
                                'type' => 'function',
                                'function' => ['name' => '', 'arguments' => ''],
                            ];
                        }

                        if (isset($tc['id'])) {
                            $toolCalls[$index]['id'] = (string) $tc['id'];
                        }

                        if (isset($tc['function']['name'])) {
                            $toolCalls[$index]['function']['name'] .= (string) $tc['function']['name'];
                        }

                        if (isset($tc['function']['arguments'])) {
                            $toolCalls[$index]['function']['arguments'] .= (string) $tc['function']['arguments'];
                        }
                    }
                }
            }
        }

        return ['content' => $content, 'tool_calls' => $toolCalls];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function sseEvent(array $payload): string
    {
        return 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE)."\n\n";
    }
}
