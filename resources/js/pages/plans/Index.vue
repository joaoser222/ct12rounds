<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    plans: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Preço', key: 'price', sortable: true },
    { title: 'Duração', key: 'duration_months', sortable: true },
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
        :items="plans.data"
        :total="plans.total"
        :current-page="plans.current_page"
        :last-page="plans.last_page"
        :per-page="plans.per_page"
        :headers="headers"
        :routes="routes"
        module="plans"
        title="Planos"
        :custom-slots="['price', 'duration_months', 'created_at']"
    >
        <template #column-price="{ item }">
            {{ formatCurrency(item.price) }}
        </template>
        <template #column-duration_months="{ item }">
            {{ item.duration_months }} {{ item.duration_months === 1 ? 'mês' : 'meses' }}
        </template>
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
    </TablePage>
</template>
