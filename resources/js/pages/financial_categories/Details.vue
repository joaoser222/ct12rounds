<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type FinancialCategory = {
    id?: number;
    name?: string;
    color?: string;
    operation_type?: string;
    cost_center_id?: number;
};

defineProps<{
    financialCategory?: FinancialCategory | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { operationTypes, costCenters } = useSharedOptions(
    sharedProps.options ?? {},
);

const defaults = {
    name: '',
    color: '',
    operation_type: '',
    cost_center_id: null,
};

</script>

<template>
    <DetailsPage
        title="Categorias Financeiras"
        :item="financialCategory"
        :defaults="defaults"
        :routes="routes"
        module="financial_categories"
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
                        v-model="form.operation_type"
                        label="Tipo de Operação"
                        :items="operationTypes"
                        :rules="[required]"
                        :error-messages="errors.operation_type"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <ServerAutocomplete
                        v-model="form.cost_center_id"
                        object-name="cost-center"
                        label="Centro de Custo"
                        :rules="[required]"
                        :error-messages="errors.cost_center_id"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
