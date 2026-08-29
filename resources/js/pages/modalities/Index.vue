<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    modalities: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Ícone', key: 'icon', sortable: false },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Cor', key: 'color', sortable: false },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    create: props.routes.create,
    show: props.routes.show,
    changeVisibility: props.routes.changeVisibility,
    destroy: props.routes.destroy,
};
</script>

<template>
    <TablePage
        :items="modalities.data"
        :total="modalities.total"
        :current-page="modalities.current_page"
        :last-page="modalities.last_page"
        :per-page="modalities.per_page"
        :headers="headers"
        :routes="routes"
        module="modalities"
        title="Modalidades"
        :custom-slots="['icon', 'color', 'created_at']"
    >
        <template #column-icon="{ item }">
            <v-img
                v-if="item.icon_url"
                :src="item.icon_url"
                width="32"
                height="32"
                cover
                rounded
            />
            <span v-else class="text-caption text-medium-emphasis">—</span>
        </template>
        <template #column-color="{ item }">
            <span
                v-if="item.color"
                class="d-inline-block rounded-circle"
                :style="{
                    backgroundColor: item.color,
                    width: '20px',
                    height: '20px',
                }"
            />
            <span v-else class="text-caption text-medium-emphasis">—</span>
        </template>
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
    </TablePage>
</template>
