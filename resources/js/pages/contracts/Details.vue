<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ContractActions from '@/pages/contracts/ContractActions.vue';
import ContractSummary from '@/pages/contracts/ContractSummary.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { formatCurrency, formatDateTime } from '@/plugins/formatters';
import { required } from '@/plugins/validators';
import { findLabel, findOption, useSharedOptions, type LabeledOption, type Option } from '@/shared/options';
import { useModulePermissions } from '@/composables/useModulePermissions';

defineOptions({ layout: AuthenticatedLayout });

type PlanTier = {
    quantity: number;
    price: number;
};

type PlanOption = {
    value: number;
    title: string;
    category?: string | null;
    modality_quantity: number;
    tiers: PlanTier[];
};

type CouponOption = {
    value: number;
    title: string;
    code: string;
    percent?: number | string | null;
    discount_limit?: number | string | null;
    duration?: number | string | null;
    expiration_date?: string | null;
};

type Registration = {
    url: string;
    qr: string;
};

type LinkedLead = {
    id: number;
    name: string;
    email: string;
    phone: string;
    document: string;
    gender?: string | null;
    birth_date?: string | null;
    address?: string | null;
    address_number?: string | null;
    address_complement?: string | null;
    address_district?: string | null;
    address_state?: string | null;
    address_city?: string | null;
    address_postal_code?: string | null;
    status: string;
};

type Contract = {
    id?: number;
    plan_name?: string;
    modality_quantity?: number | string;
    gross_value?: number;
    discount_value?: number;
    total?: number;
    first_due_date?: string | null;
    installments?: number;
    accepted_terms?: string | null;
    annotations?: string | null;
    status?: string | null;
    payment_method?: string | null;
    coupon_id?: number | null;
    plan_id?: number;
    client_id?: number | null;
    registration_token?: string | null;
    created_at?: string | null;
    updated_at?: string | null;
};

type VForm = {
    validate: () => Promise<{ valid: boolean }>;
};

const props = defineProps<{
    contract?: Contract | null;
    cancelRoute?: string | null;
    applicationRoute?: string | null;
    registration?: Registration | null;
    linkedLead?: LinkedLead | null;
    clientInfo?: string | null;
    couponInfo?: string | null;
    routes: {
        index: string;
        store: string;
        update: string;
        apply: string;
    };
    options: {
        plans: PlanOption[];
        coupons: CouponOption[];
        genderTypes?: LabeledOption<string>[];
        ufs?: LabeledOption<string>[];
        billableStatus?: Option[];
        paymentMethods?: Option[];
    };
}>();

const isCreating = !props.contract?.id;

const isPending = computed(() => {
    return !isCreating && props.contract?.client_id == null && !!props.registration;
});

const { billableStatus, paymentMethods } = useSharedOptions({
    billableStatus: props.options.billableStatus,
    paymentMethods: props.options.paymentMethods,
});

const { hasPermission: canApply, ensurePermissionsLoaded: ensureApplyLoaded } = useModulePermissions<'apply'>({
    module: () => 'contracts',
    permissions: () => undefined,
    permissionMap: () => ({ apply: 'contracts.update' }),
});

const formRef = ref<VForm | null>(null);
const selectedCoupon = ref<CouponOption | null>(null);
const copied = ref(false);

const form = useForm(
    isCreating
        ? {
            plan_id: null as number | null,
            installments: null as number | null,
            coupon_id: null as number | null,
            annotations: '',
        }
        : {
            annotations: props.contract?.annotations ?? '',
        },
);

const selectedPlan = computed<PlanOption | null>(() => {
    return props.options.plans.find((plan) => plan.value === form.plan_id) ?? null;
});

const durationOptions = computed(() => {
    return (
        selectedPlan.value?.tiers.map((tier) => ({
            title: `${tier.quantity} ${tier.quantity === 1 ? 'mes' : 'meses'} - ${formatCurrency(tier.price)}`,
            value: tier.quantity,
        })) ?? []
    );
});

const selectedTier = computed<PlanTier | null>(() => {
    return (
        selectedPlan.value?.tiers.find(
            (tier) => tier.quantity === Number(form.installments),
        ) ?? null
    );
});

const grossValuePreview = computed(() => {
    if (selectedTier.value === null || form.installments === null) {
        return 0;
    }

    return selectedTier.value.price * Number(form.installments);
});

const grossInstallmentValues = computed(() => {
    const installments = Number(form.installments ?? 0);

    if (!selectedTier.value || installments < 1) {
        return [];
    }

    return splitAmount(grossValuePreview.value, installments);
});

const discountValuePreview = computed(() => {
    if (selectedCoupon.value === null || grossInstallmentValues.value.length === 0) {
        return 0;
    }

    const percent = Number(selectedCoupon.value.percent ?? 0);
    const discountLimit = Number(selectedCoupon.value.discount_limit ?? 0);
    const couponDuration = Number(selectedCoupon.value.duration ?? grossInstallmentValues.value.length);
    const eligibleInstallments = Math.min(
        grossInstallmentValues.value.length,
        Number.isFinite(couponDuration) && couponDuration > 0
            ? couponDuration
            : grossInstallmentValues.value.length,
    );

    const rawDiscounts = grossInstallmentValues.value
        .slice(0, eligibleInstallments)
        .map((value) => value * (percent / 100));

    if (Number.isFinite(discountLimit) && discountLimit > 0) {
        return Math.min(rawDiscounts.reduce((sum, value) => sum + value, 0), discountLimit);
    }

    return rawDiscounts.reduce((sum, value) => sum + value, 0);
});

const totalValuePreview = computed(() => {
    return Math.max(0, grossValuePreview.value - discountValuePreview.value);
});

const couponPartialDurationMessage = computed(() => {
    if (selectedCoupon.value === null || form.installments === null) {
        return null;
    }

    const couponDuration = Number(selectedCoupon.value.duration ?? 0);

    if (!Number.isFinite(couponDuration) || couponDuration <= 0) {
        return null;
    }

    if (Number(form.installments) <= couponDuration) {
        return null;
    }

    return `O cupom ${selectedCoupon.value.code} será aplicado nas primeiras ${selectedCoupon.value.duration} parcelas.`;
});

const discountedInstallmentsSummary = computed(() => {
    if (selectedCoupon.value === null || form.installments === null) {
        return null;
    }

    const couponDuration = Number(selectedCoupon.value.duration ?? 0);

    if (!Number.isFinite(couponDuration) || couponDuration <= 0) {
        return `${form.installments} de ${form.installments} parcelas com desconto.`;
    }

    const discountedInstallments = Math.min(Number(form.installments), couponDuration);

    return `${discountedInstallments} de ${form.installments} parcelas com desconto.`;
});

watchSelectedCoupon();

function watchSelectedCoupon(): void {
    selectedCoupon.value = props.options.coupons.find((coupon) => coupon.value === form.coupon_id) ?? null;
}

function onPlanChange(): void {
    if (selectedPlan.value === null) {
        form.installments = null;

        return;
    }

    if (!selectedPlan.value.tiers.some((tier) => tier.quantity === Number(form.installments))) {
        form.installments = null;
    }
}

function onCouponChange(value: number | null): void {
    selectedCoupon.value = props.options.coupons.find((coupon) => coupon.value === value) ?? null;
}

async function submit(): Promise<void> {
    if (isCreating) {
        const result = await formRef.value?.validate();

        if (!result?.valid) {
            return;
        }

        form.post(props.routes.store, {
            preserveScroll: true,
            onFinish: () => form.transform((data) => data),
        });

        return;
    }

    form.put(props.routes.update.replace(':id', String(props.contract!.id)), {
        preserveScroll: true,
        onFinish: () => form.transform((data) => data),
    });
}

function applyContract(): void {
    if (!props.applicationRoute) {
        return;
    }

    if (!confirm('Aplicar o contrato? Será criado o cliente a partir do cadastro, aceitos os termos e geradas as faturas.')) {
        return;
    }

    router.patch(props.applicationRoute, {}, {
        preserveScroll: true,
    });
}

function cancelContract(route: string): void {
    if (!confirm('Tem certeza que deseja cancelar este contrato?')) {
        return;
    }

    router.patch(route, {}, {
        preserveScroll: true,
    });
}

function copyRegistrationLink(): void {
    if (!props.registration) {
        return;
    }

    navigator.clipboard?.writeText(props.registration.url).then(() => {
        copied.value = true;

        setTimeout(() => (copied.value = false), 2000);
    }).catch(() => {});
}

function splitAmount(amount: number, installments: number): number[] {
    const scale = 10000;
    const total = Math.round(amount * scale);
    const baseInstallmentValue = Math.floor(total / installments);
    const remainder = total % installments;

    return Array.from({ length: installments }, (_, index) => {
        return (baseInstallmentValue + (index < remainder ? 1 : 0)) / scale;
    });
}

onMounted(() => {
    void ensureApplyLoaded();
});
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between ga-4 my-4 flex-wrap">
            <div>
                <h1 class="text-h5 font-weight-medium">
                    {{ isCreating ? 'Novo contrato' : `Contrato #${contract?.id}` }}
                </h1>
            </div>
        </div>

        <v-row>
            <v-col cols="12" lg="8">
                <v-card>
                    <v-card-text
                        v-if="!isCreating"
                        class="pb-0"
                    >
                        <v-row class="ma-0">
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">ID do contrato</v-label>
                                <div class="text-body-1 mb-3">{{ contract?.id ?? '-' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Status</v-label>
                                <div class="mb-3">
                                    <v-chip
                                        v-if="contract?.accepted_terms === 'pending' && contract?.client_id == null"
                                        color="warning"
                                    >
                                        Pendente
                                    </v-chip>
                                    <v-chip
                                        v-else
                                        :color="findOption(billableStatus, contract?.status)?.color ?? 'secondary'"
                                    >
                                        {{ findLabel(billableStatus, contract?.status) ?? contract?.status ?? '-' }}
                                    </v-chip>
                                </div>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-label class="text-caption text-medium-emphasis">Cliente</v-label>
                                <div class="text-body-1 mb-3">{{ clientInfo ?? 'Aguardando cadastro via QR Code' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Plano</v-label>
                                <div class="text-body-1 mb-3">{{ contract?.plan_name ?? '-' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Forma de pagamento</v-label>
                                <div class="text-body-1 mb-3">{{ findLabel(paymentMethods, contract?.payment_method) ?? contract?.payment_method ?? '-' }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Criado em</v-label>
                                <div class="text-body-1 mb-3">{{ formatDateTime(contract?.created_at) }}</div>
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-label class="text-caption text-medium-emphasis">Atualizado em</v-label>
                                <div class="text-body-1 mb-3">{{ formatDateTime(contract?.updated_at) }}</div>
                            </v-col>
                        </v-row>
                        <v-divider class="my-4" />
                    </v-card-text>

                    <v-card-text>
                        <template v-if="isCreating">
                            <v-form ref="formRef">
                                <v-row class="ma-0 mt-4">
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.plan_id"
                                            label="Plano"
                                            :items="props.options.plans"
                                            item-title="title"
                                            item-value="value"
                                            :rules="[required]"
                                            :error-messages="form.errors.plan_id"
                                            @update:model-value="onPlanChange"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.installments"
                                            label="Duração"
                                            :items="durationOptions"
                                            item-title="title"
                                            item-value="value"
                                            :rules="[required]"
                                            :disabled="selectedPlan === null"
                                            :error-messages="form.errors.installments"
                                        />
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <v-select
                                            v-model="form.coupon_id"
                                            label="Cupom (opcional)"
                                            :items="props.options.coupons"
                                            item-title="title"
                                            item-value="value"
                                            clearable
                                            :error-messages="form.errors.coupon_id"
                                            @update:model-value="onCouponChange"
                                        />
                                    </v-col>
                                    <v-col v-if="selectedCoupon" cols="12">
                                        <v-alert color="info" variant="tonal" border="start">
                                            <div class="d-flex flex-column ga-1">
                                                <div>
                                                    Desconto: {{ formatCurrency(discountValuePreview) }}
                                                </div>
                                                <div>
                                                    Valor final: {{ formatCurrency(totalValuePreview) }}
                                                </div>
                                                <div>
                                                    {{ discountedInstallmentsSummary }}
                                                </div>
                                                <div v-if="couponPartialDurationMessage">
                                                    {{ couponPartialDurationMessage }}
                                                </div>
                                            </div>
                                        </v-alert>
                                    </v-col>
                                    <v-col cols="12">
                                        <v-textarea
                                            v-model="form.annotations"
                                            label="Anotações"
                                            rows="3"
                                            :error-messages="form.errors.annotations"
                                        />
                                    </v-col>
                                    <v-col cols="12">
                                        <v-alert color="primary" variant="tonal" border="start">
                                            Após salvar, um QR Code será gerado para o cliente preencher o cadastro e o contrato ficará pendente até a aplicação.
                                        </v-alert>
                                    </v-col>
                                </v-row>
                            </v-form>
                        </template>

                        <template v-else-if="isPending">
                            <v-row class="ma-0">
                                <v-col cols="12" md="6">
                                    <v-alert color="warning" variant="tonal" border="start">
                                        Autorize o contrato exibindo o QR Code abaixo para o cliente preencher o cadastro.
                                    </v-alert>

                                    <div class="d-flex flex-column align-center my-4">
                                        <v-img
                                            :src="registration?.qr"
                                            width="220"
                                            alt="QR Code de cadastro"
                                            class="border rounded"
                                        />
                                        <div class="text-body-2 text-medium-emphasis text-center my-3">
                                            Escaneie ou compartilhe o link de cadastro com o cliente.
                                        </div>
                                        <v-clipped-button
                                            color="primary"
                                            :prepend-icon="copied ? 'ti ti-check' : 'ti ti-link'"
                                            @click="copyRegistrationLink"
                                        >
                                            {{ copied ? 'Link copiado!' : 'Copiar link de cadastro' }}
                                        </v-clipped-button>
                                    </div>
                                </v-col>

                                <v-col cols="12" md="6">
                                    <v-card variant="tonal">
                                        <v-card-item>
                                            <v-card-title class="text-subtitle-1">
                                                Cadastro do cliente
                                            </v-card-title>
                                        </v-card-item>
                                        <v-card-text>
                                            <template v-if="linkedLead">
                                                <div class="text-body-2 text-medium-emphasis">Nome</div>
                                                <div class="text-body-1 mb-2">{{ linkedLead.name }}</div>
                                                <div class="text-body-2 text-medium-emphasis">CPF</div>
                                                <div class="text-body-1 mb-2">{{ linkedLead.document }}</div>
                                                <div class="text-body-2 text-medium-emphasis">E-mail</div>
                                                <div class="text-body-1 mb-2">{{ linkedLead.email }}</div>
                                                <div class="text-body-2 text-medium-emphasis">Telefone</div>
                                                <div class="text-body-1 mb-2">{{ linkedLead.phone }}</div>

                                                <v-divider class="my-3" />

                                                <div class="text-body-2 text-medium-emphasis">Endereço</div>
                                                <div class="text-body-1 mb-2">
                                                    {{ linkedLead.address ?? 'Não informado' }},
                                                    {{ linkedLead.address_number ?? '-' }}
                                                    <span v-if="linkedLead.address_complement">
                                                        - {{ linkedLead.address_complement }}
                                                    </span>
                                                </div>
                                                <div class="text-body-1 mb-2">
                                                    {{ linkedLead.address_district ?? '' }}
                                                    {{ linkedLead.address_city ?? '' }}
                                                    {{ linkedLead.address_state ?? '' }}
                                                    {{ linkedLead.address_postal_code ?? '' }}
                                                </div>

                                                <v-alert color="success" variant="tonal" border="start" class="mt-4">
                                                    Cliente já preencheu o cadastro. Aplique o contrato para criar o cliente e gerar as faturas.
                                                </v-alert>
                                            </template>

                                            <template v-else>
                                                <v-alert color="info" variant="tonal" border="start">
                                                    Nenhum cadastro recebido até o momento. O contrato será aplicado quando o cliente preencher o formulário.
                                                </v-alert>
                                            </template>
                                        </v-card-text>
                                    </v-card>
                                </v-col>
                            </v-row>

                            <v-row class="ma-0">
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.annotations"
                                        label="Anotações"
                                        rows="3"
                                        :error-messages="form.errors.annotations"
                                    />
                                </v-col>
                            </v-row>
                        </template>

                        <template v-else>
                            <v-row class="ma-0">
                                <v-col cols="12">
                                    <v-textarea
                                        v-model="form.annotations"
                                        label="Anotações"
                                        rows="3"
                                        :error-messages="form.errors.annotations"
                                    />
                                </v-col>
                            </v-row>
                        </template>
                    </v-card-text>

                    <ContractActions
                        :processing="form.processing"
                        :show-save="isCreating || !isPending"
                        :show-apply="!isCreating && isPending && Boolean(linkedLead) && canApply('apply')"
                        :show-cancel="!isCreating && Boolean(cancelRoute && contract?.status !== 'canceled' && contract?.client_id != null)"
                        @back="router.get(props.routes.index)"
                        @save="submit"
                        @apply="applyContract"
                        @cancel="cancelContract(cancelRoute!)"
                    />
                </v-card>
            </v-col>

            <v-col cols="12" lg="4">
                <ContractSummary
                    :is-creating="isCreating"
                    :plan-title="isCreating ? selectedPlan?.title : contract?.plan_name"
                    :plan-category="selectedPlan?.category"
                    :modality-quantity="isCreating ? selectedPlan?.modality_quantity : contract?.modality_quantity"
                    :installments="isCreating ? form.installments : contract?.installments"
                    :has-selected-tier="isCreating ? selectedTier !== null : true"
                    :gross-value="isCreating ? grossValuePreview : contract?.gross_value"
                    :discount-value="isCreating ? discountValuePreview : contract?.discount_value"
                    :total-value="isCreating ? totalValuePreview : contract?.total"
                    :coupon-code="isCreating ? (selectedCoupon?.code ?? null) : (couponInfo ?? null)"
                    :discounted-installments-summary="isCreating ? discountedInstallmentsSummary : null"
                    :coupon-partial-duration-message="isCreating ? couponPartialDurationMessage : null"
                />
            </v-col>
        </v-row>
    </div>
</template>