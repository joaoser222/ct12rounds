<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type GatewayTransferRecipient = {
    id?: number;
    gateway_account_id?: number;
    label?: string;
    holder_name?: string;
    holder_document?: string;
    pix_key?: string;
    pix_key_type?: string;
};

defineProps<{
    gatewayTransferRecipient?: GatewayTransferRecipient | null;
    routes: DetailsRoutes;
}>();

const { gatewayAccounts } = useSharedOptions(usePage().props.options ?? {});

const defaults = {
    gateway_account_id: null,
    label: '',
    holder_name: '',
    holder_document: '',
    pix_key: '',
    pix_key_type: '',
};
</script>

<template>
    <DetailsPage
        title="Destinatário de Transferência"
        :item="gatewayTransferRecipient"
        :defaults="defaults"
        :routes="routes"
        module="gateway_transfer_recipients"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.gateway_account_id"
                        label="Conta do Gateway"
                        :items="gatewayAccounts"
                        :rules="[required]"
                        :error-messages="errors.gateway_account_id"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.label"
                        label="Identificação"
                        :rules="[required]"
                        :error-messages="errors.label"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.holder_name"
                        v-text-case="'capitalize'"
                        label="Nome do Titular"
                        :rules="[required]"
                        :error-messages="errors.holder_name"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.holder_document"
                        label="CPF/CNPJ do Titular"
                        :rules="[required]"
                        :error-messages="errors.holder_document"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.pix_key"
                        label="Chave PIX"
                        :rules="[required]"
                        :error-messages="errors.pix_key"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.pix_key_type"
                        label="Tipo da Chave PIX"
                        :items="['CPF', 'CNPJ', 'EMAIL', 'PHONE', 'EVP']"
                        :rules="[required]"
                        :error-messages="errors.pix_key_type"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
