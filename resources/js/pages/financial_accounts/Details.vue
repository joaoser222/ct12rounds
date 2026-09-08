<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type FinancialAccount = {
    id?: number;
    name?: string;
    account_type?: string;
    holder_name?: string;
    holder_document?: string;
    holder_birth_date?: string;
    bank_account_number?: string;
    bank_agency?: string;
    bank_account_type?: string;
    bank_code?: string;
};

defineProps<{
    financialAccount?: FinancialAccount | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { accountTypes } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    name: '',
    account_type: '',
    holder_name: '',
    holder_document: '',
    holder_birth_date: '',
    bank_account_number: '',
    bank_agency: '',
    bank_account_type: '',
    bank_code: '',
};

const isBankAccount = (form: Record<string, unknown>): boolean => form.account_type === 'bank';
</script>

<template>
    <DetailsPage
        title="Conta"
        :item="financialAccount"
        :defaults="defaults"
        :routes="routes"
        module="financial_accounts"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.name"
                        v-text-case="'capitalize'"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.account_type"
                        label="Tipo de Conta"
                        :items="accountTypes"
                        :rules="[required]"
                        :error-messages="errors.account_type"
                    />
                </v-col>
                <template v-if="isBankAccount(form)">
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.holder_name"
                            v-text-case="'capitalize'"
                            label="Nome do Titular"
                            :rules="[required]"
                            :error-messages="errors.holder_name"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.holder_document"
                            label="CPF/CNPJ do Titular"
                            :rules="[required]"
                            :error-messages="errors.holder_document"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <DateField
                            v-model="form.holder_birth_date"
                            label="Data de Nascimento do Titular"
                            :rules="[required]"
                            :error-messages="errors.holder_birth_date"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.bank_code"
                            label="Código do Banco"
                            :rules="[required]"
                            :error-messages="errors.bank_code"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.bank_agency"
                            label="Agência"
                            :rules="[required]"
                            :error-messages="errors.bank_agency"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.bank_account_number"
                            label="Número da Conta"
                            :rules="[required]"
                            :error-messages="errors.bank_account_number"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="form.bank_account_type"
                            label="Tipo de Conta Bancária"
                            :rules="[required]"
                            :error-messages="errors.bank_account_type"
                        />
                    </v-col>
                </template>
            </v-row>
        </template>
    </DetailsPage>
</template>
