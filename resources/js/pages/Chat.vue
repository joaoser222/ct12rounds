<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ChatHistoryPanel from '@/components/chat/ChatHistoryPanel.vue';

defineOptions({ layout: AuthenticatedLayout });

type ChatMessage = {
    id: number;
    role: 'user' | 'assistant';
    text: string;
};

type ChatPrompt = {
    name: string;
    label: string;
    description: string;
    text: string;
    client_message: string | null;
};

type ConversationSummary = {
    id: number;
    title: string | null;
    updated_at: string;
};

const messages = ref<ChatMessage[]>([]);
const draft = ref('');
const loading = ref(false);
const conversationId = ref<number | null>(null);
const prompts = ref<ChatPrompt[]>([]);
const conversations = ref<ConversationSummary[]>([]);
const historyDrawer = ref(false);
const messagesHost = ref<HTMLElement | null>(null);
const abortController = ref<AbortController | null>(null);
const snackbar = ref(false);
const snackbarText = ref('');

const xsrfToken = decodeURIComponent(
    document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? '',
);

const jsonHeaders = {
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    'X-XSRF-TOKEN': xsrfToken,
};

const currentTitle = computed(() => {
    return (
        conversations.value.find(
            (conversation) => conversation.id === conversationId.value,
        )?.title ?? 'Nova conversa'
    );
});

const awaitingReply = computed(() => {
    const last = messages.value[messages.value.length - 1];

    return loading.value && last?.role === 'assistant' && last.text === '';
});

async function loadPrompts(): Promise<void> {
    try {
        const response = await fetch('/chat/prompts', { headers: jsonHeaders });

        if (! response.ok) {
            return;
        }

        const data = await response.json();
        prompts.value = data.prompts ?? [];
    } catch {
        prompts.value = [];
    }
}

async function loadConversations(): Promise<void> {
    try {
        const response = await fetch('/chat/conversations', {
            headers: jsonHeaders,
        });

        if (! response.ok) {
            return;
        }

        const data = await response.json();
        conversations.value = data.conversations ?? [];
    } catch {
        conversations.value = [];
    }
}

function applyPrompt(prompt: ChatPrompt): void {
    if (loading.value) {
        return;
    }

    draft.value = prompt.label;
    void send(prompt.name);
}

async function copyClientMessage(prompt: ChatPrompt): Promise<void> {
    if (!prompt.client_message) {
        return;
    }

    try {
        await navigator.clipboard.writeText(prompt.client_message);
        snackbarText.value = 'Mensagem copiada!';
        snackbar.value = true;
    } catch {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = prompt.client_message;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            snackbarText.value = 'Mensagem copiada!';
            snackbar.value = true;
        } catch {
            snackbarText.value = 'Não foi possível copiar a mensagem.';
            snackbar.value = true;
        }
    }
}

function startNewConversation(): void {
    if (loading.value) {
        return;
    }

    conversationId.value = null;
    messages.value = [];
    historyDrawer.value = false;
}

async function selectConversation(
    conversation: ConversationSummary,
): Promise<void> {
    historyDrawer.value = false;

    if (loading.value || conversation.id === conversationId.value) {
        return;
    }

    try {
        const response = await fetch(
            `/chat/conversations/${conversation.id}`,
            { headers: jsonHeaders },
        );

        if (! response.ok) {
            throw new Error('Não foi possível carregar a conversa.');
        }

        const data = await response.json();
        conversationId.value = data.conversation.id;
        messages.value = data.messages ?? [];
        await scrollToBottom();
    } catch {
        // Mantém a conversa atual em caso de falha ao carregar o histórico.
    }
}

onMounted(() => {
    void loadPrompts();
    void loadConversations();
});

watch(
    messages,
    () => {
        void scrollToBottom();
    },
    { deep: true },
);

function scrollToBottom(): Promise<void> {
    return nextTick(() => {
        const host = messagesHost.value;

        if (host) {
            host.scrollTop = host.scrollHeight;
        }
    });
}

function updateMessage(id: number, text: string): void {
    const message = messages.value.find((entry) => entry.id === id);

    if (message) {
        message.text = text;
    }
}

function stopGeneration(): void {
    abortController.value?.abort();
}

async function send(promptName: string | null = null): Promise<void> {
    const text = draft.value.trim();

    if (text === '' || loading.value) {
        return;
    }

    messages.value.push({ id: Date.now(), role: 'user', text });
    draft.value = '';
    loading.value = true;

    const assistantId = Date.now() + 1;
    messages.value.push({ id: assistantId, role: 'assistant', text: '' });
    let accumulated = '';
    abortController.value = new AbortController();

    try {
        const response = await fetch('/chat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'text/event-stream',
                ...jsonHeaders,
            },
            body: JSON.stringify({
                message: text,
                conversation_id: conversationId.value,
                stream: true,
                prompt: promptName,
            }),
            signal: abortController.value.signal,
        });

        if (!response.ok || !response.body) {
            throw new Error('Resposta inválida do servidor.');
        }

        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        while (true) {
            const { value, done } = await reader.read();

            if (done) {
                break;
            }

            buffer += decoder.decode(value, { stream: true });

            let separator: number;
            while ((separator = buffer.indexOf('\n\n')) !== -1) {
                const rawEvent = buffer.slice(0, separator);
                buffer = buffer.slice(separator + 2);

                for (const line of rawEvent.split('\n')) {
                    if (!line.startsWith('data:')) {
                        continue;
                    }

                    const data = line.slice(5).trim();

                    if (data === '' || data === '[DONE]') {
                        continue;
                    }

                    try {
                        const payload = JSON.parse(data);

                        if (payload.type === 'meta') {
                            conversationId.value =
                                payload.conversation_id ?? conversationId.value;
                        } else if (payload.type === 'token') {
                            accumulated += payload.content;
                            updateMessage(assistantId, accumulated);
                        } else if (payload.type === 'done') {
                            accumulated = payload.content ?? accumulated;
                            updateMessage(assistantId, accumulated);
                        }
                    } catch {
                        // Ignore malformed SSE payloads.
                    }
                }
            }
        }

        if (accumulated === '') {
            updateMessage(assistantId, 'Sem resposta.');
        }
    } catch (error) {
        const aborted = error instanceof DOMException && error.name === 'AbortError';

        if (! aborted) {
            updateMessage(assistantId, 'Erro ao obter resposta do assistente.');
        } else if (accumulated === '') {
            updateMessage(assistantId, 'Geração interrompida.');
        }
    } finally {
        loading.value = false;
        void loadConversations();
    }
}
</script>

<template>
    <div
        class="d-flex position-relative"
        style="height: calc(100dvh - 48px)"
    >
        <aside
            v-if="mdAndUp"
            class="d-flex flex-column border-e flex-shrink-0 bg-surface"
            style="width: 290px"
        >
            <ChatHistoryPanel
                class="flex-grow-1"
                style="min-height: 0"
                :conversations="conversations"
                :active-id="conversationId"
                @select="selectConversation"
                @create="startNewConversation"
            />
        </aside>

        <v-container
            class="flex-grow-1 d-flex flex-column px-0 pt-5 bg-background"
            fluid
            style="min-width: 0"
        >
            <div class="d-flex align-center ga-2 px-4 py-2 border-b">
                <v-btn
                    v-if="!mdAndUp"
                    icon="ti ti-layout-sidebar-left-expand"
                    variant="text"
                    size="small"
                    title="Histórico de conversas"
                    @click="historyDrawer = true"
                />
                <div class="flex-grow-1 text-truncate text-subtitle-1 font-weight-medium">
                    {{ currentTitle }}
                </div>
                <v-btn
                    icon="ti ti-message-plus"
                    variant="text"
                    size="small"
                    title="Nova conversa"
                    @click="startNewConversation"
                />
            </div>

            <div
                ref="messagesHost"
                class="flex-grow-1 overflow-y-auto pa-4"
                style="min-height: 0"
            >
                <div
                    v-if="messages.length === 0"
                    class="d-flex flex-column align-center justify-center text-center ga-3 fill-height"
                >
                    <v-icon
                        icon="ti ti-messages"
                        size="44"
                        color="primary"
                    />
                    <div class="text-h6">Nenhuma conversa ainda</div>
                    <p
                        class="text-medium-emphasis"
                        style="max-width: 420px"
                    >
                        Assistente que lê dados e executa ações permitidas à sua
                        conta (clientes, vendas, recebimentos, entre outros),
                        conforme as ferramentas do servidor MCP.
                    </p>
                </div>

                <template
                    v-for="message in messages"
                    :key="message.id"
                >
                    <div
                        class="d-flex mb-3"
                        :class="
                            message.role === 'user'
                                ? 'justify-end'
                                : 'justify-start'
                        "
                    >
                        <div
                            class="pa-3 chat-bubble"
                            :class="
                                message.role === 'user'
                                    ? 'chat-bubble-user bg-primary text-on-primary'
                                    : 'chat-bubble-assistant'
                            "
                        >
                            {{ message.text }}
                        </div>
                    </div>
                </template>

                <div
                    v-if="awaitingReply"
                    class="d-flex mb-3 justify-start"
                >
                    <div class="pa-3 chat-bubble chat-bubble-assistant d-inline-flex align-center ga-1">
                        <span class="typing-dot" />
                        <span class="typing-dot" />
                        <span class="typing-dot" />
                    </div>
                </div>
            </div>

            <div
                v-if="prompts.length > 0"
                class="px-4 pt-2"
            >
                <div class="d-flex flex-wrap ga-2">
                    <template
                        v-for="prompt in prompts"
                        :key="prompt.name"
                    >
                        <v-btn
                            variant="tonal"
                            color="primary"
                            size="small"
                            rounded="sm"
                            :disabled="loading"
                            :title="prompt.description"
                            @click="applyPrompt(prompt)"
                        >
                            {{ prompt.label }}
                        </v-btn>
                        <v-btn
                            v-if="prompt.client_message"
                            icon="ti ti-copy"
                            variant="text"
                            size="x-small"
                            rounded="sm"
                            :disabled="loading"
                            title="Copiar mensagem para cliente"
                            @click="copyClientMessage(prompt)"
                        />
                    </template>
                </div>
            </div>

            <div class="d-flex align-center ga-3 px-4 py-3">
                <v-text-field
                    v-model="draft"
                    label="Escreva uma mensagem"
                    variant="outlined"
                    hide-details
                    density="comfortable"
                    rounded="lg"
                    @keyup.enter="send"
                />
                <v-btn
                    v-if="!loading"
                    color="primary"
                    icon="ti ti-send"
                    rounded="lg"
                    :disabled="draft.trim() === ''"
                    title="Enviar"
                    @click="send"
                />
                <v-btn
                    v-else
                    color="error"
                    icon="ti ti-player-stop"
                    rounded="lg"
                    title="Interromper geração"
                    @click="stopGeneration"
                />
            </div>
        </v-container>

        <v-fade-transition>
            <div
                v-if="!mdAndUp && historyDrawer"
                class="position-absolute top-0 left-0 w-100 fill-height"
                style="background: rgba(0, 0, 0, 0.45)"
                @click="historyDrawer = false"
            />
        </v-fade-transition>

        <v-slide-x-transition>
            <aside
                v-if="!mdAndUp && historyDrawer"
                class="d-flex flex-column border-e flex-shrink-0 position-absolute top-0 bottom-0 left-0 bg-surface"
                style="width: 290px"
            >
                <ChatHistoryPanel
                    class="flex-grow-1"
                    style="min-height: 0"
                    :conversations="conversations"
                    :active-id="conversationId"
                    @select="selectConversation"
                    @create="startNewConversation"
                />
            </aside>
        </v-slide-x-transition>
    </div>

    <v-snackbar
        v-model="snackbar"
        :timeout="2000"
        location="bottom"
    >
        {{ snackbarText }}
    </v-snackbar>
</template>

<style scoped>
.chat-bubble {
    max-width: min(78%, 720px);
    border-radius: 12px;
    white-space: pre-wrap;
    word-break: break-word;
}

.chat-bubble-user {
    border-bottom-right-radius: 4px;
}

.chat-bubble-assistant {
    background: rgba(var(--v-theme-on-surface), 0.07);
    border-bottom-left-radius: 4px;
}

.typing-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.4;
    animation: typing-pulse 1.2s infinite ease-in-out;
}

.typing-dot:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing-pulse {
    0%,
    100% {
        opacity: 0.25;
        transform: translateY(0);
    }

    50% {
        opacity: 0.9;
        transform: translateY(-2px);
    }
}
</style>
