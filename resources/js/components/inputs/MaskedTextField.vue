<template>
    <v-text-field
        v-bind="$attrs"
        :model-value="displayValue"
        @update:model-value="updateValue"
    >
        <template v-for="(_, name) in $slots" #[name]="slotProps">
            <slot :name="name" v-bind="slotProps ?? {}" />
        </template>
    </v-text-field>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { applyMask, unmaskValue, type UnmaskMode } from '@/plugins/masks';

/**
 * Lightweight wrapper around `v-text-field` for masked values.
 *
 * `displayValue` controls only the display; the emitted value depends on
 * `unmask`, allowing masked or clean text to be persisted in `v-model`.
 */
defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        mask?: string;
        unmask?: UnmaskMode;
        limitToMask?: boolean;
    }>(),
    {
        modelValue: '',
        mask: '',
        unmask: 'mask',
        limitToMask: true,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// `displayValue` controls only what the user sees; the emitted value may be masked or clean.
const displayValue = computed(() =>
    applyMask(props.modelValue, props.mask, props.unmask),
);

function updateValue(value: string | null): void {
    emit(
        'update:modelValue',
        unmaskValue(value, props.mask, props.unmask, props.limitToMask),
    );
}
</script>
