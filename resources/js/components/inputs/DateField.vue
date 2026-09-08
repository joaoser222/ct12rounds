<template>
    <v-text-field
        v-model="inputValue"
        v-bind="dynamicProps"
        v-maska="'##/##/####'"
        @blur="handleInput"
    >
        <template #append-inner>
            <v-menu
                location="end"
                :close-on-content-click="false"
                v-model="datePickerMenu"
            >
                <template v-slot:activator="{ props }">
                    <v-btn
                        icon="ti ti-calendar"
                        v-bind="props"
                        size="sm"
                        tabindex="-1"
                    ></v-btn>
                </template>
                <v-date-picker
                    :model-value="pickerValue"
                    elevation="24"
                    color="primary"
                    @update:model-value="datePickerInput"
                ></v-date-picker>
            </v-menu>
        </template>
    </v-text-field>
</template>

<script setup lang="ts">
import { ref, watch, computed, useAttrs } from 'vue';
import moment from '@/plugins/moment';

/**
 * Date field with manual input and `v-date-picker`.
 *
 * The component converts between a user-friendly display format
 * (`formatDisplay`) and the expected persistence format (`formatOutput`).
 */
const props = defineProps<{
    modelValue?: string;
    formatDisplay?: string;
    formatOutput?: string;
}>();

// The formats follow safe defaults but can be adapted per screen.
const formatDisplay = props.formatDisplay ?? 'DD/MM/YYYY';
const formatOutput = props.formatOutput ?? 'YYYY-MM-DD';

// Emits
const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

// Estado
const inputValue = ref<string>('');
const datePickerMenu = ref<boolean>(false);

// Atributos
const attrs = useAttrs();

// Props dinâmicas
const dynamicProps = computed(() => ({
    ...attrs,
}));

const pickerValue = computed<string | undefined>(() => {
    if (!props.modelValue) {
        return undefined;
    }

    const momentObj = moment(props.modelValue, formatOutput);

    if (!momentObj.isValid()) {
        return undefined;
    }

    return momentObj.format('YYYY-MM-DD');
});

// The helpers isolate conversion and cleanup when the input does not represent a valid date.
function formatToDisplay(date: string | undefined): string {
    if (!date) return '';

    const momentObj = moment(date, formatOutput);
    if (momentObj.isValid()) {
        return momentObj.format(formatDisplay);
    }

    inputValue.value = '';
    return '';
}

function formatToOutput(date: string): string {
    const momentObj = moment(date, formatDisplay);
    if (momentObj.isValid()) {
        return momentObj.format(formatOutput);
    }

    inputValue.value = '';
    return '';
}

// The picker works with a normalized value and emits in the format expected by the backend.
function datePickerInput(date: unknown): void {
    const momentObj =
        typeof date === 'string'
            ? moment(date)
            : date instanceof Date
              ? moment(date)
              : null;

    if (!momentObj || !momentObj.isValid()) {
        return;
    }

    const formatted = momentObj.format(formatOutput);

    inputValue.value = formatToDisplay(formatted);
    emit('update:modelValue', formatted);
    datePickerMenu.value = false;
}

// Keeps the text in sync when the external value changes via navigation or autofill.
watch(
    () => props.modelValue,
    (newVal: string | undefined) => {
        inputValue.value = newVal ? formatToDisplay(newVal) : '';
    },
    { immediate: true },
);

// Conversion happens on blur to avoid conflicts with user typing.
function handleInput(): void {
    const formatted = formatToOutput(inputValue.value);
    emit('update:modelValue', formatted);
}
</script>
