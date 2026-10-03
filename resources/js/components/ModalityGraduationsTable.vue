<template>
    <EditableRowsTable
        :items="items as Record<string, unknown>[]"
        :columns="columns"
        title="Graduações"
        description="Graduações possíveis para os clientes desta modalidade."
        add-label="Adicionar Graduação"
        empty-message="Nenhuma graduação cadastrada. Clique em Adicionar Graduação para continuar."
        @add="addItem"
        @remove="removeItem"
    >
        <template #row="{ item, index }">
            <td>
                <v-text-field
                    v-model="item.name"
                    v-text-case="'capitalize'"
                    label="Graduação"
                    hide-details="auto"
                    :rules="[required]"
                    :error-messages="itemError(index, 'name')"
                />
            </td>
            <td>
                <v-text-field
                    v-model="item.color"
                    label="Cor"
                    type="color"
                    hide-details="auto"
                    :error-messages="itemError(index, 'color')"
                />
            </td>
        </template>
    </EditableRowsTable>
</template>

<script setup lang="ts">
import { required } from '@/plugins/validators';

type ModalityGraduation = {
    id?: number;
    name?: string;
    color?: string;
};

type FormErrors = Record<string, string | undefined>;

const props = withDefaults(
    defineProps<{
        items?: ModalityGraduation[];
        errors?: FormErrors;
    }>(),
    {
        items: () => [],
        errors: () => ({}),
    },
);

const emit = defineEmits<{
    (e: 'update:items', value: ModalityGraduation[]): void;
}>();

const columns = [{ title: 'Graduação' }, { title: 'Cor', width: '120px' }];

function addItem(): void {
    emit('update:items', [...props.items, { name: '', color: '#ffffff' }]);
}

function removeItem(index: number): void {
    emit(
        'update:items',
        props.items.filter((_, itemIndex) => itemIndex !== index),
    );
}

function itemError(index: number, field: string): string | undefined {
    return props.errors[`graduations.${index}.${field}`];
}
</script>
