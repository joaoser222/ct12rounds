<template>
    <div>
        <div
            class="d-flex align-center justify-space-between mb-3 ga-3 flex-wrap"
        >
            <div>
                <h3 class="text-h6">{{ title }}</h3>
                <p class="text-body-2 text-medium-emphasis">
                    {{ description }}
                </p>
            </div>
            <v-clipped-button
                color="primary"
                prepend-icon="ti ti-plus"
                @click="$emit('add')"
            >
                {{ addLabel }}
            </v-clipped-button>
        </div>

        <v-alert
            v-if="items.length === 0"
            color="secondary"
            variant="tonal"
            border="start"
        >
            {{ emptyMessage }}
        </v-alert>

        <v-table
            v-else
            class="border-sm border-surface-variant editable-rows-table"
        >
            <thead>
                <tr>
                    <th v-if="reorderable" style="width: 72px"></th>
                    <th
                        v-for="(column, index) in columns"
                        :key="`${column.title}-${index}`"
                        :class="
                            column.align ? `text-${column.align}` : undefined
                        "
                        :style="
                            column.width ? { width: column.width } : undefined
                        "
                    >
                        {{ column.title }}
                    </th>
                    <th style="width: 72px"></th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(item, index) in items"
                    :key="resolveRowKey(item, index)"
                >
                    <td v-if="reorderable" class="reorder-cell">
                        <v-btn-icon
                            icon="ti ti-chevron-up"
                            variant="text"
                            size="small"
                            aria-label="Mover para cima"
                            :disabled="index === 0"
                            @click="$emit('move', index, -1)"
                        />
                        <v-btn-icon
                            icon="ti ti-chevron-down"
                            variant="text"
                            size="small"
                            aria-label="Mover para baixo"
                            :disabled="index === items.length - 1"
                            @click="$emit('move', index, 1)"
                        />
                    </td>
                    <slot
                        name="row"
                        :item="item"
                        :index="index"
                        :can-remove="items.length > minItems"
                    />
                    <td class="text-right">
                        <v-btn-icon
                            icon="ti ti-trash"
                            color="error"
                            size="small"
                            :disabled="items.length <= minItems"
                            @click="$emit('remove', index)"
                        />
                    </td>
                </tr>
            </tbody>
        </v-table>
    </div>
</template>

<script setup lang="ts">
type EditableRowsColumn = {
    title: string;
    width?: string;
    align?: 'left' | 'right' | 'center';
};

const props = withDefaults(
    defineProps<{
        items?: any[];
        columns?: EditableRowsColumn[];
        title?: string;
        description?: string;
        addLabel?: string;
        emptyMessage?: string;
        minItems?: number;
        rowKeyField?: string;
        /** Renders up/down buttons and emits `move(index, offset)`. */
        reorderable?: boolean;
    }>(),
    {
        items: () => [],
        columns: () => [],
        title: 'Itens',
        description: '',
        addLabel: 'Adicionar Item',
        emptyMessage: 'Nenhum item adicionado.',
        minItems: 0,
        rowKeyField: 'id',
        reorderable: false,
    },
);

defineEmits<{
    (e: 'add'): void;
    (e: 'remove', index: number): void;
    (e: 'move', index: number, offset: number): void;
}>();

function resolveRowKey(item: any, index: number): string | number {
    const value = item[props.rowKeyField];

    return typeof value === 'string' || typeof value === 'number'
        ? value
        : `new-${index}`;
}
</script>

<style scoped>
.editable-rows-table :deep(th),
.editable-rows-table :deep(td) {
    padding-top: 16px !important;
    padding-bottom: 16px !important;
}

.editable-rows-table :deep(td) {
    vertical-align: top;
}

.reorder-cell {
    white-space: nowrap;
}
</style>
