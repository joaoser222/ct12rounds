<!-- resources/js/Pages/Clients/Index.vue -->
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

// Page props
const props = defineProps<{
    financialCategories: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

// Table configuration
const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Centro de Custo', key: 'cost_center_name', sortable: true, searchable: true },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    create: props.routes.create,
    show: props.routes.show,
    changeVisibility: props.routes.changeVisibility,
    destroy: props.routes.destroy,
};


const sharedProps = usePage().props;

</script>

<template>
    <TablePage
        :items="financialCategories.data"
        :total="financialCategories.total"
        :current-page="financialCategories.current_page"
        :last-page="financialCategories.last_page"
        :per-page="financialCategories.per_page"
        :headers="headers"
        :routes="routes"
        module="financial_categories"
        title="Categorias Financeiras"
        :custom-slots="['created_at']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
    </TablePage>
</template>
