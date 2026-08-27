<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Models\ChatMessage;
use App\Models\Client;
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

    private function givePermission(User $user, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['name' => $permissionName, 'description' => $permissionName],
        );

        $user->permissions()->attach($permission);
    }

    public function test_chat_executes_readonly_resource_via_llm_tool_call(): void
    {
        $client = Client::factory()->create(['name' => 'Cliente Teste MCP']);

        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.view');

        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'read_client',
                                    'arguments' => (string) json_encode(['id' => (string) $client->id]),
                                ],
                            ]],
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Aqui estão os dados do cliente.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Mostre o cliente',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Aqui estão os dados do cliente.']);

        $toolCalled = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            foreach ($body['messages'] ?? [] as $message) {
                if (($message['role'] ?? null) === 'tool'
                    && str_contains((string) ($message['content'] ?? ''), 'Cliente Teste MCP')) {
                    $toolCalled = true;
                }
            }
        }

        $this->assertTrue($toolCalled, 'O recurso read_client não foi executado com os dados do cliente.');
    }

    public function test_chat_without_module_permission_exposes_no_resource_tool(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Resposta sem ferramentas.',
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Mostre algo',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Resposta sem ferramentas.']);

        $sentTools = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            if (! empty($body['tools'])) {
                $sentTools = true;
            }
        }

        $this->assertFalse($sentTools, 'Usuário sem permissão de módulo não deve receber tools.');
    }

    public function test_chat_falls_back_to_next_model_when_first_fails(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        config([
            'mcp_chat.providers' => ['primary/model', 'fallback/model'],
        ]);

        Http::fake([
            '*' => Http::sequence()
                ->push(['choices' => [['message' => ['role' => 'assistant', 'content' => 'erro']]]], 500)
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Resposta do modelo de fallback.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Olá',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Resposta do modelo de fallback.']);

        // First request (primary model) failed, second (fallback) succeeded.
        $this->assertCount(2, Http::recorded());
    }

    public function test_chat_executes_writable_tool_when_user_has_permission(): void
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.create');

        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'create-client',
                                    'arguments' => (string) json_encode([
                                        'name' => 'Cliente Via Tool',
                                        'email' => 'cliente@tool.com',
                                        'phone' => '11999999999',
                                        'document' => '12345678901',
                                        'gender' => 'male',
                                        'birth_date' => '1990-01-01',
                                    ]),
                                ],
                            ]],
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Cliente criado com sucesso.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Crie um cliente',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Cliente criado com sucesso.']);

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Via Tool',
            'document' => '12345678901',
        ]);

        $toolCalled = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            if (! empty($body['tools'])) {
                $names = array_column(array_column($body['tools'], 'function'), 'name');
                if (in_array('create-client', $names, true)) {
                    $toolCalled = true;
                }
            }
        }

        $this->assertTrue($toolCalled, 'A tool create-client não foi exposta ao LLM.');
    }

    public function test_chat_hides_writable_tool_without_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        // Intentionally NOT granting clients.create.

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Sem ferramentas de escrita.',
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Crie um cliente',
        ]);

        $response->assertOk();

        $exposedToolNames = [];
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            if (! empty($body['tools'])) {
                $exposedToolNames = array_merge(
                    $exposedToolNames,
                    array_column(array_column($body['tools'], 'function'), 'name'),
                );
            }
        }

        $this->assertNotContains('create-client', $exposedToolNames, 'Usuário sem permissão não deve ver a tool de escrita.');
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
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Resposta com contexto.',
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
        $titles = array_column($response->json('conversations'), 'title');
        $this->assertCount(10, $titles);
        $this->assertSame('Conversa 1', $titles[0]);
        $this->assertSame('Conversa 10', $titles[9]);
    }

    public function test_chat_returns_conversation_messages_for_owner(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'title' => 'Minha conversa',
        ]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Olá']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Olá! Como posso ajudar?']);

        $response = $this->actingAs($user)->getJson("/chat/conversations/{$conversation->id}");

        $response->assertOk();
        $response->assertJsonPath('conversation.id', $conversation->id);
        $response->assertJsonPath('conversation.title', 'Minha conversa');

        $messages = $response->json('messages');
        $this->assertCount(2, $messages);
        $this->assertSame(['id', 'role', 'text'], array_keys($messages[0]));
        $this->assertSame('user', $messages[0]['role']);
        $this->assertSame('Olá', $messages[0]['text']);
        $this->assertSame('assistant', $messages[1]['role']);
        $this->assertSame('Olá! Como posso ajudar?', $messages[1]['text']);
    }

    public function test_chat_hides_conversation_messages_from_other_users(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $this->givePermission($intruder, 'chat.view');

        $conversation = Conversation::create([
            'user_id' => $owner->id,
            'title' => 'Conversa privada',
        ]);

        $this->actingAs($intruder)
            ->getJson("/chat/conversations/{$conversation->id}")
            ->assertNotFound();
    }

    public function test_chat_message_updates_conversation_activity_ordering(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $old = Conversation::create(['user_id' => $user->id, 'title' => 'Conversa antiga']);
        $old->forceFill(['updated_at' => now()->subDays(1)])->save();

        $newer = Conversation::create(['user_id' => $user->id, 'title' => 'Conversa mais nova']);
        $newer->forceFill(['updated_at' => now()->subMinutes(5)])->save();

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Resposta na conversa antiga.',
                    ],
                ]],
            ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Continuando a conversa antiga',
            'conversation_id' => $old->id,
        ])->assertOk();

        $response = $this->actingAs($user)->getJson('/chat/conversations');

        $response->assertOk();
        $titles = array_column($response->json('conversations'), 'title');
        $this->assertSame('Conversa antiga', $titles[0], 'A conversa com atividade recente deve vir primeiro.');
    }

    public function test_chat_usa_groq_com_formato_tool_calls(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.create');

        config([
            'mcp_chat.base_url' => 'https://api.groq.com/openai/v1/chat/completions',
            'mcp_chat.providers' => ['llama-3.3-70b-versatile'],
        ]);

        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'create-client',
                                    'arguments' => (string) json_encode([
                                        'name' => 'Cliente Groq',
                                        'email' => 'groq@tool.com',
                                        'phone' => '11999999999',
                                        'document' => '12345678901',
                                        'gender' => 'male',
                                        'birth_date' => '1990-01-01',
                                    ]),
                                ],
                            ]],
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Cliente criado via Groq.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Crie um cliente',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Cliente criado via Groq.']);

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Groq',
            'document' => '12345678901',
        ]);

        $recorded = Http::recorded()
            ->first(fn ($pair) => str_contains($pair[0]->url(), 'groq.com'));

        $this->assertNotNull($recorded, 'A requisição deve ir para o endpoint da Groq.');
        $body = $recorded[0]->data();
        $this->assertSame('llama-3.3-70b-versatile', $body['model']);
        $this->assertArrayHasKey('tools', $body);
        $this->assertSame('auto', $body['tool_choice']);
    }

    public function test_chat_envia_chat_template_kwargs_do_config(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        config([
            'mcp_chat.base_url' => 'https://fake.test/chat/completions',
            'mcp_chat.providers' => ['some/model'],
            'mcp_chat.chat_template_kwargs' => ['enable_thinking' => false],
        ]);

        Http::fake([
            'https://fake.test/chat/completions' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Resposta final.',
                        ],
                    ]],
                ]),
        ]);

        $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Ola',
        ])->assertOk()->assertJson(['reply' => 'Resposta final.']);

        $recorded = Http::recorded()
            ->first(fn ($pair) => str_contains($pair[0]->url(), 'fake.test'));

        $this->assertNotNull($recorded, 'A requisição deve ir para o provedor configurado.');
        $this->assertSame(['enable_thinking' => false], $recorded[0]->data()['chat_template_kwargs']);
    }

    public function test_chat_stream_emite_tokens_e_persiste_mensagem(): void
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

        $checks = 0;
        $service = app(ChatService::class);

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
            function () use (&$checks): bool {
                $checks++;

                return $checks > 3;
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

    public function test_chat_lists_eligible_prompts_for_current_user(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.create');

        $response = $this->actingAs($user)->getJson('/chat/prompts');

        $response->assertOk();
        $response->assertJsonStructure(['prompts']);
        $this->assertNotEmpty($response->json('prompts'));
        $this->assertArrayHasKey('text', $response->json('prompts')[0]);

        $names = array_column($response->json('prompts'), 'name');
        $this->assertContains('onboard-client', $names);
    }

    public function test_chat_hides_prompts_without_module_permission(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');

        $response = $this->actingAs($user)->getJson('/chat/prompts');

        $response->assertOk();
        $this->assertEmpty($response->json('prompts'));
    }

    public function test_chat_synthesizes_final_answer_when_model_returns_empty_content(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.view');

        Http::fake([
            '*' => Http::sequence()
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => null,
                            'tool_calls' => [[
                                'id' => 'call_1',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'read_client',
                                    'arguments' => (string) json_encode(['id' => '1']),
                                ],
                            ]],
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => '',
                        ],
                    ]],
                ])
                ->push([
                    'choices' => [[
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Resposta final sintetizada a partir dos dados.',
                        ],
                    ]],
                ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Mostre o cliente 1',
        ]);

        $response->assertOk();
        $response->assertJson(['reply' => 'Resposta final sintetizada a partir dos dados.']);
    }

    public function test_chat_injects_prompt_instructions_when_prompt_activated(): void
    {
        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.create');

        Http::fake([
            '*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Vamos criar o cliente.',
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Criar cliente',
            'prompt' => 'onboard-client',
        ]);

        $response->assertOk();

        $injected = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            foreach ($body['messages'] ?? [] as $message) {
                if (($message['role'] ?? null) === 'system'
                    && str_contains((string) ($message['content'] ?? ''), 'criar contrato informando o ID do cliente')) {
                    $injected = true;
                }
            }
        }

        $this->assertTrue($injected, 'As instruções do prompt não foram injetadas como mensagem de sistema.');
    }

    public function test_chat_stream_executes_resource_via_tool_call(): void
    {
        $client = Client::factory()->create(['name' => 'Cliente Stream Tool']);

        $user = User::factory()->create();
        $this->givePermission($user, 'chat.view');
        $this->givePermission($user, 'clients.view');

        $toolCallChunk = json_encode([
            'choices' => [[
                'delta' => [
                    'tool_calls' => [[
                        'index' => 0,
                        'id' => 'call_1',
                        'type' => 'function',
                        'function' => [
                            'name' => 'read_client',
                            'arguments' => (string) json_encode(['id' => (string) $client->id]),
                        ],
                    ]],
                ],
            ]],
        ]);

        $finalChunk = json_encode([
            'choices' => [[
                'delta' => ['content' => 'Aqui estão os dados do cliente.'],
            ]],
        ]);

        $sseToolCall = 'data: '.$toolCallChunk."\n\n".'data: [DONE]'."\n\n";
        $sseFinal = 'data: '.$finalChunk."\n\n".'data: [DONE]'."\n\n";

        Http::fake([
            '*' => Http::sequence()
                ->push($sseToolCall)
                ->push($sseFinal),
        ]);

        $response = $this->actingAs($user)->postJson('/chat/message', [
            'message' => 'Mostre o cliente',
            'stream' => true,
        ]);

        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));

        ob_start();
        $response->baseResponse->sendContent();
        $output = (string) ob_get_clean();

        $this->assertStringContainsString('"type":"done"', $output);

        $toolCalled = false;
        foreach (Http::recorded() as [$request]) {
            $body = $request->data();
            foreach ($body['messages'] ?? [] as $message) {
                if (($message['role'] ?? null) === 'tool'
                    && str_contains((string) ($message['content'] ?? ''), 'Cliente Stream Tool')) {
                    $toolCalled = true;
                }
            }
        }

        $this->assertTrue($toolCalled, 'O recurso read_client não foi executado via streaming.');
    }
}
