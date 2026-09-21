<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type ModalityCategory = {
    id?: number;
    name?: string;
    slug?: string;
    audience?: string;
};

defineProps<{
    modalityCategory?: ModalityCategory | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
    slug: '',
    audience: 'adult',
};

const audiences = [
    { value: 'adult', label: 'Adulto' },
    { value: 'child', label: 'Infantil' },
];
</script>

<template>
    <DetailsPage
        title="Categorias de Modalidades"
        :item="modalityCategory"
        :defaults="defaults"
        :routes="routes"
        module="modality_categories"
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
                        v-model="form.slug"
                        label="Slug"
                        :rules="[required]"
                        :error-messages="errors.slug"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.audience"
                        :items="audiences"
                        item-title="label"
                        item-value="value"
                        label="Público"
                        :rules="[required]"
                        :error-messages="errors.audience"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
