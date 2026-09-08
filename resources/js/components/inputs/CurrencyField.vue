<template>
    <v-text-field
        ref="inputRef"
        :model-value="formattedValue ?? ''"
        v-bind="$attrs"
        :prefix="prefix"
    />
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import {
    CurrencyDisplay,
    useCurrencyInput,
    type CurrencyInputOptions,
} from 'vue-currency-input';

/**
 * Shared currency field based on `vue-currency-input`.
 *
 * The component displays the value formatted in pt-BR and keeps the `v-model`
 * synchronized with a clean number for persistence.
 */
const props = withDefaults(
    defineProps<{
        modelValue?: number | string | null;
        autoDecimalDigits?: boolean;
        prefix?: string;
        options?: CurrencyInputOptions;
    }>(),
    {
        modelValue: null,
        autoDecimalDigits: true,
        prefix: '',
        options: undefined,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: number | null): void;
}>();

const currencyOptions = computed<CurrencyInputOptions>(() => ({
    locale: 'pt-BR',
    currency: 'BRL',
    currencyDisplay: CurrencyDisplay.hidden,
    autoDecimalDigits: props.autoDecimalDigits,
    hideGroupingSeparatorOnFocus: false,
    hideNegligibleDecimalDigitsOnFocus: false,
    precision: 2,
    ...props.options,
}));

const { formattedValue, inputRef, numberValue, setOptions, setValue } = useCurrencyInput(
    currencyOptions.value,
    false,
);

watch(currencyOptions, (options) => {
    setOptions(options);
});

watch(numberValue, (value) => {
    if (value === null) {
        if (props.modelValue !== null && props.modelValue !== '') {
            emit('update:modelValue', null);
        }

        return;
    }

    if (Number(props.modelValue) !== value) {
        emit('update:modelValue', value);
    }
});

watch(
    () => props.modelValue,
    (value) => {
        if (value === null || value === undefined || value === '') {
            setValue(null);

            return;
        }

        const numberValue = Number(value);

        setValue(Number.isFinite(numberValue) ? numberValue : null);
    },
    { immediate: true },
);
</script>
