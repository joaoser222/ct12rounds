<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ModalityCard from '@/components/ModalityCard.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type Modality = {
    id?: number;
    name?: string;
    color?: string;
};

const props = defineProps<{
    modality?: Modality | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
    color: '',
};
</script>

<template>
    <DetailsPage
        title="Modalidade"
        :item="modality"
        :defaults="defaults"
        :routes="routes"
        module="modalities"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
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
                <v-col cols="12">
                    <div class="text-subtitle-2 text-medium-emphasis mb-2">
                        Pré-visualização
                    </div>
                    <ModalityCard :name="form.name" :color="form.color" />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
