<template>
    <div>
        <v-text-field
            v-bind="$attrs"
            :model-value="displayValue"
            readonly
            @click="openDialog"
        >
            <template #prepend-inner>
                <div
                    class="color-preview"
                    :style="{
                        backgroundColor: modelValue || '#ffffff',
                        width: '24px',
                        height: '24px',
                        borderRadius: '4px',
                        border: '1px solid #ddd',
                        cursor: 'pointer',
                    }"
                    @click="openDialog"
                />
            </template>
            <template #append-inner>
                <v-btn
                    icon="ti ti-dots"
                    variant="text"
                    size="small"
                    @click="openDialog"
                />
            </template>
        </v-text-field>

        <v-dialog v-model="dialog" max-width="400px" scrollable>
            <v-card>
                <v-card-title class="d-flex justify-space-between align-center">
                    <span>Selecionar Cor</span>
                    <v-btn
                        icon="mdi-close"
                        variant="text"
                        size="small"
                        @click="closeDialog"
                    />
                </v-card-title>

                <v-card-text class="pa-4">
                    <v-color-picker
                        v-model="internalValue"
                        mode="hex"
                        show-swatches
                        hide-inputs
                        @update:model-value="updateColor"
                        class="w-100"
                    />

                    <v-text-field
                        v-model="internalValue"
                        label="Código da cor"
                        class="mt-4 w-100"
                        readonly
                    />

                    <div class="color-preview-large mt-3">
                        <div
                            :style="{
                                backgroundColor: internalValue || '#ffffff',
                                width: '100%',
                                height: '60px',
                                borderRadius: '8px',
                                border: '2px solid #ddd',
                            }"
                        />
                    </div>
                </v-card-text>

                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="closeDialog"> Cancelar </v-btn>
                    <v-btn color="primary" @click="confirmColor">
                        Confirmar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';

/**
 * Color field with inline preview and dialog-based picker.
 *
 * The external value is only updated on `confirmColor`, allowing testing the color
 * in the dialog without persisting partial changes.
 */
const props = withDefaults(
    defineProps<{
        modelValue?: string;
    }>(),
    {
        modelValue: '#ffffff',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// `internalValue` allows editing/canceling within the dialog without immediately changing the external value.
const dialog = ref<boolean>(false);
const internalValue = ref<string>(props.modelValue);
const originalValue = ref<string>(props.modelValue);

const displayValue = computed<string>(() => {
    return props.modelValue || '#ffffff';
});

watch(
    () => props.modelValue,
    (newVal: string | undefined) => {
        internalValue.value = newVal || '#ffffff';
        originalValue.value = newVal || '#ffffff';
    },
    { immediate: true },
);

// On open, the component stores a snapshot to allow restoration on cancel.
function openDialog(): void {
    originalValue.value = props.modelValue || '#ffffff';
    internalValue.value = props.modelValue || '#ffffff';
    dialog.value = true;
}

function closeDialog(): void {
    // Restores the original value if canceled.
    internalValue.value = originalValue.value;
    dialog.value = false;
}

function confirmColor(): void {
    emit('update:modelValue', internalValue.value);
    dialog.value = false;
}

function updateColor(color: string): void {
    internalValue.value = color;
}

// Exposed for integrations that need to open the picker programmatically.
defineExpose({
    openDialog,
    closeDialog,
    confirmColor,
});
</script>

<style scoped>
.color-preview {
    cursor: pointer;
    transition: transform 0.2s ease;
}

.color-preview:hover {
    transform: scale(1.1);
}

.color-preview-large {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
