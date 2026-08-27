<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    contracts: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Plano', key: 'plan_name', sortable: true, searchable: true },
    { title: 'Total', key: 'total', sortable: true },
    { title: '1o vencimento', key: 'first_due_date', sortable: true },
    { title: 'Parcelas', key: 'installments' },
    { title: 'Status', key: 'status', sortable: true, align: 'center' },
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
const { billableStatus } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="contracts.data"
        :total="contracts.total"
        :current-page="contracts.current_page"
        :last-page="contracts.last_page"
        :per-page="contracts.per_page"
        :headers="headers"
        :routes="routes"
        module="contracts"
        title="Contratos"
        :custom-slots="['created_at', 'total', 'first_due_date', 'status']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-total="{ item }">
            {{ formatCurrency(item.total) }}
        </template>
        <template #column-first_due_date="{ item }">
            {{ formatDate(item.first_due_date) }}
        </template>
        <template #column-status="{ item }">
            <v-chip v-if="item.accepted_terms === 'pending'" color="warning">
                Pendente
            </v-chip>
            <v-chip v-else :color="findOption(billableStatus, item.status)?.color">
                {{ findLabel(billableStatus, item.status) }}
            </v-chip>
        </template>
    </TablePage>
</template>
