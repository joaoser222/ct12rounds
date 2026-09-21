<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type LoyaltyLevel = {
    id?: number;
    name?: string;
    min_months?: number;
    color?: string;
    description?: string;
};

defineProps<{
    loyaltyLevel?: LoyaltyLevel | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
    min_months: 0,
    color: '',
    description: '',
};
</script>

<template>
    <DetailsPage
        title="Níveis de Fidelidade"
        :item="loyaltyLevel"
        :defaults="defaults"
        :routes="routes"
        module="loyalty_levels"
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
                    <v-text-field
                        v-model.number="form.min_months"
                        label="Meses Mínimos"
                        type="number"
                        min="0"
                        :rules="[required]"
                        :error-messages="errors.min_months"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-color-picker
                        v-model="form.color"
                        label="Cor"
                        mode="hexa"
                        :error-messages="errors.color"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-textarea
                        v-model="form.description"
                        label="Descrição"
                        :error-messages="errors.description"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
