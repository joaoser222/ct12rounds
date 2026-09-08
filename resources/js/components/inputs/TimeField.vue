<template>
    <v-text-field
        v-model="inputValue"
        v-bind="dynamicProps"
        @blur="handleInput"
    >
        <template #append-inner>
            <v-menu
                location="end"
                :close-on-content-click="false"
                v-model="timePickerMenu"
            >
                <template v-slot:activator="{ props }">
                    <v-btn
                        icon="ti ti-clock"
                        v-bind="props"
                        size="sm"
                        tabindex="-1"
                    ></v-btn>
                </template>
                <v-time-picker
                    :model-value="pickerValue"
                    elevation="24"
                    color="primary"
                    @update:model-value="timePickerInput"
                ></v-time-picker>
            </v-menu>
        </template>
    </v-text-field>
</template>

<script setup lang="ts">
import { ref, watch, computed, useAttrs } from 'vue';
import moment from '@/plugins/moment';

/**
 * Time field with manual input and `v-time-picker`.
 *
 * The user sees `formatDisplay`, while the `v-model` always emits in
 * `formatOutput`, keeping the value consistent for persistence.
 */
interface Props {
    modelValue?: string;
    formatDisplay?: string;
    formatOutput?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    formatDisplay: 'HH:mm',
    formatOutput: 'HH:mm:ss',
});

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const inputValue = ref<string>('');
const timePickerMenu = ref<boolean>(false);

const attrs = useAttrs();

const dynamicProps = computed<Record<string, unknown>>(() => {
    return {
        ...attrs,
    };
});

const pickerValue = computed<string | undefined>(() => {
    if (!props.modelValue) {
        return undefined;
    }

    const momentObj = moment(props.modelValue, props.formatOutput);

    if (!momentObj.isValid()) {
        return undefined;
    }

    return momentObj.format('HH:mm');
});

// The picker always emits in the output format, while the input remains user-friendly.
function timePickerInput(time: string | null): void {
    if (!time) {
        return;
    }

    const formatted = moment(time, 'HH:mm').format(props.formatOutput);

    inputValue.value = formatToDisplay(formatted);
    emit('update:modelValue', formatted);
    timePickerMenu.value = false;
}

function formatToDisplay(time: string): string {
    const momentObj = moment(time, props.formatOutput);

    if (momentObj.isValid()) {
        return momentObj.format(props.formatDisplay);
    }

    inputValue.value = '';
    return '';
}

function formatToOutput(time: string): string {
    const momentObj = moment(time, props.formatDisplay);

    if (momentObj.isValid()) {
        return momentObj.format(props.formatOutput);
    }

    inputValue.value = '';
    return '';
}

watch(
    () => props.modelValue,
    (newVal) => {
        inputValue.value = newVal ? formatToDisplay(newVal) : '';
    },
    { immediate: true },
);

// Normalization on blur prevents emitting partial values during typing.
function handleInput(): void {
    emit('update:modelValue', formatToOutput(inputValue.value));
}
</script>
