<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Permission;
use App\Models\User;
use App\Services\Mcp\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    // No Http::preventStrayRequests() here: Inertia SSR posts to the local
    // /render endpoint, which is a legitimate internal request.

    private function givePermission(User $user, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['name' => $permissionName, 'description' => $permissionName],
        );

        $user->permissions()->attach($permission);
    }

    public function test_chat_never_sends_tools_or_tool_choice_to_the_provider(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Abra o menu Clientes.',
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', ['message' => 'Como cadastro um cliente?'])
            ->assertOk();

        foreach (Http::recorded() as [$request]) {
            $body = $request->data();

            $this->assertArrayNotHasKey('tools', $body, 'O chat nao pode enviar schema de ferramenta.');
            $this->assertArrayNotHasKey('functions', $body, 'O chat nao pode enviar functions.');
            $this->assertArrayNotHasKey('tool_choice', $body, 'O chat nao pode oferecer escolha de ferramenta.');
        }
    }

    public function test_chat_makes_a_single_provider_call_per_message(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Resposta direta.',
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', ['message' => 'Como registro pagamento?'])
            ->assertOk()
            ->assertJson(['reply' => 'Resposta direta.']);

        $this->assertCount(
            1,
            Http::recorded(),
            'Sem loop de ferramenta, uma mensagem deve custar exatamente uma chamada.',
        );
    }

    public function test_chat_injects_matching_guide_as_context(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Siga os passos.'],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Como registro o pagamento de uma parcela de aula direta?',
        ])->assertOk();

        $body = Http::recorded()->first()[0]->data();
        $sent = implode("\n", array_column($body['messages'], 'content'));

        $this->assertStringContainsString('Guia: aulas-diretas', $sent);
    }

    public function test_chat_sends_system_prompt_with_screen_menu_and_no_write_instructions(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Resposta.'],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', ['message' => 'oi'])->assertOk();

        $body = Http::recorded()->first()[0]->data();
        $system = $body['messages'][0]['content'] ?? '';

        $this->assertSame('system', $body['messages'][0]['role']);
        $this->assertStringContainsString('/receivables?searchField=status&search=overdued', $system);
        $this->assertStringContainsString('Telas disponíveis', $system);
    }

    public function test_chat_falls_back_to_next_model_when_first_fails(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        config([
            'mcp_chat.base_url' => 'https://fake.test/chat/completions',
            'mcp_chat.providers' => ['model-a', 'model-b'],
        ]);

        Http::fake([
            'https://fake.test/chat/completions' => Http::sequence()
                ->push(['error' => 'boom'], 500)
                ->push([
                    'choices' => [[
                        'message' => ['role' => 'assistant', 'content' => 'Resposta do segundo modelo.'],
                    ]],
                ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', ['message' => 'oi'])
            ->assertOk()
            ->assertJson(['reply' => 'Resposta do segundo modelo.']);

        $models = array_map(fn ($pair) => $pair[0]->data()['model'], Http::recorded()->all());
        $this->assertSame(['model-a', 'model-b'], $models);
    }

    public function test_chat_sends_chat_template_kwargs_from_config(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        config([
            'mcp_chat.base_url' => 'https://fake.test/chat/completions',
            'mcp_chat.providers' => ['some/model'],
            'mcp_chat.chat_template_kwargs' => ['enable_thinking' => false],
        ]);

        Http::fake([
            'https://fake.test/chat/completions' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Resposta final.'],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', ['message' => 'Ola'])
            ->assertOk()
            ->assertJson(['reply' => 'Resposta final.']);

        $recorded = Http::recorded()->first(fn ($pair) => str_contains($pair[0]->url(), 'fake.test'));
        $this->assertSame(['enable_thinking' => false], $recorded[0]->data()['chat_template_kwargs']);
    }

    public function test_chat_persists_conversation_and_messages(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Resposta persistida.',
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Primeira mensagem',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['reply', 'conversation_id']);
        $this->assertNotNull($response->json('conversation_id'));

        $this->assertDatabaseHas('chat_conversations', [
            'user_id' => $user->id,
            'title' => 'Primeira mensagem',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'role' => 'user',
            'content' => 'Primeira mensagem',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'role' => 'assistant',
            'content' => 'Resposta persistida.',
        ]);
    }

    public function test_chat_uses_database_history_when_conversation_id_provided(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Conversa existente',
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Mensagem anterior do usuário']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Resposta anterior do assistente']);

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Resposta com contexto.',
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Continuação',
            'conversation_id' => $conversation->id,
        ]);

        $response->assertOk();
        $response->assertJson(['conversation_id' => $conversation->id]);

        $sentHistory = [];
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            if (! empty($body['messages'])) {
                $sentHistory = array_column($body['messages'], 'content');
            }
        }

        $this->assertContains('Mensagem anterior do usuário', $sentHistory);
        $this->assertContains('Resposta anterior do assistente', $sentHistory);
        $this->assertContains('Continuação', $sentHistory);

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Continuação',
        ]);

        $this->assertSame(
            4,
            ChatMessage::where('conversation_id', $conversation->id)->count(),
            'A conversa deve acumular 2 mensagens anteriores + 2 novas.',
        );
    }

    public function test_chat_lists_recent_conversations_for_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $old = Conversation::create(['user_id' => $user->id, 'title' => 'Conversa antiga']);
        $old->forceFill(['updated_at' => now()->subDays(2)])->save();

        $recent = Conversation::create(['user_id' => $user->id, 'title' => 'Conversa recente']);
        $recent->forceFill(['updated_at' => now()->subHour()])->save();

        Conversation::create(['user_id' => $other->id, 'title' => 'De outro usuário']);

        $response = $this->actingAs($user)->getJson('/chat/conversations');

        $response->assertOk();
        $titles = array_column($response->json('conversations'), 'title');
        $this->assertSame(['Conversa recente', 'Conversa antiga'], $titles);
    }

    public function test_chat_lists_only_the_ten_most_recent_conversations(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        foreach (range(1, 12) as $index) {
            $conversation = Conversation::create([
                'user_id' => $user->id,
                'title' => "Conversa {$index}",
            ]);
            $conversation->forceFill(['updated_at' => now()->subMinutes($index)])->save();
        }

        $response = $this->actingAs($user)->getJson('/chat/conversations');

        $response->assertOk();
        $this->assertCount(10, $response->json('conversations'));
    }

    public function test_chat_returns_conversation_messages_for_owner(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $conversation = Conversation::create(['user_id' => $user->id, 'title' => 'Minha']);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Pergunta']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Resposta']);

        $response = $this->actingAs($user)->getJson("/chat/conversations/{$conversation->id}");

        $response->assertOk();
        $this->assertSame('Minha', $response->json('conversation.title'));
        $this->assertSame(
            ['Pergunta', 'Resposta'],
            array_column($response->json('messages'), 'text'),
        );
    }

    public function test_chat_hides_conversation_messages_from_other_users(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $conversation = Conversation::create(['user_id' => $other->id, 'title' => 'De outro']);

        $this->actingAs($user)
            ->getJson("/chat/conversations/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_chat_message_updates_conversation_activity_ordering(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $first = Conversation::create(['user_id' => $user->id, 'title' => 'Primeira']);
        $first->forceFill(['updated_at' => now()->subHour()])->save();

        $second = Conversation::create(['user_id' => $user->id, 'title' => 'Segunda']);
        $second->forceFill(['updated_at' => now()->subDays(3)])->save();

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Nova resposta.'],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Continuação',
            'conversation_id' => $first->id,
        ])->assertOk();

        $titles = array_column($this->actingAs($user)->getJson('/chat/conversations')->json('conversations'), 'title');

        $this->assertSame(['Primeira', 'Segunda'], $titles);
    }

    public function test_chat_stream_emits_tokens_and_persists_message(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $sseBody = implode("\n\n", [
            'data: '.json_encode(['choices' => [['delta' => ['content' => 'Olá']]]]),
            'data: '.json_encode(['choices' => [['delta' => ['content' => ' mundo']]]]),
            'data: [DONE]',
        ])."\n\n";

        Http::fake([
            '*' => Http::response($sseBody, 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Oi',
            'stream' => true,
        ]);

        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        ob_start();
        $response->baseResponse->sendContent();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('"type":"meta"', $output);
        $this->assertStringContainsString('"type":"token"', $output);
        $this->assertStringContainsString('"content":"Olá"', $output);
        $this->assertStringContainsString('"content":" mundo"', $output);
        $this->assertStringContainsString('"type":"done"', $output);
        $this->assertStringContainsString('"content":"Olá mundo"', $output);

        $this->assertDatabaseHas('chat_messages', [
            'role' => 'user',
            'content' => 'Oi',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'role' => 'assistant',
            'content' => 'Olá mundo',
        ]);
    }

    public function test_chat_stream_interrupts_upstream_and_persists_partial_when_client_disconnects(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Stream interrompido',
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Oi']);

        // First 8192-byte read delivers the "Olá" token; the rest of the
        // upstream stream (" mundo" + [DONE]) must never be consumed because
        // the client disconnects in between.
        $padding = ': '.str_repeat('x', 8100)."\n\n";
        $sseBody = 'data: '.json_encode(['choices' => [['delta' => ['content' => 'Olá']]]])."\n\n"
            .$padding
            .'data: '.json_encode(['choices' => [['delta' => ['content' => ' mundo']]]])."\n\n"
            ."data: [DONE]\n\n";

        Http::fake([
            '*' => Http::response($sseBody, 200, ['Content-Type' => 'text/event-stream']),
        ]);

        $service = app(ChatService::class);

        // Interrupt as soon as the first token has been emitted, whatever the
        // number of internal checks. Counting calls would couple the test to
        // the service's read loop.
        $response = $service->streamAsk(
            'Oi',
            [],
            function (string $reply) use ($conversation): void {
                $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $reply,
                ]);
            },
            $conversation->id,
            null,
            function (): bool {
                return str_contains((string) ob_get_contents(), 'Olá');
            },
        );

        ob_start();
        $response->sendContent();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('"type":"token"', $output);
        $this->assertStringContainsString('"content":"Olá"', $output);
        $this->assertStringNotContainsString('"type":"done"', $output);

        $this->assertCount(1, Http::recorded(), 'Nenhuma chamada adicional ao provedor após a desconexão.');

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Olá',
        ]);

        $this->assertDatabaseMissing('chat_messages', [
            'conversation_id' => $conversation->id,
            'content' => 'Olá mundo',
        ]);
    }

    public function test_chat_lists_suggested_questions_without_module_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $response = $this->actingAs($user)->getJson('/chat/prompts');

        $response->assertOk();
        $response->assertJsonStructure(['prompts']);

        $prompts = $response->json('prompts');
        $this->assertNotEmpty($prompts);
        $this->assertArrayHasKey('name', $prompts[0]);
        $this->assertArrayHasKey('label', $prompts[0]);
        $this->assertArrayHasKey('question', $prompts[0]);

        $names = array_column($prompts, 'name');
        $this->assertContains('vendas-vencidas', $names);
    }

    public function test_chat_suggestion_resolves_to_its_question(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => ['role' => 'assistant', 'content' => 'Resposta.'],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Contas vencidas',
            'prompt' => 'vendas-vencidas',
        ])->assertOk();

        $body = Http::recorded()->first()[0]->data();
        $sent = implode("\n", array_column($body['messages'], 'content'));

        $this->assertStringContainsString('contas a receber vencidas', $sent);
    }
}