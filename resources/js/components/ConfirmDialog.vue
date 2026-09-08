<template>
    <v-dialog :model-value="queue.length > 0" max-width="460" persistent>
        <v-card v-if="queue.length > 0">
            <v-card-title class="d-flex align-center ga-2">
                <v-icon icon="ti ti-help" color="warning" />
                {{ queue[0].title ?? 'Confirmar' }}
            </v-card-title>
            <v-card-text>
                {{ queue[0].message }}
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn variant="text" @click="handleCancel">Cancelar</v-btn>
                <v-btn color="warning" variant="flat" @click="handleConfirm">Confirmar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script setup lang="ts">
import { useConfirm } from '@/composables/useConfirm';

const { queue, handleConfirm, handleCancel } = useConfirm();
</script>
