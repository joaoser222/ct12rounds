<template>
    <v-text-field
        v-bind="$attrs"
        v-model="internalValue"
        :type="showPassword ? 'text' : 'password'"
        @input="onInput"
        @update:model-value="onUpdateModelValue"
    >
        <template #append-inner>
            <v-btn
                :icon="showPassword ? 'eye-off' : 'eye'"
                tabindex="-1"
                @click="togglePasswordVisibility()"
                size="sm"
            >
            </v-btn>
        </template>
    </v-text-field>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';

/**
 * Wrapper around `v-text-field` for password fields.
 *
 * Keeps a `v-model`-compatible API and adds only the visibility toggle,
 * avoiding repetition of this logic across pages.
 */
interface Props {
    modelValue?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
    (e: 'input', event: Event): void;
}>();

const internalValue = ref<string>(props.modelValue ?? '');
const showPassword = ref(false);

// Keeps the local value in sync when the form is updated externally.
watch(
    () => props.modelValue,
    (newValue) => {
        internalValue.value = newValue ?? '';
    },
);

function togglePasswordVisibility(): void {
    showPassword.value = !showPassword.value;
}

function onInput(event: Event): void {
    emit('input', event);
}

function onUpdateModelValue(value: string): void {
    emit('update:modelValue', value);
}
</script>
