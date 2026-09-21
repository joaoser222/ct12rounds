<!-- resources/js/pages/modality_categories/Index.vue -->
<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    modalityCategories: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Slug', key: 'slug', sortable: true },
    { title: 'Público', key: 'audience', sortable: true },
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
        :items="modalityCategories.data"
        :total="modalityCategories.total"
        :current-page="modalityCategories.current_page"
        :last-page="modalityCategories.last_page"
        :per-page="modalityCategories.per_page"
        :headers="headers"
        :routes="routes"
        module="modality_categories"
        title="Categorias de Modalidades"
        :custom-slots="['audience', 'created_at']"
    >
        <template #column-audience="{ item }">
            <v-chip :color="item.audience === 'adult' ? 'primary' : 'success'" size="small">
                {{ item.audience === 'adult' ? 'Adulto' : 'Infantil' }}
            </v-chip>
        </template>
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
    </TablePage>
</template>
