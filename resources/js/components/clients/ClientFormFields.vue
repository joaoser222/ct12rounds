<script setup lang="ts">
import { ref } from 'vue';
import { masks, phoneMask } from '@/plugins/masks';
import { fillAddressFromCep, type AddressForm } from '@/plugins/viacep';
import { cpf, email, required } from '@/plugins/validators';
import type { Option } from '@/shared/options';

type ClientFormData = {
    name: string;
    email: string;
    phone: string;
    document: string;
    gender: string;
    birth_date: string;
    legal_representative: boolean;
    legal_representative_name: string;
    legal_representative_document: string;
    legal_representative_birth_date: string;
    address_postal_code: string;
    address: string;
    address_number: string;
    address_complement: string;
    address_district: string;
    address_state: string;
    address_city: string;
};

type ClientFormErrors = Record<string, string | undefined>;

const props = withDefaults(
    defineProps<{
        form: ClientFormData;
        errors: ClientFormErrors;
        genderTypes: Option[];
        ufs: Option[];
        requireAddressState?: boolean;
        disabled?: boolean;
    }>(),
    {
        requireAddressState: false,
        disabled: false,
    },
);

const isLoadingAddress = ref(false);

function phoneFieldMask(): string {
    return phoneMask(props.form.phone);
}

async function fillAddress(): Promise<void> {
    if (isLoadingAddress.value) {
        return;
    }

    isLoadingAddress.value = true;

    try {
        await fillAddressFromCep(
            props.form as AddressForm,
            String(props.form.address_postal_code ?? ''),
        );
    } finally {
        isLoadingAddress.value = false;
    }
}
</script>

<template>
    <v-row class="ma-0">
        <v-col cols="12" md="6" class="d-flex align-center">
            <v-checkbox
                v-model="form.legal_representative"
                label="Possui responsável legal"
                :disabled="disabled"
                :error-messages="errors.legal_representative"
            />
        </v-col>

        <template v-if="form.legal_representative">
            <v-col cols="12">
                <v-divider class="my-4">
                    <strong>Dados do Responsável</strong>
                </v-divider>
            </v-col>
            <v-col cols="12">
            <v-text-field
                v-model="form.legal_representative_name"
                label="Nome do responsável"
                :rules="[required]"
                :disabled="disabled"
                :error-messages="errors.legal_representative_name"
                v-text-case="'capitalize'"
            />
            </v-col>
            <v-col cols="12" md="6">
                <MaskedTextField
                    v-model="form.legal_representative_document"
                    label="CPF do responsável"
                    :mask="masks.cpf"
                    :rules="[required, cpf]"
                    :disabled="disabled"
                    :error-messages="errors.legal_representative_document"
                />
            </v-col>
            <v-col cols="12" md="6">
                <DateField
                    v-model="form.legal_representative_birth_date"
                    label="Nascimento do responsável"
                    :rules="[required]"
                    :disabled="disabled"
                    :error-messages="errors.legal_representative_birth_date"
                />
            </v-col>
        </template>

        <v-col cols="12">
            <v-divider class="my-4">
                <strong>Dados Pessoais</strong>
            </v-divider>
        </v-col>
        <v-col cols="12">
            <v-text-field
                v-model="form.name"
                label="Nome"
                :rules="[required]"
                :disabled="disabled"
                :error-messages="errors.name"
                v-text-case="'capitalize'"
            />
        </v-col>
        <v-col cols="12" md="4">
            <DateField
                v-model="form.birth_date"
                label="Nascimento"
                :rules="[required]"
                :disabled="disabled"
                :error-messages="errors.birth_date"
            />
        </v-col>
        <v-col cols="12" md="4">
            <v-select
                v-model="form.gender"
                label="Gênero"
                :items="genderTypes"
                :rules="[required]"
                :disabled="disabled"
                :error-messages="errors.gender"
            />
        </v-col>
        <v-col cols="12" md="4">
            <MaskedTextField
                v-model="form.document"
                label="CPF"
                :mask="masks.cpf"
                :rules="[required, cpf]"
                :disabled="disabled"
                :error-messages="errors.document"
            />
        </v-col>
        <v-col cols="12" md="8">
            <v-text-field
                v-model="form.email"
                label="E-mail"
                type="email"
                :rules="[required, email]"
                :disabled="disabled"
                :error-messages="errors.email"
                v-text-case="'lower'"
            />
        </v-col>
        <v-col cols="12" md="4">
            <MaskedTextField
                v-model="form.phone"
                label="Telefone"
                :mask="phoneFieldMask()"
                :rules="[required]"
                :disabled="disabled"
                :error-messages="errors.phone"
            />
        </v-col>

        <v-col cols="12">
            <v-divider class="my-4">
                <strong>Endereço</strong>
            </v-divider>
        </v-col>
        <v-col cols="12" md="4">
            <MaskedTextField
                v-model="form.address_postal_code"
                label="CEP"
                :mask="masks.cep"
                :loading="isLoadingAddress"
                :rules="[required]"
                :disabled="disabled"
                :error-messages="errors.address_postal_code"
                @blur="fillAddress"
            />
        </v-col>
        <v-col cols="12" md="8">
            <v-text-field
                v-model="form.address"
                label="Endereço"
                :disabled="disabled"
                :error-messages="errors.address"
                v-text-case="'capitalize'"
            />
        </v-col>
        <v-col cols="12" md="4">
            <v-text-field
                v-model="form.address_number"
                label="Número"
                :rules="[required]"
                :disabled="disabled"
                :error-messages="errors.address_number"
                v-text-case="'upper'"
            />
        </v-col>
        <v-col cols="12" md="8">
            <v-text-field
                v-model="form.address_complement"
                label="Complemento"
                :disabled="disabled"
                :error-messages="errors.address_complement"
                v-text-case="'capitalize'"
            />
        </v-col>
        <v-col cols="12" md="4">
            <v-text-field
                v-model="form.address_district"
                label="Bairro"
                :disabled="disabled"
                :error-messages="errors.address_district"
                v-text-case="'capitalize'"
            />
        </v-col>
        <v-col cols="12" md="4">
            <v-select
                v-model="form.address_state"
                label="Estado"
                :items="ufs"
                :rules="requireAddressState ? [required] : []"
                :disabled="disabled"
                :error-messages="errors.address_state"
            />
        </v-col>
        <v-col cols="12" md="4">
            <v-text-field
                v-model="form.address_city"
                label="Cidade"
                :disabled="disabled"
                :error-messages="errors.address_city"
                v-text-case="'capitalize'"
            />
        </v-col>
    </v-row>
</template>
