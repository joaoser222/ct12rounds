<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatCurrency, formatDateTime } from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';
import GatewaySyncButton from '@/components/gateway/GatewaySyncButton.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    gatewayTransfers: PaginatedResponse<any>;
    routes: Pick<TableRoutes, 'index' | 'create' | 'show'>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Referência', key: 'gateway_reference_key', searchable: true },
    { title: 'Bruto', key: 'gross_value', sortable: true },
    { title: 'Taxa', key: 'fee_value', sortable: true },
    { title: 'Total', key: 'total', sortable: true },
    {
        title: 'Status',
        key: 'status',
        sortable: true,
        align: 'center',
        searchable: {
            component: 'VSelect',
            props: () => ({ items: transactionStatus }),
        },
    },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    create: props.routes.create,
    show: props.routes.show,
};

const sharedProps = usePage().props;
const { transactionStatus } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="gatewayTransfers.data"
        :total="gatewayTransfers.total"
        :current-page="gatewayTransfers.current_page"
        :last-page="gatewayTransfers.last_page"
        :per-page="gatewayTransfers.per_page"
        :headers="headers"
        :routes="routes"
        module="gateway_transfers"
        title="Transferências do Gateway"
        hide-selection
        hide-visibility-filter
        :permission-map="{ delete: false, visibility: false }"
        :custom-slots="[
            'gross_value',
            'fee_value',
            'total',
            'status',
            'created_at',
        ]"
    >
        <template #toolbar-actions>
            <GatewaySyncButton scope="transfers" />
        </template>
        <template #column-gross_value="{ item }">
            {{ formatCurrency(item.gross_value) }}
        </template>
        <template #column-fee_value="{ item }">
            {{ formatCurrency(item.fee_value) }}
        </template>
        <template #column-total="{ item }">
            {{ formatCurrency(item.total) }}
        </template>
        <template #column-status="{ item }">
            <v-chip :color="findOption(transactionStatus, item.status)?.color">
                {{ findLabel(transactionStatus, item.status) ?? item.status }}
            </v-chip>
        </template>
        <template #column-created_at="{ item }">
            {{ formatDateTime(item.created_at) }}
        </template>
    </TablePage>
</template>
