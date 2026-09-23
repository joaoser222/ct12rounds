<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { formatDateTime } from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';
import GatewaySyncButton from '@/components/gateway/GatewaySyncButton.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    gatewayPostbacks: PaginatedResponse<any>;
    routes: Pick<TableRoutes, 'index' | 'show'>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    {
        title: 'Evento',
        key: 'postback_event',
        sortable: true,
        searchable: true,
    },
    { title: 'Tipo', key: 'postback_type', sortable: true, searchable: true },
    {
        title: 'Status',
        key: 'status',
        sortable: true,
        align: 'center',
        searchable: {
            component: 'VSelect',
            props: () => ({ items: postbackStatus }),
        },
    },
    { title: 'Conta', key: 'gateway_account_id' },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.show,
};

const sharedProps = usePage().props;
const { postbackStatus } = useSharedOptions(sharedProps.options ?? {});
</script>

<template>
    <TablePage
        :items="gatewayPostbacks.data"
        :total="gatewayPostbacks.total"
        :current-page="gatewayPostbacks.current_page"
        :last-page="gatewayPostbacks.last_page"
        :per-page="gatewayPostbacks.per_page"
        :headers="headers"
        :routes="routes"
        module="gateway_postbacks"
        title="Postbacks do Gateway"
        hide-selection
        hide-visibility-filter
        :permission-map="{ create: false, delete: false, visibility: false }"
        :custom-slots="['status', 'created_at']"
    >
        <template #toolbar-actions>
            <GatewaySyncButton scope="postbacks" />
        </template>
        <template #column-status="{ item }">
            <v-chip :color="findOption(postbackStatus, item.status)?.color">
                {{ findLabel(postbackStatus, item.status) ?? item.status }}
            </v-chip>
        </template>
        <template #column-created_at="{ item }">
            {{ formatDateTime(item.created_at) }}
        </template>
    </TablePage>
</template>
