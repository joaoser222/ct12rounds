<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';
import { formatDateTime } from '@/plugins/formatters';
import { useToast } from '@/composables/useToast';

defineOptions({ layout: AuthenticatedLayout });

type GatewaySetting = {
    key: string;
    label: string;
    type: string;
    required: boolean;
    default: unknown;
    options: { value: string; label: string }[] | null;
    placeholder: string | null;
    helpText: string | null;
};

type GatewayDefinition = {
    name: string;
    description: string;
    settings: GatewaySetting[];
};

type GatewayAccount = {
    id?: number;
    name?: string;
    description?: string;
    settings?: Record<string, any>;
    invoicing_enabled?: boolean;
    invoicing_supported?: boolean;
    invoicing_configured?: boolean;
};

type MunicipalItem = {
    value: string;
    title: string;
};

const props = defineProps<{
    gatewayAccount?: GatewayAccount | null;
    routes: DetailsRoutes;
}>();

const sharedProps = usePage().props as { options?: { definitions?: GatewayDefinition[]; providers?: { value: string; label: string; description: string }[] } };
const definitions = sharedProps.options?.definitions ?? [];
const providers = sharedProps.options?.providers ?? [];

const defaults = {
    name: '',
    description: '',
    settings: {} as Record<string, unknown>,
    invoicing_enabled: false,
};

const isEditing = computed(() => props.gatewayAccount?.id != null);

const selectedProvider = ref(props.gatewayAccount?.name ?? '');

const currentDefinition = computed<GatewayDefinition | undefined>(() =>
    definitions.find((d) => d.name === selectedProvider.value),
);

const { show: showToast } = useToast();

const accountId = computed(() => props.gatewayAccount?.id);
const supportsInvoicing = computed(() => props.gatewayAccount?.invoicing_supported === true);
const invoicingEnabled = computed(() => props.gatewayAccount?.invoicing_enabled === true);

const municipalRoutes = computed(() => {
    const id = accountId.value;

    if (id == null) {
        return null;
    }

    return {
        options: props.routes.municipalOptions?.replace(':id', String(id)),
        services: props.routes.municipalServices?.replace(':id', String(id)),
        configuration: props.routes.municipalConfiguration?.replace(':id', String(id)),
    };
});

const municipalForm = ref({
    municipal_service_code: '',
    municipal_service_name: '',
    service_description: '',
    observations: '',
    incentivized_tax: false,
});

const fiscalConfiguredAt = ref<string | null>(null);
const municipalStates = ref<MunicipalItem[]>([]);
const municipalCities = ref<MunicipalItem[]>([]);
const municipalServices = ref<MunicipalItem[]>([]);
const selectedState = ref('');
const selectedCity = ref('');
const selectedServiceCode = ref('');
const loadingMunicipalOptions = ref(false);
const loadingMunicipalServices = ref(false);
const savingMunicipal = ref(false);
const municipalError = ref('');
const municipalErrors = ref<Record<string, string[]>>({});

const fiscalState = computed(() => {
    if (savingMunicipal.value) {
        return 'configuring';
    }

    if (fiscalConfiguredAt.value || props.gatewayAccount?.invoicing_configured === true) {
        return 'configured';
    }

    return 'not_configured';
});

const showMunicipalSection = computed(() => isEditing.value && supportsInvoicing.value);

function normalizeMunicipalItems(data: unknown): MunicipalItem[] {
    if (!Array.isArray(data)) {
        return [];
    }

    return data
        .filter((item): item is Record<string, unknown> => typeof item === 'object' && item !== null)
        .map((item) => ({
            value: String(item.municipalCode ?? item.municipal_code ?? item.id ?? item.name ?? ''),
            title: String(item.municipalName ?? item.municipal_name ?? item.name ?? item.code ?? ''),
        }))
        .filter((item) => item.value !== '' && item.title !== '');
}

function normalizeServiceItems(data: unknown): MunicipalItem[] {
    if (!Array.isArray(data)) {
        return [];
    }

    return data
        .filter((item): item is Record<string, unknown> => typeof item === 'object' && item !== null)
        .map((item) => ({
            value: String(item.id ?? item.code ?? item.municipalServiceCode ?? ''),
            title: String(item.name ?? item.description ?? item.code ?? ''),
        }))
        .filter((item) => item.value !== '' && item.title !== '');
}

function extractCollection(data: any, keys: string[]): unknown {
    for (const key of keys) {
        if (Array.isArray(data?.[key])) {
            return data[key];
        }
    }

    if (Array.isArray(data)) {
        return data;
    }

    return data?.data;
}

async function loadMunicipalOptions(): Promise<void> {
    if (!municipalRoutes.value?.options || loadingMunicipalOptions.value) {
        return;
    }

    loadingMunicipalOptions.value = true;
    municipalError.value = '';

    try {
        const response = await fetch(municipalRoutes.value.options, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Falha ao carregar as opções municipais.');
        }

        const data = await response.json();
        const items = normalizeMunicipalItems(extractCollection(data, ['municipalOptions']));

        const states = new Map<string, string>();

        for (const item of items) {
            const state = String(item.title.split(' - ').at(-1) ?? '').trim();

            if (state) {
                states.set(state, state);
            }
        }

        municipalStates.value = [...states.entries()].map(([value]) => ({ value, title: value }));
        municipalCities.value = items;
    } catch (error) {
        municipalError.value = error instanceof Error ? error.message : 'Falha ao carregar as opções municipais.';
    } finally {
        loadingMunicipalOptions.value = false;
    }
}

async function loadMunicipalServices(): Promise<void> {
    if (!municipalRoutes.value?.services || loadingMunicipalServices.value) {
        return;
    }

    loadingMunicipalServices.value = true;
    municipalError.value = '';

    try {
        const params = new URLSearchParams();

        if (selectedState.value) {
            params.set('state', selectedState.value);
        }

        if (selectedCity.value) {
            params.set('city', selectedCity.value);
        }

        const response = await fetch(`${municipalRoutes.value.services}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error('Falha ao carregar os serviços municipais.');
        }

        const data = await response.json();
        municipalServices.value = normalizeServiceItems(extractCollection(data, ['municipalServices', 'services']));
    } catch (error) {
        municipalError.value = error instanceof Error ? error.message : 'Falha ao carregar os serviços municipais.';
    } finally {
        loadingMunicipalServices.value = false;
    }
}

function onServiceSelected(value: string | null): void {
    if (!value) {
        return;
    }

    const service = municipalServices.value.find((item) => item.value === value);

    if (service) {
        municipalForm.value.municipal_service_code = service.value;
        municipalForm.value.municipal_service_name = service.title;
    }
}

async function saveMunicipalConfiguration(): Promise<void> {
    if (!municipalRoutes.value?.configuration) {
        return;
    }

    if (!municipalForm.value.municipal_service_code || !municipalForm.value.service_description) {
        municipalError.value = 'Informe o código do serviço municipal e a descrição do serviço.';

        return;
    }

    savingMunicipal.value = true;
    municipalError.value = '';
    municipalErrors.value = {};

    try {
        const response = await fetch(municipalRoutes.value.configuration, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/)?.[1] ?? ''),
            },
            body: JSON.stringify(municipalForm.value),
        });

        if (!response.ok) {
            const body = await response.json().catch(() => ({}));
            const message = body?.message ?? 'Falha ao salvar a configuração municipal.';

            if (body?.errors && typeof body.errors === 'object') {
                municipalErrors.value = Object.fromEntries(
                    Object.entries(body.errors).map(([field, messages]) => [
                        field,
                        Array.isArray(messages) ? messages.map(String) : [String(messages)],
                    ]),
                );
            }

            throw new Error(message);
        }

        const body = await response.json();
        fiscalConfiguredAt.value = new Date().toISOString();
        showToast({ type: 'success', message: 'Configuração municipal salva com sucesso.' });

        if (body?.configuration && typeof body.configuration === 'object') {
            const configuration = body.configuration as Record<string, unknown>;

            if (configuration.municipalServiceCode) {
                municipalForm.value.municipal_service_code = String(configuration.municipalServiceCode);
            }

            if (configuration.serviceDescription) {
                municipalForm.value.service_description = String(configuration.serviceDescription);
            }
        }
    } catch (error) {
        municipalError.value = error instanceof Error ? error.message : 'Falha ao salvar a configuração municipal.';
    } finally {
        savingMunicipal.value = false;
    }
}

onMounted(() => {
    const settings = props.gatewayAccount?.settings ?? {};

    if (settings.invoicing && typeof settings.invoicing === 'object') {
        const invoicing = settings.invoicing as Record<string, unknown>;
        municipalForm.value.municipal_service_code = String(invoicing.municipal_service_code ?? '');
        municipalForm.value.municipal_service_name = String(invoicing.municipal_service_name ?? '');
        municipalForm.value.service_description = String(invoicing.service_description ?? '');
        municipalForm.value.observations = String(invoicing.observations ?? '');
        municipalForm.value.incentivized_tax = invoicing.incentivized_tax === true;
        fiscalConfiguredAt.value = typeof invoicing.fiscal_configuration_at === 'string'
            ? invoicing.fiscal_configuration_at
            : null;
    }

    if (showMunicipalSection.value) {
        loadMunicipalOptions();
    }
});
</script>

<template>
    <DetailsPage
        title="Gateway"
        :item="gatewayAccount"
        :defaults="defaults"
        :routes="routes"
        module="gateway_accounts"
    >
        <template #default="{ form, errors, isCreating }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-select
                        v-if="isCreating"
                        v-model="selectedProvider"
                        label="Provedor"
                        :items="providers"
                        item-title="label"
                        item-value="value"
                        :rules="[required]"
                        :error-messages="errors.name"
                        @update:model-value="
                            (val) => {
                                form.name = val;
                                const def = definitions.find((d) => d.name === val);
                                if (def) {
                                    for (const setting of def.settings) {
                                        if (!(setting.key in form.settings)) {
                                            form.settings[setting.key] = setting.default ?? '';
                                        }
                                    }
                                }
                            }
                        "
                    />
                    <v-text-field
                        v-else
                        v-model="form.name"
                        label="Provedor"
                        readonly
                        :error-messages="errors.name"
                    />
                </v-col>

                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.description"
                        label="Descrição"
                        :error-messages="errors.description"
                    />
                </v-col>
            </v-row>

            <v-divider class="my-4" />

            <v-switch
                v-model="form.invoicing_enabled"
                label="Habilitar emissão de notas fiscais"
                color="primary"
                hint="Permite solicitar notas fiscais para cobranças deste gateway."
                persistent-hint
                @update:model-value="
                    (enabled) => {
                        if (enabled && !form.settings.invoicing) {
                            form.settings.invoicing = {};
                        }
                    }
                "
            />

            <v-row v-if="form.invoicing_enabled && form.settings.invoicing" class="ma-0 mt-2">
                <v-col cols="12">
                    <v-textarea
                        v-model="form.settings.invoicing.service_description"
                        label="Descrição do serviço fiscal"
                        hint="Configuração enviada ao provedor no momento da solicitação."
                        persistent-hint
                        :error-messages="errors['settings.invoicing.service_description']"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.settings.invoicing.municipal_service_id"
                        label="ID do serviço municipal"
                        :error-messages="errors['settings.invoicing.municipal_service_id']"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.settings.invoicing.municipal_service_code"
                        label="Código do serviço municipal"
                        :error-messages="errors['settings.invoicing.municipal_service_code']"
                    />
                </v-col>
            </v-row>

            <v-card v-if="showMunicipalSection" variant="tonal" class="mt-4">
                <v-card-title class="d-flex align-center ga-2">
                    <v-icon icon="ti ti-building-community" />
                    Configuração Municipal Fiscal
                </v-card-title>
                <v-card-subtitle>
                    Dados necessários para emissão de NFS-e pelo provedor.
                </v-card-subtitle>
                <v-card-text>
                    <v-alert
                        :type="fiscalState === 'configured' ? 'success' : 'info'"
                        variant="tonal"
                        class="mb-4"
                        density="compact"
                    >
                        <template v-if="fiscalState === 'configured'">
                            Configuração fiscal <strong>efetivada</strong>
                            <span v-if="fiscalConfiguredAt"> em {{ formatDateTime(fiscalConfiguredAt) }}</span>.
                        </template>
                        <template v-else-if="fiscalState === 'configuring'">
                            Salvando configuração municipal...
                        </template>
                        <template v-else>
                            <strong>Não configurada.</strong> Preencha os dados abaixo e salve antes de emitir
                            notas fiscais. Situações possíveis da nota: pendente, autorizada, rejeitada, cancelada
                            ou falha — acompanhe no módulo de Notas Fiscais.
                        </template>
                    </v-alert>

                    <v-alert v-if="municipalError" type="error" variant="tonal" density="compact" class="mb-4">
                        {{ municipalError }}
                    </v-alert>

                    <v-form @submit.prevent="saveMunicipalConfiguration">
                        <v-row class="ma-0">
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="selectedState"
                                    label="UF"
                                    :items="municipalStates"
                                    item-title="title"
                                    item-value="value"
                                    :loading="loadingMunicipalOptions"
                                    clearable
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="selectedCity"
                                    label="Município"
                                    :items="municipalCities"
                                    item-title="title"
                                    item-value="value"
                                    :loading="loadingMunicipalOptions"
                                    clearable
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="selectedServiceCode"
                                    label="Serviço municipal"
                                    :items="municipalServices"
                                    item-title="title"
                                    item-value="value"
                                    :loading="loadingMunicipalServices"
                                    clearable
                                    no-data-text="Consulte os serviços após selecionar UF/município."
                                    @update:model-value="onServiceSelected"
                                    @click:append="loadMunicipalServices"
                                />
                            </v-col>
                            <v-col cols="12" md="6" class="d-flex align-center">
                                <v-btn
                                    variant="outlined"
                                    color="primary"
                                    :loading="loadingMunicipalServices"
                                    :disabled="!selectedState && !selectedCity"
                                    prepend-icon="ti ti-refresh"
                                    @click="loadMunicipalServices"
                                >
                                    Consultar serviços
                                </v-btn>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="municipalForm.municipal_service_code"
                                    label="Código do serviço municipal"
                                    :rules="[required]"
                                    required
                                    :error-messages="municipalErrors.municipal_service_code"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="municipalForm.municipal_service_name"
                                    v-text-case="'capitalize'"
                                    label="Nome do serviço municipal"
                                    :error-messages="municipalErrors.municipal_service_name"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="municipalForm.service_description"
                                    label="Descrição do serviço"
                                    :rules="[required]"
                                    required
                                    :error-messages="municipalErrors.service_description"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="municipalForm.observations"
                                    label="Observações"
                                    :error-messages="municipalErrors.observations"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-switch
                                    v-model="municipalForm.incentivized_tax"
                                    label="Tributação incentivada"
                                    color="primary"
                                    hint="Marque quando o serviço for enquadrado em regime de tributação incentivada."
                                    persistent-hint
                                    :error-messages="municipalErrors.incentivized_tax"
                                />
                            </v-col>
                        </v-row>

                        <div class="d-flex justify-end ga-2 mt-2">
                            <v-btn
                                variant="tonal"
                                color="secondary"
                                prepend-icon="ti ti-arrow-left"
                                @click="router.visit(props.routes.index ?? '/gateway-accounts')"
                            >
                                Voltar
                            </v-btn>
                            <v-btn
                                color="primary"
                                type="submit"
                                :loading="savingMunicipal"
                                :disabled="!invoicingEnabled"
                                prepend-icon="ti ti-device-floppy"
                            >
                                Salvar configuração fiscal
                            </v-btn>
                        </div>
                    </v-form>
                </v-card-text>
            </v-card>

            <h3 class="text-subtitle-1 font-weight-medium mb-3">
                Configurações
            </h3>

            <v-row v-if="currentDefinition" class="ma-0">
                <v-col
                    v-for="setting in currentDefinition.settings"
                    :key="setting.key"
                    cols="12"
                    md="6"
                >
                    <v-select
                        v-if="setting.type === 'select'"
                        v-model="form.settings[setting.key]"
                        :label="setting.label"
                        :items="setting.options ?? []"
                        item-title="label"
                        item-value="value"
                        :rules="setting.required ? [required] : []"
                        :error-messages="errors[`settings.${setting.key}`]"
                        :persistent-hint="!!setting.helpText"
                        :hint="setting.helpText"
                    />
                    <v-text-field
                        v-else-if="setting.type === 'password'"
                        v-model="form.settings[setting.key]"
                        :label="setting.label"
                        type="password"
                        :rules="setting.required ? [required] : []"
                        :placeholder="setting.placeholder"
                        :error-messages="errors[`settings.${setting.key}`]"
                        :persistent-hint="!!setting.helpText"
                        :hint="setting.helpText"
                    />
                    <v-text-field
                        v-else
                        v-model="form.settings[setting.key]"
                        :label="setting.label"
                        :rules="setting.required ? [required] : []"
                        :placeholder="setting.placeholder"
                        :error-messages="errors[`settings.${setting.key}`]"
                        :persistent-hint="!!setting.helpText"
                        :hint="setting.helpText"
                    />
                </v-col>

                <v-col v-if="currentDefinition.settings.length === 0" cols="12">
                    <v-alert type="info" variant="tonal">
                        Nenhuma configuração adicional necessária para este provedor.
                    </v-alert>
                </v-col>
            </v-row>

            <v-row v-else class="ma-0">
                <v-col cols="12">
                    <v-alert type="info" variant="tonal">
                        Selecione um provedor para configurar.
                    </v-alert>
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
