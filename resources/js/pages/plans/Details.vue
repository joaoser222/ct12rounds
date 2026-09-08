<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Plan = {
    id?: number;
    name?: string;
    plan_category_id?: number;
    description?: string;
    price?: number;
    duration_months?: number;
    plan_modalities?: number[];
};

const props = defineProps<{
    plan?: Plan | null;
    routes: DetailsRoutes;
    publicRegistrationUrl?: string | null;
}>();

const { modalities } = useSharedOptions(usePage().props.options ?? {});

const defaults = {
    name: '',
    plan_category_id: null,
    description: '',
    price: 0,
    duration_months: 1,
    plan_modalities: [],
};

async function copyPublicLink(): Promise<void> {
    if (!props.publicRegistrationUrl) return;
    await navigator.clipboard.writeText(props.publicRegistrationUrl);
}
</script>

<template>
    <DetailsPage
        title="Plano"
        :item="plan"
        :defaults="defaults"
        :routes="routes"
        module="plans"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.name"
                        label="Nome"
                        :rules="[required]"
                        :error-messages="errors.name"
                        v-text-case="'upper'"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <ServerAutocomplete
                        v-model="form.plan_category_id"
                        object-name="plan-category"
                        label="Categoria de Plano"
                        :rules="[required]"
                        :error-messages="errors.plan_category_id"
                    />
                </v-col>
                <v-col cols="12">
                    <QuillEditor
                        v-model="form.description"
                        label="Descrição"
                        rows="5"
                        :error-messages="errors.description"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <CurrencyField
                        v-model="form.price"
                        label="Preço"
                        :rules="[required]"
                        :error-messages="errors.price"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.duration_months"
                        label="Duração (meses)"
                        type="number"
                        :rules="[required]"
                        :error-messages="errors.duration_months"
                    />
                </v-col>
                <v-col
                    v-if="publicRegistrationUrl"
                    cols="12"
                >
                    <v-text-field
                        :model-value="publicRegistrationUrl"
                        label="Link de cadastro"
                        readonly
                        persistent-hint
                        hint="Envie este link ao cliente para ele preencher os dados e dar o aceite. O plano já vem selecionado."
                    >
                        <template #append-inner>
                            <v-btn-icon
                                icon="ti ti-copy"
                                size="small"
                                title="Copiar link"
                                @click="copyPublicLink"
                            />
                        </template>
                    </v-text-field>
                </v-col>
                <v-col cols="12">
                    <v-autocomplete
                        v-model="form.plan_modalities"
                        label="Modalidades disponíveis"
                        :items="modalities"
                        multiple
                        chips
                        closable-chips
                        clearable
                        item-title="title"
                        item-value="value"
                        :error-messages="errors.plan_modalities"
                        hint="Se não preencher, todas as modalidades estão disponíveis"
                        persistent-hint
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
