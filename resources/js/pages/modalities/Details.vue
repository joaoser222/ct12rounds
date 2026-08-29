<script setup lang="ts">
import { ref, watch } from 'vue';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type Modality = {
    id?: number;
    name?: string;
    color?: string;
    icon?: string | null;
    icon_url?: string | null;
};

const props = defineProps<{
    modality?: Modality | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    name: '',
    color: '',
    icon: null,
    remove_icon: false,
};

const iconFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);

const iconTypes = ['image/png', 'image/jpeg'];
const validIcon = (value: File | File[] | null) => {
    if (!value) {
        return true;
    }

    const file = Array.isArray(value) ? value[0] : value;

    return iconTypes.includes(file.type) || 'Permitido apenas PNG ou JPG';
};
const iconSize = (value: File | File[] | null) => {
    if (!value) {
        return true;
    }

    const file = Array.isArray(value) ? value[0] : value;

    return file.size <= 2 * 1024 * 1024 || 'A imagem deve ter no máximo 2MB';
};

watch(iconFile, (file) => {
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    } else {
        previewUrl.value = null;
    }
});
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
                <v-col cols="12" md="12">
                    <v-file-upload
                        v-model="iconFile"
                        title="Ícone"
                        subtitle="Arraste a imagem para cá"
                        divider-text="ou"
                        browse-text="Escolher arquivo"
                        icon="ti ti-photo"
                        :error-messages="errors.icon"
                        :rules="[validIcon, iconSize]"
                        filter-by-type="image/png,image/jpeg"
                        show-size
                        clearable
                        @update:model-value="
                            form.icon = iconFile;
                            form.remove_icon = !iconFile && !!modality?.icon;
                        "
                    />
                    <div
                        v-if="
                            previewUrl ||
                            (modality?.icon_url && !form.remove_icon)
                        "
                        class="d-flex align-center ga-3 mt-4"
                    >
                        <v-img
                            :src="previewUrl ?? modality?.icon_url"
                            width="64"
                            height="64"
                            cover
                            rounded
                        />
                        <span class="text-caption text-medium-emphasis">
                            Pré-visualização do ícone
                        </span>
                    </div>
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
