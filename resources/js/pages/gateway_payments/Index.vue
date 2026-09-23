<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import {
    formatCurrency,
    formatDate,
    formatDateTime,
} from '@/plugins/formatters';
import type { PaginatedResponse } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';
import GatewaySyncButton from '@/components/gateway/GatewaySyncButton.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    gatewayPayments: PaginatedResponse<any>;
    routes: Pick<TableRoutes, 'index' | 'show'>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Referência', key: 'gateway_reference_key', searchable: true },
    {
        title: 'Método',
        key: 'payment_method',
        searchable: {
            component: 'VSelect',
            props: () => ({ items: paymentMethods }),
        },
    },
    {
        title: 'Pagamento',
        key: 'payment_date',
        sortable: true,
        searchable: { component: 'DateField' },
    },
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
    { title: 'Bruto', key: 'gross_value', sortable: true },
    { title: 'Taxa', key: 'fee_value', sortable: true },
    { title: 'Total', key: 'total', sortable: true },
    { title: 'Fatura', key: 'invoice_id' },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.show,
};

const sharedProps = usePage().props;
const { paymentMethods, transactionStatus } = useSharedOptions(
    sharedProps.options ?? {},
);
</script>

<template>
    <TablePage
        :items="gatewayPayments.data"
        :total="gatewayPayments.total"
        :current-page="gatewayPayments.current_page"
        :last-page="gatewayPayments.last_page"
        :per-page="gatewayPayments.per_page"
        :headers="headers"
        :routes="routes"
        module="gateway_payments"
        title="Pagamentos do Gateway"
        hide-selection
        hide-visibility-filter
        :permission-map="{ create: false, delete: false, visibility: false }"
        :custom-slots="[
            'payment_method',
            'payment_date',
            'status',
            'gross_value',
            'fee_value',
            'total',
            'created_at',
        ]"
    >
        <template #toolbar-actions>
            <GatewaySyncButton scope="payments" />
        </template>
        <template #column-payment_method="{ item }">
            {{
                findLabel(paymentMethods, item.payment_method) ??
                item.payment_method
            }}
        </template>
        <template #column-payment_date="{ item }">
            {{ formatDate(item.payment_date) }}
        </template>
        <template #column-status="{ item }">
            <v-chip :color="findOption(transactionStatus, item.status)?.color">
                {{ findLabel(transactionStatus, item.status) ?? item.status }}
            </v-chip>
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
        <template #column-created_at="{ item }">
            {{ formatDateTime(item.created_at) }}
        </template>
    </TablePage>
</template>
