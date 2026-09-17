<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { ApexOptions } from 'apexcharts';
import { computed, onMounted, shallowRef, type Component } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    charts: {
        contractsByMonth: { labels: string[]; contracts: number[] };
        receivedByMonth: { labels: string[]; values: number[] };
        contractsByPlan: { labels: string[]; values: number[] };
        outstanding: { pending: number; overdue: number };
    };
}>();

const chartComponent = shallowRef<Component | null>(null);

onMounted(async () => {
    const module = await import('vue3-apexcharts');
    chartComponent.value = module.default as unknown as Component;
});

const currency = new Intl.NumberFormat('pt-BR', {
    style: 'currency',
    currency: 'BRL',
});

const contractsSeries = computed(() => [
    { name: 'Contratos', data: props.charts.contractsByMonth.contracts },
]);

const contractsOptions = computed<ApexOptions>(() => ({
    chart: {
        type: 'bar',
        height: 320,
        background: 'transparent',
        toolbar: { show: false },
    },
    theme: { mode: 'dark' },
    plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
    colors: ['#6366f1'],
    dataLabels: { enabled: false },
    grid: { borderColor: 'rgba(148, 163, 184, 0.2)' },
    xaxis: { categories: props.charts.contractsByMonth.labels },
    yaxis: {
        labels: { formatter: (value: number): string => value.toFixed(0) },
    },
    tooltip: {
        y: { formatter: (value: number): string => `${value} contrato(s)` },
    },
}));

const receivedSeries = computed(() => [
    { name: 'Recebimentos', data: props.charts.receivedByMonth.values },
]);

const receivedOptions = computed<ApexOptions>(() => ({
    chart: {
        type: 'area',
        height: 320,
        background: 'transparent',
        toolbar: { show: false },
    },
    theme: { mode: 'dark' },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: { opacityFrom: 0.4, opacityTo: 0.05 },
    },
    colors: ['#10b981'],
    dataLabels: { enabled: false },
    grid: { borderColor: 'rgba(148, 163, 184, 0.2)' },
    xaxis: { categories: props.charts.receivedByMonth.labels },
    yaxis: {
        labels: {
            formatter: (value: number): string => currency.format(value),
        },
    },
    tooltip: {
        y: { formatter: (value: number): string => currency.format(value) },
    },
}));

const planSeries = computed(() => props.charts.contractsByPlan.values);

const planOptions = computed<ApexOptions>(() => ({
    chart: { type: 'donut', height: 320, background: 'transparent' },
    theme: { mode: 'dark' },
    labels: props.charts.contractsByPlan.labels,
    legend: { position: 'bottom' },
    dataLabels: { enabled: true },
    tooltip: {
        y: { formatter: (value: number): string => `${value} contrato(s)` },
    },
}));

const outstandingSeries = computed(() => [
    props.charts.outstanding.pending,
    props.charts.outstanding.overdue,
]);

const outstandingOptions = computed<ApexOptions>(() => ({
    chart: { type: 'donut', height: 320, background: 'transparent' },
    theme: { mode: 'dark' },
    labels: ['Pendente', 'Vencido'],
    colors: ['#f59e0b', '#ef4444'],
    legend: { position: 'bottom' },
    dataLabels: { enabled: true },
    tooltip: {
        y: { formatter: (value: number): string => currency.format(value) },
    },
}));
</script>

<template>
    <div class="mx-auto" style="max-width: 1400px">
        <div class="mb-6">
            <h1 class="text-h4">Dashboard</h1>
            <p class="text-medium-emphasis mt-1">
                Visão geral de contratações e pagamentos.
            </p>
        </div>

        <v-row>
            <v-col cols="12" lg="6">
                <v-card color="surface" elevation="3" class="h-100">
                    <v-card-title class="text-subtitle-1 font-weight-medium">
                        Contratações por mês
                    </v-card-title>
                    <v-card-text>
                        <v-skeleton-loader
                            v-if="!chartComponent"
                            type="image"
                            height="320"
                        />
                        <component
                            :is="chartComponent"
                            v-else
                            type="bar"
                            height="320"
                            :options="contractsOptions"
                            :series="contractsSeries"
                        />
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card color="surface" elevation="3" class="h-100">
                    <v-card-title class="text-subtitle-1 font-weight-medium">
                        Recebimentos por mês
                    </v-card-title>
                    <v-card-text>
                        <v-skeleton-loader
                            v-if="!chartComponent"
                            type="image"
                            height="320"
                        />
                        <component
                            :is="chartComponent"
                            v-else
                            type="area"
                            height="320"
                            :options="receivedOptions"
                            :series="receivedSeries"
                        />
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card color="surface" elevation="3" class="h-100">
                    <v-card-title class="text-subtitle-1 font-weight-medium">
                        Contratações por plano
                    </v-card-title>
                    <v-card-text>
                        <v-skeleton-loader
                            v-if="!chartComponent"
                            type="image"
                            height="320"
                        />
                        <component
                            :is="chartComponent"
                            v-else
                            type="donut"
                            height="320"
                            :options="planOptions"
                            :series="planSeries"
                        />
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card color="surface" elevation="3" class="h-100">
                    <v-card-title class="text-subtitle-1 font-weight-medium">
                        A receber (pendente / vencido)
                    </v-card-title>
                    <v-card-text>
                        <v-skeleton-loader
                            v-if="!chartComponent"
                            type="image"
                            height="320"
                        />
                        <component
                            :is="chartComponent"
                            v-else
                            type="donut"
                            height="320"
                            :options="outstandingOptions"
                            :series="outstandingSeries"
                        />
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>
