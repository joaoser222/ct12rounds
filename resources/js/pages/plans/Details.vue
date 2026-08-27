<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type PlanTier = {
    quantity: number | null;
    price: number;
};

type Plan = {
    id?: number;
    name?: string;
    plan_category_id?: number;
    modality_quantity?: number;
    description?: string;
    tiers?: PlanTier[];
    plan_modalities?: number[];
};

const props = defineProps<{
    plan?: Plan | null;
    routes: DetailsRoutes;
    publicCadastroUrl?: string | null;
}>();

const { modalities } = useSharedOptions(usePage().props.options ?? {});

const defaults = {
    name: '',
    plan_category_id: null,
    modality_quantity: 1,
    description: '',
    tiers: [],
    plan_modalities: [],
};

const tierColumns = [
    { title: 'Meses', width: '180px', align: 'right' as const },
    { title: 'Preço', width: '220px', align: 'right' as const },
];

function addTier(form: Record<string, unknown>): void {
    const tiers = form.tiers as PlanTier[];

    tiers.push({ quantity: null, price: 0 });
}

function removeTier(form: Record<string, unknown>, index: number): void {
    const tiers = form.tiers as PlanTier[];

    tiers.splice(index, 1);
}

function validateTiers(value: unknown): true | string {
    if (!Array.isArray(value) || value.length === 0) {
        return 'Adicione pelo menos uma duração com preço.';
    }

    if (
        value.some((tier) => !tier || !tier.quantity || Number(tier.price) < 0)
    ) {
        return 'Preencha meses e preço de todas as durações.';
    }

    return true;
}

async function copyPublicLink(): Promise<void> {
    if (!props.publicCadastroUrl) return;
    await navigator.clipboard.writeText(props.publicCadastroUrl);
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
                        v-text-case="'capitalize'"
                    />
                </v-col>
                <v-col cols="12" md="3">
                    <ServerAutocomplete
                        v-model="form.plan_category_id"
                        object-name="plan-category"
                        label="Categoria de Plano"
                        :rules="[required]"
                        :error-messages="errors.plan_category_id"
                    />
                </v-col>
                <v-col cols="12" md="3">
                    <v-text-field
                        v-model="form.modality_quantity"
                        label="Qtd. Modalidades"
                        :rules="[required]"
                        type="number"
                        :error-messages="errors.modality_quantity"
                    />
                </v-col>
                <v-col cols="12">
                    <v-textarea
                        v-model="form.description"
                        label="Descrição"
                        rows="3"
                        :error-messages="errors.description"
                    />
                </v-col>
                <v-col
                    v-if="publicCadastroUrl"
                    cols="12"
                >
                    <v-text-field
                        :model-value="publicCadastroUrl"
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
                    <EditableRowsTable
                        :items="form.tiers"
                        :columns="tierColumns"
                        title="Durações e Preços"
                        description="Cada linha define o preço do plano para uma quantidade de meses."
                        add-label="Adicionar duração"
                        empty-message="Configure pelo menos uma faixa de meses com preço para salvar o plano."
                        @add="addTier(form)"
                        @remove="removeTier(form, $event)"
                    >
                        <template #row="{ item, index }">
                            <td>
                                <v-text-field
                                    v-model="item.quantity"
                                    label="Meses"
                                    type="number"
                                    :rules="[required]"
                                    hide-details="auto"
                                    :error-messages="
                                        errors[`tiers.${index}.quantity`]
                                    "
                                />
                            </td>
                            <td>
                                <CurrencyField
                                    v-model="item.price"
                                    label="Preço"
                                    :rules="[required]"
                                    hide-details="auto"
                                    :error-messages="
                                        errors[`tiers.${index}.price`]
                                    "
                                />
                            </td>
                        </template>
                    </EditableRowsTable>

                    <v-input
                        :model-value="form.tiers"
                        :rules="[validateTiers]"
                        :error-messages="errors.tiers"
                    />
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
