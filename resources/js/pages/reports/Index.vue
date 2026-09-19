<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import type { PaginatedResponse } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    reports: PaginatedResponse<any>;
    routes: Pick<TableRoutes, 'index' | 'show' | 'run'>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Rótulo', key: 'label', sortable: true, searchable: true },
    { title: 'Descrição', key: 'description', searchable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.run,
};
</script>

<template>
    <TablePage
        :items="reports.data"
        :total="reports.total"
        :current-page="reports.current_page"
        :last-page="reports.last_page"
        :per-page="reports.per_page"
        :headers="headers"
        :routes="routes"
        module="reports"
        title="Relatórios"
        hide-selection
        hide-visibility-filter
        open-on-row-click
        :permission-map="{ create: false, delete: false, visibility: false }"
        :permissions="[]"
    />
</template>
