<script setup lang="ts">
import { formatCurrency } from '@/plugins/formatters';

defineProps<{
    isCreating: boolean;
    planTitle?: string | null;
    planCategory?: string | null;
    modalityQuantity?: number | string | null;
    installments?: number | null;
    hasSelectedTier: boolean;
    grossValue?: number;
    discountValue?: number;
    totalValue?: number;
    couponCode?: string | null;
    discountedInstallmentsSummary?: string | null;
    couponPartialDurationMessage?: string | null;
}>();
</script>

<template>
    <v-card>
        <v-card-item>
            <v-card-title>Resumo</v-card-title>
            <v-card-subtitle>Confira os dados do contrato.</v-card-subtitle>
        </v-card-item>
        <v-card-text class="d-flex flex-column ga-4">
            <div>
                <div class="text-caption text-medium-emphasis">Plano</div>
                <div class="text-body-1 font-weight-medium">{{ planTitle || 'Selecione um plano' }}</div>
            </div>

            <div>
                <div class="text-caption text-medium-emphasis">Categoria</div>
                <div class="text-body-2 text-medium-emphasis">{{ planCategory || 'Sem categoria' }}</div>
            </div>

            <div>
                <div class="text-caption text-medium-emphasis">Qtd. modalidades</div>
                <div class="text-body-2 text-medium-emphasis">{{ modalityQuantity ?? '-' }}</div>
            </div>

            <div>
                <div class="text-caption text-medium-emphasis">Duração</div>
                <div class="text-body-1 font-weight-medium">{{ installments ? `${installments} meses` : 'Não selecionada' }}</div>
            </div>

            <div v-if="isCreating">
                <div class="text-caption text-medium-emphasis">Início do contrato</div>
                <div class="text-body-2 text-medium-emphasis">
                    As parcelas serão geradas a partir de hoje, quando o contrato for aplicado.
                </div>
            </div>

            <div>
                <div class="text-caption text-medium-emphasis">Valor bruto</div>
                <div class="text-body-1 font-weight-medium">{{ hasSelectedTier ? formatCurrency(grossValue ?? 0) : '-' }}</div>
            </div>

            <div>
                <div class="text-caption text-medium-emphasis">Desconto</div>
                <div class="text-body-1 font-weight-medium">{{ hasSelectedTier ? formatCurrency(discountValue ?? 0) : '-' }}</div>
                <div v-if="couponCode" class="text-body-2 text-medium-emphasis">
                    Cupom {{ couponCode }} aplicado.
                </div>
                <div v-if="discountedInstallmentsSummary" class="text-body-2 text-medium-emphasis">
                    {{ discountedInstallmentsSummary }}
                </div>
                <div v-if="couponPartialDurationMessage" class="text-body-2 text-info">
                    {{ couponPartialDurationMessage }}
                </div>
            </div>

            <div>
                <div class="text-caption text-medium-emphasis">Total final</div>
                <div class="text-h6 font-weight-bold">{{ hasSelectedTier ? formatCurrency(totalValue ?? 0) : '-' }}</div>
            </div>
        </v-card-text>
    </v-card>
</template>