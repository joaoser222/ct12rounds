<template>
    <EditableRowsTable
        :items="items as Record<string, unknown>[]"
        :columns="columns"
        title="Graduações"
        description="Vincule um nível de graduação e a modalidade correspondente ao cliente. Use Promover para avançar o cliente para a próxima graduação da modalidade."
        add-label="Adicionar Graduação"
        empty-message="Nenhuma graduação adicionada. Clique em Adicionar Graduação para continuar."
        @add="addItem"
        @remove="removeItem"
    >
        <template #row="{ item, index }">
            <td>
                <v-select
                    v-model="item.modality_id"
                    :items="modalityOptions"
                    item-title="title"
                    item-value="value"
                    label="Modalidade"
                    hide-details="auto"
                    :error-messages="itemError(index, 'modality_id')"
                    @update:model-value="clearGraduation(item)"
                />
            </td>
            <td>
                <v-select
                    v-model="item.modality_graduation_id"
                    :items="graduationOptionsFor(item.modality_id)"
                    item-title="label"
                    item-value="value"
                    label="Graduação"
                    hide-details="auto"
                    :disabled="!item.modality_id"
                    :error-messages="itemError(index, 'modality_graduation_id')"
                >
                    <template #item="{ props: optionProps, item: option }">
                        <v-list-item
                            v-bind="optionProps"
                            :title="option.raw.label"
                        >
                            <template #prepend>
                                <span
                                    class="graduation-swatch"
                                    :style="{
                                        backgroundColor:
                                            option.raw.color || 'transparent',
                                    }"
                                />
                            </template>
                        </v-list-item>
                    </template>
                </v-select>
            </td>
            <td>
                <v-text-field
                    v-model="item.promoted_at"
                    label="Data da promoção"
                    type="date"
                    hide-details="auto"
                    :error-messages="itemError(index, 'promoted_at')"
                />
            </td>
            <td>
                <v-btn
                    color="primary"
                    variant="tonal"
                    size="small"
                    prepend-icon="ti ti-chevrons-up"
                    :disabled="!nextGraduation(item)"
                    @click="promote(item)"
                >
                    Promover
                </v-btn>
            </td>
        </template>
    </EditableRowsTable>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useToast } from '@/composables/useToast';

type ClientGraduation = {
    modality_id?: string | number | null;
    modality_graduation_id?: string | number | null;
    promoted_at?: string | null;
};

type GraduationOption = {
    value: string;
    label: string;
    color?: string | null;
    modality_id: string;
    modality_name: string;
    position: number;
};

type FormErrors = Record<string, string | undefined>;

const props = withDefaults(
    defineProps<{
        items?: ClientGraduation[];
        options?: GraduationOption[];
        errors?: FormErrors;
    }>(),
    {
        items: () => [],
        options: () => [],
        errors: () => ({}),
    },
);

const emit = defineEmits<{
    (e: 'update:items', value: ClientGraduation[]): void;
}>();

const { show } = useToast();

const columns = [
    { title: 'Modalidade' },
    { title: 'Graduação' },
    { title: 'Data da promoção', width: '180px' },
    { title: 'Promoção', width: '150px' },
];

const modalityOptions = computed(() => {
    const seen = new Set<string>();

    return props.options.reduce<{ title: string; value: string }[]>(
        (accumulator, option) => {
            if (seen.has(option.modality_id)) {
                return accumulator;
            }

            seen.add(option.modality_id);
            accumulator.push({
                title: option.modality_name,
                value: option.modality_id,
            });

            return accumulator;
        },
        [],
    );
});

function graduationOptionsFor(modalityId: unknown) {
    if (modalityId == null || modalityId === '') {
        return [];
    }

    return props.options.filter(
        (option) => option.modality_id === String(modalityId),
    );
}

function nextGraduation(item: ClientGraduation): GraduationOption | null {
    const options = graduationOptionsFor(item.modality_id);

    const currentIndex = options.findIndex(
        (option) => option.value === String(item.modality_graduation_id),
    );

    if (currentIndex === -1) {
        return null;
    }

    return options[currentIndex + 1] ?? null;
}

function promote(item: ClientGraduation): void {
    const next = nextGraduation(item);

    if (!next) {
        return;
    }

    item.modality_graduation_id = next.value;
    item.promoted_at = new Date().toISOString().slice(0, 10);

    emit(
        'update:items',
        props.items.map((entry) => ({ ...entry })),
    );

    show({
        type: 'success',
        message: `Promovido para ${next.label}. Salve para confirmar.`,
    });
}

function addItem(): void {
    const pendingIndex = props.items.findIndex(
        (item) => !item.modality_graduation_id,
    );

    if (pendingIndex !== -1) {
        show({
            type: 'warning',
            message:
                'Preencha a graduação do item atual antes de adicionar outro.',
        });

        return;
    }

    emit('update:items', [
        ...props.items,
        { modality_id: null, modality_graduation_id: null, promoted_at: null },
    ]);
}

function removeItem(index: number): void {
    emit(
        'update:items',
        props.items.filter((_, itemIndex) => itemIndex !== index),
    );
}

function clearGraduation(item: ClientGraduation): void {
    item.modality_graduation_id = null;
    item.promoted_at = null;
    emit(
        'update:items',
        props.items.map((entry) => ({ ...entry })),
    );
}

function itemError(index: number, field: string): string | undefined {
    return props.errors[`graduations.${index}.${field}`];
}
</script>

<style scoped>
.graduation-swatch {
    display: inline-block;
    width: 14px;
    height: 14px;
    border-radius: 4px;
    border: 1px solid rgb(var(--v-border-color));
}
</style>
