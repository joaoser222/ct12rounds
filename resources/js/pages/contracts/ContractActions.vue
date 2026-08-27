<script setup lang="ts">
defineProps<{
    processing?: boolean;
    showSave?: boolean;
    showApply?: boolean;
    showCancel?: boolean;
}>();

defineEmits<{
    back: [];
    save: [];
    apply: [];
    cancel: [];
}>();
</script>

<template>
    <v-divider />
    <v-card-actions class="pa-4 d-flex justify-space-between flex-wrap ga-2">
        <v-clipped-button color="secondary" prepend-icon="ti ti-arrow-left" :disabled="processing" @click="$emit('back')">
            Voltar
        </v-clipped-button>

        <div class="flex-grow-1"></div>
        <v-clipped-button v-if="showApply" color="success" prepend-icon="ti ti-user-check" :loading="processing" @click="$emit('apply')">
            Aplicar contrato
        </v-clipped-button>
        <v-clipped-button v-if="showSave" color="primary" prepend-icon="ti ti-device-floppy" :loading="processing" @click="$emit('save')">
            Salvar
        </v-clipped-button>
        <v-clipped-button v-if="showCancel" color="error" prepend-icon="ti ti-x" :disabled="processing" @click="$emit('cancel')">
            Cancelar contrato
        </v-clipped-button>
    </v-card-actions>
</template>