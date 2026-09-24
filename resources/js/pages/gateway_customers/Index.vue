<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDateTime } from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';
import GatewaySyncButton from '@/components/gateway/GatewaySyncButton.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    gatewayCustomers: PaginatedResponse<any>;
    routes: Pick<TableRoutes, 'index' | 'show'>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Referência', key: 'gateway_reference_key', searchable: true },
    { title: 'Tipo', key: 'holder_type', sortable: true, searchable: true },
    { title: 'Titular', key: 'holder_name' },
    { title: 'Conta', key: 'gateway_account_id' },
    { title: 'Postback', key: 'gateway_postback_id' },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.show,
};
</script>

<template>
    <TablePage
        :items="gatewayCustomers.data"
        :total="gatewayCustomers.total"
        :current-page="gatewayCustomers.current_page"
        :last-page="gatewayCustomers.last_page"
        :per-page="gatewayCustomers.per_page"
        :headers="headers"
        :routes="routes"
        module="gateway_customers"
        title="Clientes do Gateway"
        hide-selection
        hide-visibility-filter
        :permission-map="{ create: false, delete: false, visibility: false }"
        :custom-slots="['created_at']"
    >
        <template #toolbar-actions>
            <GatewaySyncButton scope="customers" />
        </template>
        <template #column-created_at="{ item }">
            {{ formatDateTime(item.created_at) }}
        </template>
    </TablePage>
</template>
