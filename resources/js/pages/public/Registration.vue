<script setup lang="ts">
import CreditCardField from '@/components/CreditCardField.vue';
import { useForm } from '@inertiajs/vue3';
import { masks, phoneMask } from '@/plugins/masks';
import {
    cep,
    cpf,
    email,
    exactLength,
    phone,
    required,
} from '@/plugins/validators';
import { fillAddressFromCep, type AddressForm } from '@/plugins/viacep';
import { computed, onMounted, ref, watch } from 'vue';

defineOptions({ layout: null });

type PlanSummary = {
    id: number;
    name: string;
    public_slug: string;
};

type ContractRegistration = {
    id: number;
    token: string;
    plan?: string | null;
    preview_url?: string | null;
};

type PrefilledData = {
    name?: string;
    email?: string;
    phone?: string;
};

const props = defineProps<{
    plan?: PlanSummary | null;
    requiresLegalRepresentative?: boolean;
    coupon?: string | null;
    couponWarning?: string | null;
    initial?: PrefilledData | null;
    contract?: ContractRegistration | null;
    success?: boolean;
    retryClientId?: number | null;
    privacyNotice?: string | null;
}>();

const currentStep = ref<'clientData' | 'paymentData'>('clientData');
const isLoadingAddress = ref(false);
const contractPreviewDialog = ref(false);
const contractPreviewContent = ref('');
const contractPreviewError = ref('');
const isLoadingContractPreview = ref(false);
const privacyDialog = ref(false);
const hasReadPrivacyNotice = ref(!props.privacyNotice);

const isContractFlow = computed(() => !!props.contract);
const isRetry = computed(() => !!props.retryClientId);

const form = useForm({
    name: props.initial?.name ?? '',
    email: props.initial?.email ?? '',
    phone: props.initial?.phone ?? '',
    document: '',
    gender: '',
    birth_date: '',
    address: '',
    address_number: '',
    address_complement: '',
    address_district: '',
    address_state: '',
    address_city: '',
    address_postal_code: '',
    plan: props.plan?.public_slug ?? '',
    coupon: props.coupon ?? '',
    contract: props.contract?.token ?? '',
    accepted: false,
    audience_category: props.requiresLegalRepresentative ? 'child' : 'adult',
    legal_representative_name: '',
    legal_representative_document: '',
    legal_representative_birth_date: '',
    card_number: '',
    card_expiry_month: '',
    card_expiry_year: '',
    card_cvv: '',
    card_holder_name: '',
    is_contract_flow: isContractFlow.value,
});

const isAddressRequired = computed(() => isContractFlow.value);

const isEmailValid = (value: string) => email(value) === true;
const isCpfValid = (value: string) => cpf(value) === true;
const isCepValid = (value: string) => cep(value) === true;

const clientDataValid = computed(() => {
    if (required(form.name) !== true) return false;
    if (required(form.email) !== true || !isEmailValid(form.email))
        return false;
    if (required(form.phone) !== true || phone(form.phone) !== true)
        return false;
    if (required(form.document) !== true || !isCpfValid(form.document))
        return false;

    if (isContractFlow.value) {
        if (required(form.gender) !== true) return false;
        if (required(form.birth_date) !== true) return false;
        if (
            required(form.address_postal_code) !== true ||
            !isCepValid(form.address_postal_code)
        )
            return false;
        if (required(form.address) !== true) return false;
        if (required(form.address_number) !== true) return false;
        if (required(form.address_district) !== true) return false;
        if (required(form.address_city) !== true) return false;
        if (
            required(form.address_state) !== true ||
            exactLength(2)(form.address_state) !== true
        )
            return false;
    }

    if (
        props.requiresLegalRepresentative &&
        form.audience_category === 'child'
    ) {
        if (required(form.legal_representative_name) !== true) return false;
        if (
            required(form.legal_representative_document) !== true ||
            !isCpfValid(form.legal_representative_document)
        )
            return false;
        if (required(form.legal_representative_birth_date) !== true)
            return false;
    }

    return true;
});

const paymentDataValid = computed(() => {
    if (!form.card_number || form.card_number.replace(/\D/g, '').length < 13)
        return false;
    if (!form.card_expiry_month || form.card_expiry_month.length < 2)
        return false;
    if (!form.card_expiry_year || form.card_expiry_year.length < 4)
        return false;
    if (!form.card_cvv || form.card_cvv.length < 3) return false;
    if (!form.card_holder_name) return false;
    if (!form.accepted) return false;
    return true;
});

const goToPaymentData = () => {
    if (!clientDataValid.value) {
        return;
    }

    form.post('/register/prepare-client', {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            currentStep.value = 'paymentData';
        },
    });
};

const goToClientData = () => {
    currentStep.value = 'clientData';
};

watch(
    () => form.errors.document,
    (message) => {
        if (message) {
            currentStep.value = 'clientData';
        }
    },
);

function openContractPreview(): void {
    if (!props.contract?.preview_url) {
        return;
    }

    contractPreviewDialog.value = true;
    contractPreviewError.value = '';

    if (contractPreviewContent.value !== '') {
        return;
    }

    isLoadingContractPreview.value = true;

    void loadContractPreview(props.contract.preview_url);
}

async function loadContractPreview(url: string): Promise<void> {
    try {
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            throw new Error(
                'Não foi possível carregar as cláusulas do contrato.',
            );
        }

        const body = await response.json();

        contractPreviewContent.value = String(body?.content ?? '');
    } catch (error) {
        contractPreviewError.value =
            error instanceof Error
                ? error.message
                : 'Não foi possível carregar as cláusulas do contrato.';
    } finally {
        isLoadingContractPreview.value = false;
    }
}

const submitPreRegistration = () => {
    if (!clientDataValid.value || !hasReadPrivacyNotice.value) return;
    form.post('/register');
};

const submitContractRegistration = () => {
    if (!paymentDataValid.value) return;
    form.post('/register');
};

const submitRetryPayment = () => {
    if (!paymentDataValid.value) return;
    form.post('/register/retry-payment');
};

function openPrivacyNotice(): void {
    privacyDialog.value = true;
}

function acknowledgePrivacyNotice(): void {
    hasReadPrivacyNotice.value = true;
    privacyDialog.value = false;
}

onMounted(() => {
    if (props.privacyNotice) {
        privacyDialog.value = true;
    }
});

async function fillAddress(): Promise<void> {
    if (isLoadingAddress.value) {
        return;
    }

    isLoadingAddress.value = true;

    try {
        await fillAddressFromCep(
            form as AddressForm,
            String(form.address_postal_code ?? ''),
        );
    } finally {
        isLoadingAddress.value = false;
    }
}
</script>

<template>
    <v-main
        v-cloak
        theme="dark"
        class="d-flex align-center justify-center"
        style="min-height: 100vh"
    >
        <v-container
            fluid
            class="fill-height d-flex align-center justify-center pa-4"
        >
            <v-card width="640" color="secondary">
                <v-card-text class="pa-8">
                    <h1 class="text-h6 font-weight-medium text-center mb-1">
                        {{
                            isContractFlow
                                ? `Cadastro no plano ${plan?.name ?? contract?.plan ?? ''}`
                                : 'Pré-cadastro'
                        }}
                    </h1>
                    <p
                        class="text-body-2 text-medium-emphasis text-center mb-6"
                    >
                        {{
                            isContractFlow
                                ? `Preencha seus dados para garantir sua vaga no plano ${plan?.name ?? contract?.plan ?? ''}.`
                                : 'Deixe seus dados para nossa equipe entrar em contato o mais rápido possível.'
                        }}
                    </p>

                    <v-alert
                        v-if="success"
                        type="success"
                        variant="tonal"
                        class="mb-4"
                    >
                        Cadastro realizado com sucesso! Nossa equipe entrará em
                        contato em breve.
                    </v-alert>

                    <v-alert
                        v-if="coupon"
                        type="info"
                        variant="tonal"
                        class="mb-4"
                    >
                        Cupom promocional aplicado:
                        <strong>{{ coupon }}</strong>
                        <span v-if="!isContractFlow">
                            Seu desconto será mantido na hora da contratação.
                        </span>
                    </v-alert>

                    <v-alert
                        v-if="couponWarning"
                        type="warning"
                        variant="tonal"
                        class="mb-4"
                    >
                        {{ couponWarning }}
                    </v-alert>

                    <!-- Pré-cadastro: formulário simples -->
                    <v-form
                        v-if="!isContractFlow"
                        @submit.prevent="submitPreRegistration"
                    >
                        <v-text-field
                            v-model="form.name"
                            v-text-case="'capitalize-exclusive'"
                            label="Nome completo"
                            :rules="[required]"
                            :error-messages="form.errors.name"
                            class="mb-5"
                        />
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.email"
                                    label="E-mail"
                                    type="email"
                                    :rules="[required, email]"
                                    :error-messages="form.errors.email"
                                    class="mb-5"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <MaskedTextField
                                    v-model="form.phone"
                                    label="Telefone"
                                    :mask="phoneMask(form.phone)"
                                    :rules="[required, phone]"
                                    :error-messages="form.errors.phone"
                                    class="mb-5"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <MaskedTextField
                                    v-model="form.document"
                                    label="CPF"
                                    :mask="masks.cpf"
                                    :rules="[required, cpf]"
                                    :error-messages="form.errors.document"
                                    class="mb-5"
                                />
                            </v-col>
                        </v-row>

                        <div
                            v-if="privacyNotice"
                            class="d-flex align-center flex-wrap ga-2 mb-5"
                        >
                            <v-btn
                                variant="text"
                                size="small"
                                prepend-icon="ti ti-shield-lock-outline"
                                class="pa-0"
                                @click="openPrivacyNotice"
                            >
                                Aviso de privacidade
                            </v-btn>
                            <v-chip
                                v-if="!hasReadPrivacyNotice"
                                size="small"
                                color="warning"
                                variant="tonal"
                            >
                                Confirme a leitura para continuar
                            </v-chip>
                        </div>

                        <v-clipped-button
                            type="submit"
                            color="primary"
                            block
                            class="cta-finalize"
                            :loading="form.processing"
                            :disabled="
                                form.processing ||
                                !clientDataValid ||
                                !hasReadPrivacyNotice
                            "
                        >
                            Finalizar
                        </v-clipped-button>
                    </v-form>

                    <!-- Cadastro com contrato: multi-step -->
                    <template v-else>
                        <v-form
                            @submit.prevent="
                                isRetry
                                    ? submitRetryPayment()
                                    : submitContractRegistration()
                            "
                        >
                            <template
                                v-if="!isRetry && currentStep === 'clientData'"
                            >
                                <v-text-field
                                    v-model="form.name"
                                    v-text-case="'capitalize-exclusive'"
                                    label="Nome completo"
                                    :rules="[required]"
                                    :error-messages="form.errors.name"
                                    class="mb-5"
                                />
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.email"
                                            label="E-mail"
                                            type="email"
                                            :rules="[required, email]"
                                            :error-messages="form.errors.email"
                                            class="mb-5"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <MaskedTextField
                                            v-model="form.phone"
                                            label="Telefone"
                                            :mask="phoneMask(form.phone)"
                                            :rules="[required, phone]"
                                            :error-messages="form.errors.phone"
                                            class="mb-5"
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" md="6">
                                        <MaskedTextField
                                            v-model="form.document"
                                            label="CPF"
                                            :mask="masks.cpf"
                                            :rules="[required, cpf]"
                                            :error-messages="
                                                form.errors.document
                                            "
                                            class="mb-5"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.gender"
                                            label="Gênero"
                                            :items="[
                                                {
                                                    title: 'Masculino',
                                                    value: 'M',
                                                },
                                                {
                                                    title: 'Feminino',
                                                    value: 'F',
                                                },
                                            ]"
                                            :rules="[required]"
                                            :error-messages="form.errors.gender"
                                            class="mb-5"
                                        />
                                    </v-col>
                                </v-row>

                                <v-text-field
                                    v-model="form.birth_date"
                                    label="Data de nascimento"
                                    type="date"
                                    :rules="[required]"
                                    :error-messages="form.errors.birth_date"
                                    class="mb-5"
                                />

                                <template v-if="isAddressRequired">
                                    <MaskedTextField
                                        v-model="form.address_postal_code"
                                        label="CEP"
                                        :mask="masks.cep"
                                        :rules="[required, cep]"
                                        :loading="isLoadingAddress"
                                        :error-messages="
                                            form.errors.address_postal_code
                                        "
                                        class="mb-5"
                                        @blur="fillAddress"
                                    />
                                    <v-text-field
                                        v-model="form.address"
                                        label="Endereço (rua)"
                                        :rules="[required]"
                                        :error-messages="form.errors.address"
                                        class="mb-5"
                                    />
                                    <v-row>
                                        <v-col cols="12" md="4">
                                            <v-text-field
                                                v-model="form.address_number"
                                                label="Número"
                                                :rules="[required]"
                                                :error-messages="
                                                    form.errors.address_number
                                                "
                                                class="mb-5"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="8">
                                            <v-text-field
                                                v-model="
                                                    form.address_complement
                                                "
                                                label="Complemento"
                                                :error-messages="
                                                    form.errors
                                                        .address_complement
                                                "
                                                class="mb-5"
                                            />
                                        </v-col>
                                    </v-row>
                                    <v-text-field
                                        v-model="form.address_district"
                                        label="Bairro"
                                        :rules="[required]"
                                        :error-messages="
                                            form.errors.address_district
                                        "
                                        class="mb-5"
                                    />
                                    <v-row>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                v-model="form.address_city"
                                                label="Cidade"
                                                readonly
                                                :rules="[required]"
                                                :error-messages="
                                                    form.errors.address_city
                                                "
                                                class="mb-5"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                v-model="form.address_state"
                                                label="UF"
                                                maxlength="2"
                                                readonly
                                                :rules="[
                                                    required,
                                                    exactLength(2),
                                                ]"
                                                :error-messages="
                                                    form.errors.address_state
                                                "
                                                class="mb-5"
                                            />
                                        </v-col>
                                    </v-row>
                                </template>

                                <template v-if="requiresLegalRepresentative">
                                    <v-divider class="my-4">
                                        <strong>Responsável Legal</strong>
                                    </v-divider>

                                    <p
                                        class="text-body-2 text-medium-emphasis mb-3"
                                    >
                                        Para menores de idade, é necessário
                                        informar os dados do responsável legal.
                                    </p>

                                    <v-text-field
                                        v-model="form.legal_representative_name"
                                        v-text-case="'capitalize-exclusive'"
                                        label="Nome do responsável"
                                        :rules="[required]"
                                        :error-messages="
                                            form.errors
                                                .legal_representative_name
                                        "
                                        class="mb-5"
                                    />
                                    <v-row>
                                        <v-col cols="12" md="6">
                                            <MaskedTextField
                                                v-model="
                                                    form.legal_representative_document
                                                "
                                                label="CPF do responsável"
                                                :mask="masks.cpf"
                                                :rules="[required, cpf]"
                                                :error-messages="
                                                    form.errors
                                                        .legal_representative_document
                                                "
                                                class="mb-5"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                v-model="
                                                    form.legal_representative_birth_date
                                                "
                                                label="Nascimento do responsável"
                                                type="date"
                                                :rules="[required]"
                                                :error-messages="
                                                    form.errors
                                                        .legal_representative_birth_date
                                                "
                                                class="mb-5"
                                            />
                                        </v-col>
                                    </v-row>
                                </template>

                                <v-btn
                                    color="primary"
                                    size="large"
                                    block
                                    :loading="form.processing"
                                    :disabled="
                                        !clientDataValid || form.processing
                                    "
                                    @click="goToPaymentData"
                                >
                                    Avançar
                                </v-btn>
                            </template>

                            <template
                                v-if="isRetry || currentStep === 'paymentData'"
                            >
                                <v-divider class="mb-4">
                                    <strong>Dados do Cartão de Crédito</strong>
                                </v-divider>

                                <CreditCardField
                                    v-model:card-number="form.card_number"
                                    v-model:card-expiry-month="
                                        form.card_expiry_month
                                    "
                                    v-model:card-expiry-year="
                                        form.card_expiry_year
                                    "
                                    v-model:card-cvv="form.card_cvv"
                                    v-model:card-holder-name="
                                        form.card_holder_name
                                    "
                                    :errors="{
                                        card_number: form.errors.card_number
                                            ? [form.errors.card_number]
                                            : [],
                                        card_expiry_month: form.errors
                                            .card_expiry_month
                                            ? [form.errors.card_expiry_month]
                                            : [],
                                        card_expiry_year: form.errors
                                            .card_expiry_year
                                            ? [form.errors.card_expiry_year]
                                            : [],
                                        card_cvv: form.errors.card_cvv
                                            ? [form.errors.card_cvv]
                                            : [],
                                        card_holder_name: form.errors
                                            .card_holder_name
                                            ? [form.errors.card_holder_name]
                                            : [],
                                    }"
                                />

                                <v-checkbox
                                    v-model="form.accepted"
                                    label="Li e aceito os termos do contrato."
                                    :error-messages="form.errors.accepted"
                                    color="primary"
                                    class="mb-2"
                                    @click="openContractPreview"
                                />

                                <v-btn
                                    v-if="contract?.preview_url"
                                    variant="text"
                                    size="small"
                                    prepend-icon="ti ti-file-document-outline"
                                    class="mb-4"
                                    @click="openContractPreview"
                                >
                                    Visualizar contrato
                                </v-btn>

                                <v-row class="mt-4">
                                    <v-col v-if="!isRetry" cols="6">
                                        <v-btn
                                            variant="outlined"
                                            size="large"
                                            block
                                            @click="goToClientData"
                                        >
                                            Voltar
                                        </v-btn>
                                    </v-col>
                                    <v-col :cols="isRetry ? 12 : 6">
                                        <v-btn
                                            type="submit"
                                            color="primary"
                                            size="large"
                                            block
                                            :loading="form.processing"
                                            :disabled="
                                                form.processing ||
                                                !paymentDataValid
                                            "
                                        >
                                            {{
                                                isRetry
                                                    ? 'Tentar novamente'
                                                    : 'Finalizar cadastro'
                                            }}
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </template>
                        </v-form>
                    </template>
                </v-card-text>
            </v-card>
        </v-container>

        <v-dialog v-model="contractPreviewDialog" max-width="900" scrollable>
            <v-card>
                <v-card-title class="d-flex align-center ga-2">
                    <v-icon icon="ti ti-file-document-outline" />
                    Cláusulas do contrato
                </v-card-title>
                <v-card-text>
                    <v-alert
                        v-if="contractPreviewError"
                        type="error"
                        variant="tonal"
                        density="compact"
                        class="mb-4"
                    >
                        {{ contractPreviewError }}
                    </v-alert>
                    <v-skeleton-loader
                        v-if="isLoadingContractPreview"
                        type="paragraph"
                    />
                    <div
                        v-else
                        class="contract-preview"
                        v-html="contractPreviewContent"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn
                        variant="text"
                        @click="contractPreviewDialog = false"
                    >
                        Fechar
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-dialog v-model="privacyDialog" max-width="640" scrollable>
            <v-card>
                <v-card-title class="d-flex align-center ga-2">
                    <v-icon icon="ti ti-shield-lock-outline" />
                    Aviso de privacidade
                </v-card-title>
                <v-card-text>
                    <div class="privacy-notice">
                        {{ privacyNotice }}
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-btn variant="text" @click="privacyDialog = false">
                        Fechar
                    </v-btn>
                    <v-spacer />
                    <v-btn color="primary" @click="acknowledgePrivacyNotice">
                        Li e concordo
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-main>
</template>

<style scoped>
/* CTA de pré-cadastro no padrão dos botões de destaque da landing page. */
.cta-finalize {
    background: #0057ff;
    color: #fff;
    font-family: 'Barlow', sans-serif;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 2.5px;
    text-transform: uppercase;
    height: 52px;
    transition:
        background 0.25s,
        transform 0.25s;
}
.cta-finalize:hover:not(.v-btn--disabled) {
    background: #1a6bff !important;
    transform: translateY(-2px);
}
/* Nunca vira link visitado/roxo: mantém sempre primary. */
.cta-finalize:visited,
.cta-finalize:active,
.cta-finalize:focus {
    color: #fff;
}
.v-theme--dark .cta-finalize.v-btn--disabled,
.v-theme--dark .cta-finalize.v-btn--disabled.v-btn--variant-flat {
    background: #0057ff !important;
    color: #fff !important;
    opacity: 0.6;
}
/* Cláusulas renderizadas via v-html a partir de resources/templates/contract.blade.php. */
.contract-preview {
    font-size: 0.9rem;
    line-height: 1.6;
}
.contract-preview :deep(h1) {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1rem;
}
.contract-preview :deep(h2) {
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin: 1.5rem 0 0.5rem;
}
.contract-preview :deep(p) {
    margin-bottom: 0.75rem;
    text-align: justify;
}
.contract-preview :deep(ul),
.contract-preview :deep(ol) {
    margin-bottom: 0.75rem;
    padding-left: 1.25rem;
}
/* Texto do aviso de privacidade, preservando as quebras de linha do cadastro. */
.privacy-notice {
    font-size: 0.875rem;
    line-height: 1.6;
    white-space: pre-line;
}
</style>
