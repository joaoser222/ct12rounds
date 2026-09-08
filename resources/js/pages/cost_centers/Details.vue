<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type CostCenter = {
    id?: number;
    name?: string;
    color?: string;
    operation_type?: string;
};

defineProps<{
    costCenter?: CostCenter | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props;
const { operationTypes } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    name: '',
    color: '',
    operation_type: '',
};
</script>

<template>
    <DetailsPage
        title="Centro de Custo"
        :item="costCenter"
        :defaults="defaults"
        :routes="routes"
        module="cost_centers"
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
                    <v-text-field
                        v-model="form.color"
                        label="Cor"
                        type="color"
                        :error-messages="errors.color"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
