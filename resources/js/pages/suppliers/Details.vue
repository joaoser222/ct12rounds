<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { documentMask, masks, phoneMask } from '@/plugins/masks';
import { fillAddressFromCep, type AddressForm } from '@/plugins/viacep';
import { required, email, phone, cpfCnpj } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

type Supplier = {
    id?: number;
    name?: string;
    email?: string;
    phone?: string;
    document?: string;
    address_postal_code?: string | null;
    address?: string | null;
    address_number?: string | null;
    address_complement?: string | null;
    address_district?: string | null;
    address_state?: string | null;
    address_city?: string | null;
};

defineProps<{
    supplier?: Supplier | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { ufs } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    name: '',
    email: '',
    phone: '',
    document: '',
    address_postal_code: '',
    address: '',
    address_number: '',
    address_complement: '',
    address_district: '',
    address_state: '',
    address_city: '',
};

const isLoadingAddress = ref(false);

async function fillAddress(form: AddressForm): Promise<void> {
    if (isLoadingAddress.value) return;
    isLoadingAddress.value = true;
    try {
        await fillAddressFromCep(form, form.address_postal_code);
    } finally {
        isLoadingAddress.value = false;
    }
}
</script>

<template>
    <DetailsPage
        title="Fornecedor"
        :item="supplier"
        :defaults="defaults"
        :routes="routes"
        module="suppliers"
    >
        <template #default="{ form, errors }">
            <v-divider class="my-4">
                <strong>Dados Pessoais</strong>
            </v-divider>
            <v-row class="ma-0">
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                        v-text-case="'capitalize'"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <MaskedTextField
                        v-model="form.document"
                        label="CPF/CNPJ"
                        :mask="documentMask(form.document)"
                        :limit-to-mask="false"
                        :rules="[required, cpfCnpj]"
                        :error-messages="errors.document"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.email"
                        label="E-mail"
                        type="email"
                        :rules="[required, email]"
                        :error-messages="errors.email"
                        v-text-case="'lower'"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <MaskedTextField
                        v-model="form.phone"
                        label="Telefone"
                        :mask="phoneMask(form.phone)"
                        :rules="[required, phone]"
                        :error-messages="errors.phone"
                    />
                </v-col>
            </v-row>

            <v-divider class="my-4">
                <strong>Endereço</strong>
            </v-divider>
            <v-row class="ma-0">
                <v-col cols="12" md="4">
                <MaskedTextField
                    v-model="form.address_postal_code"
                    label="CEP"
                    :mask="masks.cep"
                    :loading="isLoadingAddress"
                    :rules="[required]"
                    :error-messages="errors.address_postal_code"
                    @blur="fillAddress(form)"
                />
                </v-col>
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.address"
                        label="Endereço"
                        :error-messages="errors.address"
                        v-text-case="'capitalize'"
                    />
                </v-col>
                <v-col cols="12" md="4">
                <v-text-field
                    v-model="form.address_number"
                    label="Número"
                    :rules="[required]"
                    :error-messages="errors.address_number"
                    v-text-case="'upper'"
                />
                </v-col>
                <v-col cols="12" md="8">
                    <v-text-field
                        v-model="form.address_complement"
                        label="Complemento"
                        :error-messages="errors.address_complement"
                        v-text-case="'capitalize'"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.address_district"
                        label="Bairro"
                        :error-messages="errors.address_district"
                        v-text-case="'capitalize'"
                    />
                </v-col>
                <v-col cols="12" md="4">
                <v-select
                    v-model="form.address_state"
                    label="Estado"
                    :items="ufs"
                    :error-messages="errors.address_state"
                />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.address_city"
                        label="Cidade"
                        :error-messages="errors.address_city"
                        v-text-case="'capitalize'"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
