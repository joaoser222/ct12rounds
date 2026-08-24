<script setup lang="ts">
import { formatDate } from '@/plugins/formatters';

type ConversationSummary = {
    id: number;
    title: string | null;
    updated_at: string;
};

defineProps<{
    conversations: ConversationSummary[];
    activeId: number | null;
}>();

const emit = defineEmits<{
    select: [conversation: ConversationSummary];
    create: [];
}>();
</script>

<template>
    <div class="d-flex flex-column py-5">
        <div class="pa-4 pb-3">
            <v-btn
                block
                color="primary"
                prepend-icon="ti ti-plus"
                rounded="lg"
                @click="emit('create')"
            >
                Nova conversa
            </v-btn>
        </div>

        <div class="flex-grow-1 overflow-y-auto py-2">
            <p
                v-if="conversations.length === 0"
                class="text-caption text-medium-emphasis text-center pa-4"
            >
                Nenhuma conversa anterior.
            </p>
            <v-list
                v-else
                lines="two"
                density="compact"
                nav
                class="px-2"
            >
                <v-list-item
                    v-for="conversation in conversations"
                    :key="conversation.id"
                    :active="conversation.id === activeId"
                    prepend-icon="ti ti-message-circle"
                    :title="conversation.title ?? 'Sem título'"
                    :subtitle="formatDate(conversation.updated_at)"
                    @click="emit('select', conversation)"
                />
            </v-list>
        </div>
    </div>
</template>
