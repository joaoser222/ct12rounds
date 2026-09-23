<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { masks } from '@/plugins/masks';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type Coupon = {
    id?: number;
    code?: string;
    percent?: number;
    discount_limit?: number;
    duration?: number;
    expiration_date?: string;
};

defineProps<{
    coupon?: Coupon | null;
    routes: DetailsRoutes;
}>();

const defaults = {
    code: '',
    percent: 0,
    discount_limit: 0,
    duration: 1,
    expiration_date: '',
};
</script>

<template>
    <DetailsPage
        title="Cupom"
        :item="coupon"
        :defaults="defaults"
        :routes="routes"
        module="coupons"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <MaskedTextField
                        v-model="form.code"
                        label="Código"
                        :mask="'Z*'"
                        :rules="[required]"
                        :error-messages="errors.code"
                        v-text-case="'upper'"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <CurrencyField
                        v-model="form.percent"
                        label="Desconto (%)"
                        :rules="[required]"
                        :error-messages="errors.percent"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <CurrencyField
                        v-model="form.discount_limit"
                        label="Limite de Desconto"
                        :error-messages="errors.discount_limit"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <v-text-field
                        v-model="form.duration"
                        label="Duração (meses)"
                        type="number"
                        :error-messages="errors.duration"
                    />
                </v-col>
                <v-col cols="12" md="4">
                    <DateField
                        v-model="form.expiration_date"
                        label="Data de Validade"
                        :error-messages="errors.expiration_date"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
