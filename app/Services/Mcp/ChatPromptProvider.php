<?php

declare(strict_types=1);

namespace App\Services\Mcp;

use App\Mcp\Servers\GymnamiteServer;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response as McpResponse;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Prompt;
use ReflectionClass;

class ChatPromptProvider
{
    /**
     * Short labels shown as chat chips. Keeps the UI compact; the detailed
     * instructions remain hidden from the user and are injected server-side.
     *
     * @var array<string, string>
     */
    private const LABELS = [
        'onboard-client' => 'Criar cliente',
        'collect-receivable' => 'Registrar recebimento',
        'financial-overview' => 'Resumo financeiro',
        'register-trainer' => 'Cadastrar treinador',
        'create-financial-category' => 'Cadastrar categoria financeira',
        'create-cost-center' => 'Cadastrar centro de custo',
        'create-product' => 'Cadastrar produto',
    ];

    /**
     * Build the list of MCP prompts the current user is allowed to use, ready
     * to be rendered as conversation starters in the chat UI.
     *
     * @return array<int, array{name: string, label: string, description: string, text: string, client_message: ?string}>
     */
    public function promptsForCurrentUser(): array
    {
        $prompts = [];

        foreach ($this->promptClassList() as $class) {
            if (! class_exists($class)) {
                continue;
            }

            /** @var Prompt $prompt */
            $prompt = app($class);

            if (! $prompt->eligibleForRegistration()) {
                continue;
            }

            $name = $prompt->name();

            $prompts[] = [
                'name' => $name,
                'label' => self::LABELS[$name] ?? $prompt->description(),
                'description' => $prompt->description(),
                'text' => $this->promptText($prompt),
                'client_message' => $this->clientMessage($prompt),
            ];
        }

        return $prompts;
    }

    /**
     * Resolve the hidden instruction text for a prompt the current user is
     * allowed to use, or null when the prompt is unknown or not permitted.
     */
    public function promptTextByName(string $name): ?string
    {
        foreach ($this->promptClassList() as $class) {
            if (! class_exists($class)) {
                continue;
            }

            /** @var Prompt $prompt */
            $prompt = app($class);

            if (! $prompt->eligibleForRegistration() || $prompt->name() !== $name) {
                continue;
            }

            return $this->promptText($prompt);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function promptClassList(): array
    {
        $defaults = (new ReflectionClass(GymnamiteServer::class))
            ->getDefaultProperties();

        return $defaults['prompts'] ?? [];
    }

    /**
     * Render the prompt instructions as plain text for the user to send.
     */
    private function promptText(Prompt $prompt): string
    {
        $result = $prompt->handle(new Request([]));

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
     * Return the optional client-facing message text for a prompt, or null
     * when the prompt is not suitable for forwarding to a client.
     */
    private function clientMessage(Prompt $prompt): ?string
    {
        if (! method_exists($prompt, 'clientMessage')) {
            return null;
        }

        /** @var string|null $message */
        $message = $prompt->clientMessage();

        return $message !== '' ? $message : null;
    }
}
