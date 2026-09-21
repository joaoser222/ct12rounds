<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useDisplay } from 'vuetify';
import { useModulePermissions } from '@/composables/useModulePermissions';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

type SettingField = {
    id: number;
    name: string;
    label: string;
    content: string | number | boolean | null;
    object_type: string;
    input_type: string;
    select_object_name?: string | null;
    group: string | null;
};

const props = defineProps<{
    settings: SettingField[];
    routes: {
        update: string;
    };
}>();

const form = useForm<{
    settings: Record<string, string | number | boolean | null>;
}>({
    settings: Object.fromEntries(
        props.settings.map((setting) => [
            setting.name,
            normalizeValue(setting),
        ]),
    ),
});

const GROUP_LABEL: Record<string, string> = {
    billing: 'Faturamento',
    financial: 'Financeiro',
    peoples: 'Pessoas',
    landing: 'Landing page',
};

const GROUP_ORDER = ['billing', 'financial', 'peoples', 'landing'];

const tabs = computed(() => {
    const grouped = new Map<string, SettingField[]>();

    for (const setting of props.settings) {
        const key = setting.group ?? 'geral';
        grouped.set(key, [...(grouped.get(key) ?? []), setting]);
    }

    const keys = [
        ...GROUP_ORDER,
        ...[...grouped.keys()].filter((key) => !GROUP_ORDER.includes(key)),
    ];

    return keys
        .filter((key) => (grouped.get(key)?.length ?? 0) > 0)
        .map((key) => ({
            key,
            label: GROUP_LABEL[key] ?? key,
            settings: grouped.get(key)!,
        }));
});

const activeTab = ref(tabs.value[0]?.key ?? '');

const { mobile } = useDisplay();

const { hasPermission, ensurePermissionsLoaded } =
    useModulePermissions<'update'>({
        module: () => 'settings',
        permissions: () => undefined,
        permissionMap: () => undefined,
    });

const canSave = computed(() => {
    return (
        hasPermission('update') && !form.processing && props.settings.length > 0
    );
});

function normalizeValue(
    setting: SettingField,
): string | number | boolean | null {
    if (isSelectField(setting)) {
        return setting.content === '' ? null : setting.content;
    }

    if (setting.object_type === 'boolean' || setting.object_type === 'bool') {
        return (
            setting.content === true ||
            setting.content === '1' ||
            setting.content === 1
        );
    }

    if (
        ['integer', 'int', 'number', 'numeric', 'float', 'decimal'].includes(
            setting.object_type,
        ) &&
        setting.content !== null &&
        setting.content !== ''
    ) {
        const numericValue = Number(setting.content);

        return Number.isFinite(numericValue) ? numericValue : null;
    }

    return setting.content;
}

function isBooleanField(setting: SettingField): boolean {
    return setting.input_type === 'boolean' || setting.input_type === 'bool';
}

function isNumericField(setting: SettingField): boolean {
    return ['integer', 'int', 'number', 'numeric', 'float', 'decimal'].includes(
        setting.input_type,
    );
}

function isSelectField(setting: SettingField): boolean {
    return (
        setting.input_type === 'select' && Boolean(setting.select_object_name)
    );
}

function isTextareaField(setting: SettingField): boolean {
    return setting.object_type === 'textarea';
}

function submit(): void {
    if (!canSave.value) {
        return;
    }

    form.put(props.routes.update, {
        preserveScroll: true,
    });
}

onMounted(() => {
    void ensurePermissionsLoaded();
});
</script>

<template>
    <div>
        <div
            class="d-flex align-center justify-space-between ga-4 my-4 flex-wrap"
        >
            <div>
                <h1 class="text-h5 font-weight-medium">Configurações</h1>
                <p class="text-body-2 text-medium-emphasis mt-1">
                    Os campos abaixo são montados dinamicamente a partir das
                    configurações cadastradas.
                </p>
            </div>
        </div>

        <v-card class="overflow-hidden">
            <v-btn-group
                v-if="settings.length > 0"
                class="bg-secondary border-0 w-100"
                :class="mobile ? 'flex-column' : ''"
                :stretch="!mobile"
                style="border-radius: 0"
            >
                <v-btn
                    v-for="tab in tabs"
                    :key="tab.key"
                    :color="activeTab === tab.key ? 'primary' : undefined"
                    :variant="activeTab === tab.key ? 'flat' : 'text'"
                    class="text-none py-3"
                    height="auto"
                    @click="activeTab = tab.key"
                >
                    {{ tab.label }}
                </v-btn>
            </v-btn-group>

            <v-card-text>
                <v-alert
                    v-if="settings.length === 0"
                    type="info"
                    variant="tonal"
                    border="start"
                >
                    Nenhuma configuração foi cadastrada ainda.
                </v-alert>

                <v-window v-else v-model="activeTab">
                    <v-window-item
                        v-for="tab in tabs"
                        :key="tab.key"
                        :value="tab.key"
                    >
                        <v-form @submit.prevent="submit">
                            <v-row class="ma-0">
                                <v-col
                                    v-for="setting in tab.settings"
                                    :key="setting.id"
                                    cols="12"
                                    md="6"
                                >
                                    <v-switch
                                        v-if="isBooleanField(setting)"
                                        v-model="form.settings[setting.name]"
                                        :label="setting.label"
                                        color="primary"
                                        hide-details="auto"
                                        :error-messages="
                                            form.errors[
                                                `settings.${setting.name}`
                                            ]
                                        "
                                        :disabled="
                                            !hasPermission('update') ||
                                            form.processing
                                        "
                                    />

                                    <ServerAutocomplete
                                        v-else-if="isSelectField(setting)"
                                        v-model="form.settings[setting.name]"
                                        :object-name="
                                            setting.select_object_name!
                                        "
                                        :label="setting.label"
                                        clearable
                                        persistent-hint
                                        :error-messages="
                                            form.errors[
                                                `settings.${setting.name}`
                                            ]
                                        "
                                        :disabled="
                                            !hasPermission('update') ||
                                            form.processing
                                        "
                                    />

                                    <v-text-field
                                        v-else-if="isNumericField(setting)"
                                        v-model="form.settings[setting.name]"
                                        :label="setting.label"
                                        type="number"
                                        persistent-hint
                                        :error-messages="
                                            form.errors[
                                                `settings.${setting.name}`
                                            ]
                                        "
                                        :disabled="
                                            !hasPermission('update') ||
                                            form.processing
                                        "
                                    />

                                    <v-textarea
                                        v-else-if="isTextareaField(setting)"
                                        v-model="form.settings[setting.name]"
                                        :label="setting.label"
                                        rows="4"
                                        persistent-hint
                                        :error-messages="
                                            form.errors[
                                                `settings.${setting.name}`
                                            ]
                                        "
                                        :disabled="
                                            !hasPermission('update') ||
                                            form.processing
                                        "
                                    />

                                    <v-text-field
                                        v-else
                                        v-model="form.settings[setting.name]"
                                        :label="setting.label"
                                        persistent-hint
                                        :error-messages="
                                            form.errors[
                                                `settings.${setting.name}`
                                            ]
                                        "
                                        :disabled="
                                            !hasPermission('update') ||
                                            form.processing
                                        "
                                    />
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-window-item>
                </v-window>
            </v-card-text>
        </v-card>

        <div class="d-flex justify-end ga-2 pa-3">
            <v-clipped-button
                color="primary"
                prepend-icon="ti ti-device-floppy"
                :loading="form.processing"
                :disabled="!canSave"
                @click="submit"
            >
                Salvar configurações
            </v-clipped-button>
        </div>
    </div>
</template>
