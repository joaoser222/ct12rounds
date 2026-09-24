<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';
import { formatDateTime } from '@/plugins/formatters';

defineOptions({ layout: AuthenticatedLayout });

defineProps<{
    gatewayCustomer: Record<string, unknown>;
    routes: { index: string };
}>();

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' },
    { title: 'Referência', key: 'gateway_reference_key' },
    { title: 'Tipo do titular', key: 'holder_type' },
    { title: 'Titular', key: 'holder_name' },
    { title: 'Conta Gateway', key: 'gateway_account_id' },
    { title: 'Postback', key: 'gateway_postback_id' },
    { title: 'Criado em', key: 'created_at' },
    { title: 'Atualizado em', key: 'updated_at' },
];
</script>

<template>
    <ReadOnlyDetailsPage
        title="Cliente do Gateway"
        :item="gatewayCustomer"
        :fields="fields"
        :index-route="routes.index"
        :custom-slots="['created_at', 'updated_at']"
    >
        <template #field-created_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
        <template #field-updated_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
    </ReadOnlyDetailsPage>
</template>
