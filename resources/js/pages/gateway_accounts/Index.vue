<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import GatewayAccountSyncAction from '@/components/gateway/GatewayAccountSyncAction.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    gatewayAccounts: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Descrição', key: 'description', searchable: true },
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
        :items="gatewayAccounts.data"
        :total="gatewayAccounts.total"
        :current-page="gatewayAccounts.current_page"
        :last-page="gatewayAccounts.last_page"
        :per-page="gatewayAccounts.per_page"
        :headers="headers"
        :routes="routes"
        module="gateway_accounts"
        title="Gateway de Pagamentos"
        :custom-slots="['created_at']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>

        <template #extra-actions="{ item }">
            <GatewayAccountSyncAction :account-id="item.id" />
        </template>
    </TablePage>
</template>
