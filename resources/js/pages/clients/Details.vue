<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ClientFormFields from '@/components/clients/ClientFormFields.vue';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type Client = {
    id?: number;
    name?: string;
    email?: string;
    phone?: string;
    document?: string;
    gender?: string;
    birth_date?: string;
    audience_category?: string | null;
    legal_representative_name?: string | null;
    legal_representative_document?: string | null;
    legal_representative_birth_date?: string | null;
    address_postal_code?: string | null;
    address?: string | null;
    address_number?: string | null;
    address_complement?: string | null;
    address_district?: string | null;
    address_state?: string | null;
    address_city?: string | null;
};

type ImageRightsDefaults = {
    image_producer_name: string;
    image_usage_purpose: string;
    image_description: string;
    image_material_type: string;
    site_owner_name: string;
    site_name: string;
    site_domain: string;
    forum_city: string;
    legal_representative_relationship: string;
};

const props = defineProps<{
    client?: Client | null;
    routes: DetailsRoutes;
    imageRightsDefaults: ImageRightsDefaults;
}>();

const imageRightsDialog = ref(false);
const imageRightsForm = reactive<ImageRightsDefaults>({
    ...props.imageRightsDefaults,
});

const sharedProps = usePage().props;
const { genderTypes, states } = useSharedOptions(sharedProps.options ?? {});

const defaults = {
    name: '',
    email: '',
    phone: '',
    document: '',
    gender: '',
    birth_date: '',
    audience_category: 'adult',
    legal_representative_name: '',
    legal_representative_document: '',
    legal_representative_birth_date: '',
    address_postal_code: '',
    address: '',
    address_number: '',
    address_complement: '',
    address_district: '',
    address_state: '',
    address_city: '',
};

function onAudienceCategoryUpdate(form: Record<string, any>, value: string) {
    form.audience_category = value;
}

function openImageRightsDialog(): void {
    if (props.client?.id === undefined || !props.routes.imageRights) {
        return;
    }

    Object.assign(imageRightsForm, props.imageRightsDefaults, {
        forum_city:
            props.client.address_city ?? props.imageRightsDefaults.forum_city,
    });
    imageRightsDialog.value = true;
}

function generateImageRightsPdf(): void {
    if (props.client?.id === undefined || !props.routes.imageRights) {
        return;
    }

    const params = new URLSearchParams();

    Object.entries(imageRightsForm).forEach(([key, value]) => {
        params.set(key, value);
    });

    const url = `${props.routes.imageRights.replace(
        ':id',
        String(props.client.id),
    )}?${params.toString()}`;

    imageRightsDialog.value = false;

    const preview = window.open(url, '_blank', 'noopener,noreferrer');

    if (!preview) {
        window.location.assign(url);
    }
}
</script>

<template>
    <div>
        <DetailsPage
            title="Cliente"
            :item="client"
            :defaults="defaults"
            :routes="routes"
            module="clients"
        >
            <template #default="{ form, errors }">
                <ClientFormFields
                    :form="form"
                    :errors="errors"
                    :gender-types="genderTypes"
                    :states="states"
                    @update:audience-category="
                        onAudienceCategoryUpdate(form, $event)
                    "
                />
            </template>

            <template #actions="{ isCreating }">
                <v-clipped-button
                    v-if="!isCreating"
                    color="secondary"
                    prepend-icon="ti ti-file-pdf"
                    @click="openImageRightsDialog"
                >
                    Gerar autorização
                </v-clipped-button>
            </template>
        </DetailsPage>

        <v-dialog v-model="imageRightsDialog" max-width="760">
            <v-card>
                <v-card-title>Autorização de uso de imagem</v-card-title>
                <v-card-text>
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="imageRightsForm.image_description"
                                label="Descrição da imagem"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="imageRightsForm.image_material_type"
                                label="Tipo de material"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="imageRightsForm.image_usage_purpose"
                                label="Finalidade do uso"
                                rows="2"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="imageRightsForm.image_producer_name"
                                label="Produtor do material"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="imageRightsForm.site_owner_name"
                                label="Proprietário do site"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="imageRightsForm.site_name"
                                label="Nome do site"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="imageRightsForm.site_domain"
                                label="Domínio do site"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="imageRightsForm.forum_city"
                                label="Cidade do foro"
                                hide-details
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="
                                    imageRightsForm.legal_representative_relationship
                                "
                                label="Parentesco do responsável legal"
                                hide-details
                            />
                        </v-col>
                    </v-row>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="imageRightsDialog = false">
                        Cancelar
                    </v-btn>
                    <v-btn
                        color="primary"
                        prepend-icon="ti ti-file-pdf"
                        @click="generateImageRightsPdf"
                    >
                        Gerar PDF
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>
