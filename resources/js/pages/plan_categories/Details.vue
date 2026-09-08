<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type PlanCategory = {
    id?: number;
    name?: string;
};

defineProps<{
    planCategory?: PlanCategory | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
};
</script>

<template>
    <DetailsPage
        title="Categoria de Plano"
        :item="planCategory"
        :defaults="defaults"
        :routes="routes"
        module="plan_categories"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12">
                    <v-text-field
                        v-model="form.name"
                        v-text-case="'capitalize'"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
