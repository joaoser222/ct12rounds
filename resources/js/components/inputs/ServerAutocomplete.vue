<template>
    <v-autocomplete
        :model-value="normalizedModelValue"
        v-model:search="searchQuery"
        :items="displayedOptions"
        :loading="isLoading"
        item-title="label"
        item-value="value"
        no-filter
        v-bind="$attrs"
        @update:model-value="emitSelection($event)"
    />
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * Autocomplete with remote search on the `/select-box/{objectName}` endpoint.
 *
 * Flow:
 * - loads initial options with an empty search on mount;
 * - applies a 300ms debounce on typing;
 * - aborts previous requests to avoid race conditions.
 *
 * Main props:
 * - `objectName`: resource queried on the backend;
 * - `limit`: maximum options per request;
 * - `modelValue`: selected value from `v-model`.
 */
type SelectOption = {
    value: string;
    label: string;
} & Record<string, unknown>;

const props = withDefaults(
    defineProps<{
        modelValue?: string | number | null;
        objectName: string;
        limit?: number;
    }>(),
    {
        modelValue: null,
        limit: 15,
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', value: string | number | null): void;
    (e: 'selected-item', value: SelectOption | null): void;
}>();

const searchQuery = ref('');
const options = ref<SelectOption[]>([]);
const isLoading = ref(false);

const normalizedModelValue = computed<string | null>(() => {
    return props.modelValue == null ? null : String(props.modelValue);
});

const displayedOptions = computed<SelectOption[]>(() => {
    const selected = selectedValue();

    if (selected === '') {
        return options.value;
    }

    const prioritizedOptions = [...options.value].sort((first, second) => {
        if (first.value === selected) {
            return -1;
        }

        if (second.value === selected) {
            return 1;
        }

        return 0;
    });

    return prioritizedOptions.filter((option, index, items) => {
        return items.findIndex((item) => item.value === option.value) === index;
    });
});

const selectedOption = computed<SelectOption | undefined>(() => {
    const selected = selectedValue();

    if (selected === '') {
        return undefined;
    }

    return displayedOptions.value.find((option) => option.value === selected);
});

let searchTimeout: ReturnType<typeof setTimeout> | null = null;
let activeRequestController: AbortController | null = null;

function selectedValue(): string {
    return props.modelValue == null ? '' : String(props.modelValue);
}

function normalizedSearchQuery(query: string): string {
    if (selectedOption.value && query === selectedOption.value.label) {
        return '';
    }

    return query;
}

function emitSelection(value: string | number | null): void {
    emit('update:modelValue', value);

    if (value == null) {
        emit('selected-item', null);

        return;
    }

    const selected = displayedOptions.value.find((option) => option.value === String(value));

    emit('selected-item', selected ?? null);
}

// Runs the search immediately and ensures only the most recent response updates the state.
function runFetch(query: string): void {
    activeRequestController?.abort();

    const requestController = new AbortController();

    activeRequestController = requestController;
    if (searchTimeout) {
        clearTimeout(searchTimeout);
        searchTimeout = null;
    }

    isLoading.value = true;

    const params = new URLSearchParams({
        search: query,
        limit: String(props.limit),
    });

    const selected = selectedValue();

    if (selected !== '') {
        params.set('selected', selected);
    }

    fetch(`/select-box/${props.objectName}?${params}`, {
            signal: requestController.signal,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
            .then((res) => res.json())
            .then((data: SelectOption[]) => {
                if (activeRequestController === requestController) {
                    options.value = data;
                }
            })
            .catch((error: unknown) => {
                if (error instanceof DOMException && error.name === 'AbortError') {
                    return;
                }

                throw error;
            })
            .finally(() => {
                if (activeRequestController === requestController) {
                    isLoading.value = false;
                    activeRequestController = null;
                }
            });
}

// Uses debounce on typed searches and allows immediate loading on initial mount.
function fetchOptions(query: string, immediate = false): void {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    if (immediate) {
        runFetch(query);

        return;
    }

    searchTimeout = setTimeout(() => {
        runFetch(query);
    }, 300);
}

watch(searchQuery, (value) => {
    fetchOptions(normalizedSearchQuery(value ?? ''));
});

watch(
    () => props.modelValue,
    (value) => {
        if (value == null) {
            return;
        }

        const valueAsString = String(value);

        if (options.value.some((option) => option.value === valueAsString)) {
            return;
        }

        fetchOptions(searchQuery.value ?? '', true);
    },
);

onMounted(() => {
    fetchOptions('', true);
});

onBeforeUnmount(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    activeRequestController?.abort();
});
</script>
