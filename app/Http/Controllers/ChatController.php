<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\Mcp\ChatPromptProvider;
use App\Services\Mcp\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly ChatPromptProvider $promptProvider,
    ) {}

    public function message(Request $request): JsonResponse|StreamedResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['nullable', 'array'],
            'history.*.role' => ['required_with:history', 'string', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:8000'],
            'conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'prompt' => ['nullable', 'string', 'max:255'],
            'stream' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if (! empty($data['conversation_id'])) {
            $conversation = Conversation::query()
                ->where('id', $data['conversation_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $history = $conversation->messages()
                ->orderBy('id')
                ->get(['role', 'content'])
                ->map(fn ($message) => ['role' => $message->role, 'content' => $message->content])
                ->all();
        } else {
            $history = $data['history'] ?? [];

            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => mb_substr($data['message'], 0, 100),
            ]);
        }

        $conversation->messages()->create([
            'role' => 'user',
            'content' => $data['message'],
        ]);
        $conversation->touch();

        if (! empty($data['stream'])) {
            return $this->chatService->streamAsk(
                $data['message'],
                $history,
                function (string $reply) use ($conversation): void {
                    $conversation->messages()->create([
                        'role' => 'assistant',
                        'content' => $reply,
                    ]);
                },
                $conversation->id,
                $data['prompt'] ?? null,
            );
        }

        $reply = $this->chatService->ask($data['message'], $history, $data['prompt'] ?? null);

        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return response()->json([
            'reply' => $reply,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $conversations = Conversation::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'updated_at']);

        return response()->json([
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        abort_unless($conversation->user_id === $request->user()->id, 404);

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
            ],
            'messages' => $conversation->messages()
                ->orderBy('id')
                ->get(['id', 'role', 'content'])
                ->map(fn (ChatMessage $message): array => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'text' => $message->content,
                ]),
        ]);
    }

    public function prompts(Request $request): JsonResponse
    {
        return response()->json([
            'prompts' => $this->promptProvider->promptsForCurrentUser(),
        ]);
    }
}
