<script setup lang="ts">
import CreditCardField from '@/components/CreditCardField.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
};

const props = defineProps<{
    plan?: PlanSummary | null;
    requiresLegalRepresentative?: boolean;
    coupon?: string | null;
    couponWarning?: string | null;
    contract?: ContractRegistration | null;
    terms?: string | null;
    success?: boolean;
    retryClientId?: number | null;
}>();

const showTerms = ref(false);
const currentStep = ref(1);

const isContractFlow = computed(() => !!props.contract);
const isRetry = computed(() => !!props.retryClientId);

const form = useForm({
    name: '',
    email: '',
    phone: '',
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
    legal_representative: false,
    legal_representative_name: '',
    legal_representative_document: '',
    legal_representative_birth_date: '',
    card_number: '',
    card_expiry_month: '',
    card_expiry_year: '',
    card_cvv: '',
    card_holder_name: '',
    is_contract_flow: isContractFlow.value,
    requires_legal_representative: props.requiresLegalRepresentative ?? false,
});

const isAddressRequired = computed(() => isContractFlow.value);

const step1Valid = computed(() => {
    if (!form.name || !form.email || !form.phone) return false;
    if (!form.document) return false;
    if (isContractFlow.value) {
        if (!form.gender || !form.birth_date) return false;
        if (!form.address_postal_code || !form.address || !form.address_number) return false;
        if (!form.address_district || !form.address_city || !form.address_state) return false;
    }
    if (props.requiresLegalRepresentative && form.legal_representative) {
        if (!form.legal_representative_name || !form.legal_representative_document || !form.legal_representative_birth_date) return false;
    }
    return true;
});

const step2Valid = computed(() => {
    if (!form.card_number || form.card_number.replace(/\D/g, '').length < 13) return false;
    if (!form.card_expiry_month || form.card_expiry_month.length < 2) return false;
    if (!form.card_expiry_year || form.card_expiry_year.length < 4) return false;
    if (!form.card_cvv || form.card_cvv.length < 3) return false;
    if (!form.card_holder_name) return false;
    return true;
});

const goToStep2 = () => {
    if (step1Valid.value) {
        currentStep.value = 2;
    }
};

const goToStep1 = () => {
    currentStep.value = 1;
};

const submitPreRegistration = () => {
    if (!step1Valid.value) return;
    form.post('/register');
};

const submitContractRegistration = () => {
    if (!step2Valid.value) return;
    form.post('/register');
};

const submitRetryPayment = () => {
    if (!step2Valid.value) return;
    form.post('/register/retry-payment');
};
</script>

<template>
    <v-main
        theme="dark"
        class="d-flex align-center justify-center"
        style="min-height: 100vh"
    >
        <v-container
            fluid
            class="fill-height d-flex align-center justify-center pa-4"
        >
            <v-card
                width="640"
                color="secondary"
            >
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
                                : 'Preencha seus dados básicos e garanta sua condição promocional.'
                        }}
                    </p>

                    <v-alert
                        v-if="success"
                        type="success"
                        variant="tonal"
                        class="mb-4"
                    >
                        Cadastro realizado com sucesso! Nossa equipe entrará em contato em breve.
                    </v-alert>

                    <v-alert
                        v-if="coupon && isContractFlow"
                        type="info"
                        variant="tonal"
                        class="mb-4"
                    >
                        Cupom promocional aplicado: <strong>{{ coupon }}</strong>
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
                            v-text-case="'capitalize'"
                            label="Nome completo"
                            :error-messages="form.errors.name"
                            class="mb-3"
                        />
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.email"
                                    label="E-mail"
                                    type="email"
                                    :error-messages="form.errors.email"
                                    class="mb-3"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.phone"
                                    label="Telefone"
                                    :error-messages="form.errors.phone"
                                    class="mb-3"
                                />
                            </v-col>
                        </v-row>
                        <v-text-field
                            v-model="form.document"
                            label="CPF"
                            :error-messages="form.errors.document"
                            class="mb-3"
                        />

                        <v-checkbox
                            v-model="form.accepted"
                            label="Li e aceito os termos do contrato."
                            :error-messages="form.errors.accepted"
                            color="primary"
                            class="mb-3"
                        />

                        <v-btn
                            type="submit"
                            color="primary"
                            size="large"
                            block
                            :loading="form.processing"
                            :disabled="form.processing || !step1Valid"
                        >
                            Finalizar pré-cadastro
                        </v-btn>
                    </v-form>

                    <!-- Cadastro com contrato: multi-step -->
                    <template v-else>
                        <v-stepper
                            v-if="!isRetry"
                            v-model="currentStep"
                            :items="['Dados Pessoais', 'Pagamento']"
                            flat
                            class="mb-4"
                        />

                        <v-form @submit.prevent="isRetry ? submitRetryPayment() : submitContractRegistration">
                            <template v-if="!isRetry && currentStep === 1">
                                <v-text-field
                                    v-model="form.name"
                                    v-text-case="'capitalize'"
                                    label="Nome completo"
                                    :error-messages="form.errors.name"
                                    class="mb-3"
                                />
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.email"
                                            label="E-mail"
                                            type="email"
                                            :error-messages="form.errors.email"
                                            class="mb-3"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.phone"
                                            label="Telefone"
                                            :error-messages="form.errors.phone"
                                            class="mb-3"
                                        />
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" md="6">
                                        <v-text-field
                                            v-model="form.document"
                                            label="CPF"
                                            :error-messages="form.errors.document"
                                            class="mb-3"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.gender"
                                            label="Gênero"
                                            :items="[
                                                { title: 'Masculino', value: 'M' },
                                                { title: 'Feminino', value: 'F' },
                                            ]"
                                            :error-messages="form.errors.gender"
                                            class="mb-3"
                                        />
                                    </v-col>
                                </v-row>

                                <v-text-field
                                    v-model="form.birth_date"
                                    label="Data de nascimento"
                                    type="date"
                                    :error-messages="form.errors.birth_date"
                                    class="mb-3"
                                />

                                <template v-if="isAddressRequired">
                                    <v-text-field
                                        v-model="form.address_postal_code"
                                        label="CEP"
                                        :error-messages="form.errors.address_postal_code"
                                        class="mb-3"
                                    />
                                    <v-text-field
                                        v-model="form.address"
                                        label="Endereço (rua)"
                                        :error-messages="form.errors.address"
                                        class="mb-3"
                                    />
                                    <v-row>
                                        <v-col cols="12" md="4">
                                            <v-text-field
                                                v-model="form.address_number"
                                                label="Número"
                                                :error-messages="form.errors.address_number"
                                                class="mb-3"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="8">
                                            <v-text-field
                                                v-model="form.address_complement"
                                                label="Complemento"
                                                :error-messages="form.errors.address_complement"
                                                class="mb-3"
                                            />
                                        </v-col>
                                    </v-row>
                                    <v-text-field
                                        v-model="form.address_district"
                                        label="Bairro"
                                        :error-messages="form.errors.address_district"
                                        class="mb-3"
                                    />
                                    <v-row>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                v-model="form.address_city"
                                                label="Cidade"
                                                :error-messages="form.errors.address_city"
                                                class="mb-3"
                                            />
                                        </v-col>
                                        <v-col cols="12" md="6">
                                            <v-text-field
                                                v-model="form.address_state"
                                                label="UF"
                                                maxlength="2"
                                                :error-messages="form.errors.address_state"
                                                class="mb-3"
                                            />
                                        </v-col>
                                    </v-row>
                                </template>

                                <template v-if="requiresLegalRepresentative">
                                    <v-divider class="my-4">
                                        <strong>Responsável Legal</strong>
                                    </v-divider>

                                    <v-checkbox
                                        v-model="form.legal_representative"
                                        label="Possui responsável legal"
                                        color="primary"
                                        class="mb-3"
                                    />

                                    <template v-if="form.legal_representative">
                                        <v-text-field
                                            v-model="form.legal_representative_name"
                                            v-text-case="'capitalize'"
                                            label="Nome do responsável"
                                            :error-messages="form.errors.legal_representative_name"
                                            class="mb-3"
                                        />
                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="form.legal_representative_document"
                                                    label="CPF do responsável"
                                                    :error-messages="form.errors.legal_representative_document"
                                                    class="mb-3"
                                                />
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <v-text-field
                                                    v-model="form.legal_representative_birth_date"
                                                    label="Nascimento do responsável"
                                                    type="date"
                                                    :error-messages="form.errors.legal_representative_birth_date"
                                                    class="mb-3"
                                                />
                                            </v-col>
                                        </v-row>
                                    </template>
                                </template>

                                <template v-if="terms">
                                    <v-card
                                        variant="tonal"
                                        class="mb-3"
                                    >
                                        <v-card-actions>
                                            <span class="text-body-2">Termos e condições</span>
                                            <v-spacer />
                                            <v-btn
                                                variant="text"
                                                size="small"
                                                :prepend-icon="showTerms ? 'ti ti-chevron-up' : 'ti ti-chevron-down'"
                                                @click="showTerms = !showTerms"
                                            >
                                                {{ showTerms ? 'Ocultar' : 'Ler termos' }}
                                            </v-btn>
                                        </v-card-actions>
                                        <v-expand-transition>
                                            <v-card-text
                                                v-show="showTerms"
                                                class="text-body-2 text-pre-wrap"
                                                style="max-height: 240px; overflow-y: auto"
                                            >
                                                {{ terms }}
                                            </v-card-text>
                                        </v-expand-transition>
                                    </v-card>
                                </template>

                                <v-checkbox
                                    v-model="form.accepted"
                                    label="Li e aceito os termos do contrato."
                                    :error-messages="form.errors.accepted"
                                    color="primary"
                                    class="mb-3"
                                />

                                <v-btn
                                    color="primary"
                                    size="large"
                                    block
                                    :disabled="!step1Valid"
                                    @click="goToStep2"
                                >
                                    Avançar
                                </v-btn>
                            </template>

                            <template v-if="isRetry || currentStep === 2">
                                <v-divider class="mb-4">
                                    <strong>Dados do Cartão de Crédito</strong>
                                </v-divider>

                                <CreditCardField
                                    v-model:card-number="form.card_number"
                                    v-model:card-expiry-month="form.card_expiry_month"
                                    v-model:card-expiry-year="form.card_expiry_year"
                                    v-model:card-cvv="form.card_cvv"
                                    v-model:card-holder-name="form.card_holder_name"
                                    :errors="{
                                        card_number: form.errors.card_number ? [form.errors.card_number] : [],
                                        card_expiry_month: form.errors.card_expiry_month ? [form.errors.card_expiry_month] : [],
                                        card_expiry_year: form.errors.card_expiry_year ? [form.errors.card_expiry_year] : [],
                                        card_cvv: form.errors.card_cvv ? [form.errors.card_cvv] : [],
                                        card_holder_name: form.errors.card_holder_name ? [form.errors.card_holder_name] : [],
                                    }"
                                />

                                <v-row class="mt-4">
                                    <v-col v-if="!isRetry" cols="6">
                                        <v-btn
                                            variant="outlined"
                                            size="large"
                                            block
                                            @click="goToStep1"
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
                                            :disabled="form.processing || !step2Valid"
                                        >
                                            {{ isRetry ? 'Tentar novamente' : 'Finalizar cadastro' }}
                                        </v-btn>
                                    </v-col>
                                </v-row>
                            </template>
                        </v-form>
                    </template>
                </v-card-text>
            </v-card>
        </v-container>
    </v-main>
</template>
