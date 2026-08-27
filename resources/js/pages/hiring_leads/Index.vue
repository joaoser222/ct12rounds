<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { formatDate } from '@/plugins/formatters';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type HiringLead = {
    id: number;
    name: string;
    document: string;
    phone: string;
    email: string;
    source: string;
    status: string;
    created_at: string;
};

const props = defineProps<{
    hiringLeads: PaginatedResponse<HiringLead>;
    routes: Omit<IndexRoutes, 'create'> & { convert?: string };
    options: Record<string, any>;
}>();

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Nome', key: 'name', sortable: true, searchable: true },
    { title: 'Documento', key: 'document', searchable: true },
    { title: 'Telefone', key: 'phone' },
    { title: 'E-mail', key: 'email', searchable: true },
    { title: 'Origem', key: 'source', sortable: true },
    { title: 'Status', key: 'status', sortable: true },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    show: props.routes.show,
    changeVisibility: props.routes.changeVisibility,
    destroy: props.routes.destroy,
};

const { hiringLeadStatus, hiringLeadSource } = useSharedOptions(
    props.options ?? {},
);

const convert = (item: HiringLead) => {
    if (!confirm(`Converter ${item.name} em cliente?`)) return;
    const route = props.routes.convert?.replace(':id', String(item.id));
    if (!route) return;
    router.post(route, undefined, {
        preserveScroll: true,
        onSuccess: () => router.reload(),
    });
};
</script>

<template>
    <TablePage
        :items="hiringLeads.data"
        :total="hiringLeads.total"
        :current-page="hiringLeads.current_page"
        :last-page="hiringLeads.last_page"
        :per-page="hiringLeads.per_page"
        :headers="headers"
        :routes="routes"
        module="hiring_leads"
        :permission-map="{ create: false }"
        title="Pré-cadastros de Clientes"
        :custom-slots="['created_at', 'source', 'status']"
    >
        <template #column-created_at="{ item }">
            {{ formatDate(item.created_at) }}
        </template>
        <template #column-source="{ item }">
            <v-chip
                :color="findOption(hiringLeadSource, item.source)?.color ?? 'secondary'"
                size="small"
            >
                {{ findLabel(hiringLeadSource, item.source) }}
            </v-chip>
        </template>
        <template #column-status="{ item }">
            <v-chip
                :color="findOption(hiringLeadStatus, item.status)?.color ?? 'secondary'"
                size="small"
            >
                {{ findLabel(hiringLeadStatus, item.status) }}
            </v-chip>
        </template>
        <template #extra-actions="{ item }">
            <v-btn-icon
                icon="ti ti-user-check"
                size="small"
                color="success"
                title="Converter em cliente"
                @click="convert(item)"
            />
        </template>
    </TablePage>
</template>