<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';
import { formatDate, formatDateTime } from '@/plugins/formatters';
import { findLabel, findOption, useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    hiringLead?: Record<string, unknown> | null;
    routes: {
        index?: string;
        convert?: string;
        show?: string;
        changeVisibility?: string;
        destroy?: string;
    };
    options: Record<string, any>;
}>();

const { genderTypes, hiringLeadSource, hiringLeadStatus, plans, coupons, ufs } =
    useSharedOptions(props.options ?? {});

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id', md: 6 },
    { title: 'Nome', key: 'name', md: 6 },
    { title: 'CPF', key: 'document', md: 4 },
    { title: 'Telefone', key: 'phone', md: 4 },
    { title: 'E-mail', key: 'email', md: 4 },
    { title: 'Gênero', key: 'gender', md: 6 },
    { title: 'Nascimento', key: 'birth_date', md: 6 },
    { title: 'Origem', key: 'source', md: 6 },
    { title: 'Status', key: 'status', md: 6 },
    { title: 'Aceite dos termos', key: 'accepted_at', md: 6 },
    { title: 'Convertido em', key: 'converted_at', md: 6 },
    { title: 'Plano', key: 'plan_id', md: 6 },
    { title: 'Cupom', key: 'coupon_id', md: 6 },
    { title: 'CEP', key: 'address_postal_code', md: 4 },
    { title: 'Endereço', key: 'address', md: 8 },
    { title: 'Número', key: 'address_number', md: 4 },
    { title: 'Complemento', key: 'address_complement', md: 8 },
    { title: 'Bairro', key: 'address_district', md: 4 },
    { title: 'Estado', key: 'address_state', md: 4 },
    { title: 'Cidade', key: 'address_city', md: 4 },
    { title: 'Criado em', key: 'created_at', md: 6 },
    { title: 'Atualizado em', key: 'updated_at', md: 6 },
];

const convert = () => {
    if (!props.routes.convert || !props.hiringLead) return;
    const route = props.routes.convert.replace(
        ':id',
        String(props.hiringLead.id),
    );

    router.post(route, undefined, {
        preserveScroll: true,
    });
};
</script>

<template>
    <ReadOnlyDetailsPage
        title="Pré-cadastro de Cliente"
        :item="hiringLead ?? {}"
        :fields="fields"
        :index-route="routes.index ?? ''"
        :custom-slots="[
            'gender',
            'birth_date',
            'source',
            'status',
            'accepted_at',
            'converted_at',
            'plan_id',
            'coupon_id',
            'address_state',
            'created_at',
            'updated_at',
        ]"
    >
        <template #field-gender="{ value }">
            {{
                findLabel(genderTypes, value as string | null) ?? value ?? '-'
            }}
        </template>
        <template #field-birth_date="{ value }">
            {{ formatDate(value as string | null) }}
        </template>
        <template #field-source="{ value }">
            <v-chip
                :color="
                    findOption(hiringLeadSource, value as string | null)?.color ??
                    'secondary'
                "
                size="small"
            >
                {{
                    findLabel(hiringLeadSource, value as string | null) ??
                    value ??
                    '-'
                }}
            </v-chip>
        </template>
        <template #field-status="{ value }">
            <v-chip
                :color="
                    findOption(hiringLeadStatus, value as string | null)?.color ??
                    'secondary'
                "
                size="small"
            >
                {{
                    findLabel(hiringLeadStatus, value as string | null) ??
                    value ??
                    '-'
                }}
            </v-chip>
        </template>
        <template #field-accepted_at="{ value }">
            {{ value ? formatDateTime(value as string) : '-' }}
        </template>
        <template #field-converted_at="{ value }">
            {{ value ? formatDateTime(value as string) : '-' }}
        </template>
        <template #field-plan_id="{ value }">
            {{ findLabel(plans, value as string | number | null) ?? '-' }}
        </template>
        <template #field-coupon_id="{ value }">
            {{ findLabel(coupons, value as string | number | null) ?? '-' }}
        </template>
        <template #field-address_state="{ value }">
            {{ findLabel(ufs, value as string | null) ?? value ?? '-' }}
        </template>
        <template #field-created_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>
        <template #field-updated_at="{ value }">
            {{ formatDateTime(value as string | null) }}
        </template>

        <template #actions>
            <v-clipped-button
                v-if="hiringLead?.status !== 'converted' && routes.convert"
                color="success"
                prepend-icon="ti ti-user-check"
                @click="convert"
            >
                Converter em Cliente
            </v-clipped-button>
        </template>
    </ReadOnlyDetailsPage>
</template>