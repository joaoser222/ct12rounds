<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    cardNumber?: string;
    cardExpiryMonth?: string;
    cardExpiryYear?: string;
    cardCvv?: string;
    cardHolderName?: string;
    errors?: Record<string, string[]>;
}>();

const emit = defineEmits<{
    'update:cardNumber': [value: string];
    'update:cardExpiryMonth': [value: string];
    'update:cardExpiryYear': [value: string];
    'update:cardCvv': [value: string];
    'update:cardHolderName': [value: string];
}>();

const cardNumber = computed({
    get: () => props.cardNumber ?? '',
    set: (val: string) => emit('update:cardNumber', val.replace(/\D/g, '').slice(0, 19)),
});

const cardExpiryMonth = computed({
    get: () => props.cardExpiryMonth ?? '',
    set: (val: string) => {
        const digits = val.replace(/\D/g, '').slice(0, 2);
        emit('update:cardExpiryMonth', digits);
    },
});

const cardExpiryYear = computed({
    get: () => props.cardExpiryYear ?? '',
    set: (val: string) => {
        const digits = val.replace(/\D/g, '').slice(0, 4);
        emit('update:cardExpiryYear', digits);
    },
});

const cardCvv = computed({
    get: () => props.cardCvv ?? '',
    set: (val: string) => emit('update:cardCvv', val.replace(/\D/g, '').slice(0, 4)),
});

const cardHolderName = computed({
    get: () => props.cardHolderName ?? '',
    set: (val: string) => emit('update:cardHolderName', val),
});

const cardNumberFormatted = computed(() => {
    const v = cardNumber.value.replace(/\s/g, '');
    return v.replace(/(.{4})/g, '$1 ').trim();
});
</script>

<template>
    <div>
        <v-text-field
            v-model="cardHolderName"
            label="Nome no Cartão"
            v-text-case="'upper'"
            :error-messages="errors?.card_holder_name"
            class="mb-3"
        />

        <v-text-field
            v-model="cardNumber"
            label="Número do Cartão"
            :model-value="cardNumberFormatted"
            @update:model-value="cardNumber = $event"
            inputmode="numeric"
            maxlength="19"
            placeholder="0000 0000 0000 0000"
            :error-messages="errors?.card_number"
            class="mb-3"
        />

        <v-row>
            <v-col cols="6">
                <v-text-field
                    v-model="cardExpiryMonth"
                    label="Mês"
                    inputmode="numeric"
                    maxlength="2"
                    placeholder="MM"
                    :error-messages="errors?.card_expiry_month"
                />
            </v-col>
            <v-col cols="6">
                <v-text-field
                    v-model="cardExpiryYear"
                    label="Ano"
                    inputmode="numeric"
                    maxlength="4"
                    placeholder="AAAA"
                    :error-messages="errors?.card_expiry_year"
                />
            </v-col>
        </v-row>

        <v-text-field
            v-model="cardCvv"
            label="CVV"
            type="password"
            inputmode="numeric"
            maxlength="4"
            placeholder="***"
            :error-messages="errors?.card_cvv"
            class="mb-3"
        />
    </div>
</template>
