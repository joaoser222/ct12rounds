<script setup lang="ts">
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
    coupon?: string | null;
    contract?: ContractRegistration | null;
    terms?: string | null;
    success?: boolean;
}>();

const showTerms = ref(false);

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
});

const contractFlow = computed(() => !!props.plan || !!props.contract);

const isContractRegistration = computed(() => !!props.contract);

const isAddressRequired = computed(() => contractFlow.value);

const submit = () => {
    form.post('/cadastro');
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
                            isContractRegistration
                                ? `Cadastro do contrato #${contract?.id}`
                                : (contractFlow ? `Cadastro para o plano ${plan?.name}` : 'Pré-cadastro')
                        }}
                    </h1>
                    <p
                        class="text-body-2 text-medium-emphasis text-center mb-6"
                    >
                        {{
                            isContractRegistration
                                ? `Preencha seus dados para concretizar o contrato ${contract?.plan ? `do plano ${contract.plan}` : ''}.`
                                : (contractFlow
                                    ? `Preencha seus dados para garantir sua vaga no plano ${plan?.name}.`
                                    : 'Preencha seus dados básicos e garanta sua condição promocional.')
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
                        v-if="coupon"
                        type="info"
                        variant="tonal"
                        class="mb-4"
                    >
                        Cupom promocional aplicado: <strong>{{ coupon }}</strong>
                    </v-alert>

                    <v-form @submit.prevent="submit">
                        <v-text-field
                            v-model="form.name"
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

                        <v-row v-if="contractFlow">
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
                            v-if="contractFlow"
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

                        <v-text-field
                            v-if="!isContractRegistration"
                            v-model="form.coupon"
                            label="Cupom promocional (opcional)"
                            :error-messages="form.errors.coupon"
                            class="mb-3"
                        />

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
                            type="submit"
                            color="primary"
                            size="large"
                            block
                            :loading="form.processing"
                            :disabled="form.processing"
                        >
                            Finalizar cadastro
                        </v-btn>
                    </v-form>
                </v-card-text>
            </v-card>
        </v-container>
    </v-main>
</template>