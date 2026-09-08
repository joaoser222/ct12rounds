<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ClientFormFields from '@/components/clients/ClientFormFields.vue';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Client = {
    id?: number;
    name?: string;
    email?: string;
    phone?: string;
    document?: string;
    gender?: string;
    birth_date?: string;
    legal_representative?: boolean;
    legal_representative_name?: string | null;
    legal_representative_document?: string | null;
    legal_representative_birth_date?: string | null;
    address_postal_code?: string | null;
    address?: string | null;
    address_number?: string | null;
    address_complement?: string | null;
    address_district?: string | null;
    address_state?: string | null;
    address_city?: string | null;
};

defineProps<{
    client?: Client | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { genderTypes, states } = useSharedOptions(
    sharedProps.options ?? {},
);

const defaults = {
    name: '',
    email: '',
    phone: '',
    document: '',
    gender: '',
    birth_date: '',
    legal_representative: false,
    legal_representative_name: '',
    legal_representative_document: '',
    legal_representative_birth_date: '',
    address_postal_code: '',
    address: '',
    address_number: '',
    address_complement: '',
    address_district: '',
    address_state: '',
    address_city: ''
};
</script>

<template>
    <DetailsPage
        title="Cliente"
        :item="client"
        :defaults="defaults"
        :routes="routes"
        module="clients"
    >
        <template #default="{ form, errors }">
            <ClientFormFields
                :form="form"
                :errors="errors"
                :gender-types="genderTypes"
                :states="states"
            />
        </template>
    </DetailsPage>
</template>
