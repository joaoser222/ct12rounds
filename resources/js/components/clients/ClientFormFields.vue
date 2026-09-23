<script setup lang="ts">
import { ref, watch } from 'vue';
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
    audience_category: string | null;
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
        states: Option[];
        requireAddressState?: boolean;
        disabled?: boolean;
    }>(),
    {
        requireAddressState: false,
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:audience_category': [value: string];
}>();

const isLoadingAddress = ref(false);

function phoneFieldMask(): string {
    return phoneMask(props.form.phone);
}

function calculateAge(birthDate: string): number | null {
    if (!birthDate) return null;
    const today = new Date();
    const birth = new Date(birthDate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (
        monthDiff < 0 ||
        (monthDiff === 0 && today.getDate() < birth.getDate())
    ) {
        age--;
    }
    return age;
}

watch(
    () => props.form.birth_date,
    (newBirthDate) => {
        const age = calculateAge(newBirthDate);
        if (age === null) return;
        const newCategory = age >= 18 ? 'adult' : 'child';
        if (props.form.audience_category !== newCategory) {
            emit('update:audience_category', newCategory);
        }
    },
);

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
        <v-col cols="12" md="6">
            <v-label class="mb-2 text-white"
                ><strong>Tipo de cliente</strong></v-label
            >
            <v-radio-group
                v-model="form.audience_category"
                :disabled="disabled"
                :error-messages="errors.audience_category"
                inline
            >
                <v-radio
                    label="Adulto"
                    value="adult"
                    color="primary"
                    false-icon="ti ti-circle"
                    true-icon="ti ti-circle-filled"
                />
                <v-radio
                    label="Infantil"
                    value="child"
                    color="primary"
                    false-icon="ti ti-circle"
                    true-icon="ti ti-circle-filled"
                />
            </v-radio-group>
        </v-col>

        <template v-if="form.audience_category === 'child'">
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
                    v-text-case="'capitalize-exclusive'"
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
                v-text-case="'capitalize-exclusive'"
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
                :items="states"
                :rules="requireAddressState ? [required] : []"
                :disabled="disabled"
                readonly
                :error-messages="errors.address_state"
            />
        </v-col>
        <v-col cols="12" md="4">
            <v-text-field
                v-model="form.address_city"
                label="Cidade"
                :disabled="disabled"
                readonly
                :error-messages="errors.address_city"
                v-text-case="'capitalize'"
            />
        </v-col>
    </v-row>
</template>
