<!-- resources/js/Pages/Clients/Index.vue -->
<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import { masks,formatMasks } from '@/plugins/masks';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

// Page props
const props = defineProps<{
    clients: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

// Table configuration
const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'CPF', key: 'document',searchable: {
        component: 'MaskedTextField',
        props: {
            mask: masks.cpf
        }
    }},
    { title: 'Telefone', key: 'phone' },
    {
        title: 'Status',
        key: 'status',
        sortable: true,
        align: 'center',
    },
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
const { clientStatus } = useSharedOptions(
    sharedProps.options ?? {},
);

</script>

<template>
    <TablePage
        :items="clients.data"
        :total="clients.total"
        :current-page="clients.current_page"
        :last-page="clients.last_page"
        :per-page="clients.per_page"
        :headers="headers"
        :routes="routes"
        module="clients"
        title="Clientes"
        :custom-slots="['status', 'created_at', 'phone', 'document']"
    >
        <!-- Custom status -->
        <template #column-status="{ item }">
            <v-chip :color="findOption(clientStatus, item.status)?.color">
                {{ findLabel(clientStatus, item.status) }}
            </v-chip>
        </template>
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-document="{ item }">
            {{ formatMasks.cpf(item.document) }}
        </template>
        <template #column-phone="{ item }">
            {{ formatMasks.phone(item.phone) }}
        </template>
    </TablePage>
</template>
