<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { formatDate } from '@/plugins/formatters';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

type ReportFilter = {
    key: string;
    label: string;
    type: string;
    required: boolean;
    options: { value: number | string; label: string }[];
};

type ReportColumn = {
    key: string;
    label: string;
    type: string | null;
    sortable: boolean;
};

type Report = {
    id?: number;
    name?: string;
    label?: string;
    description?: string;
};

type ReportRow = Record<string, unknown>;

const props = defineProps<{
    report: Report;
    definition: {
        key: string;
        label: string;
        description: string;
        filters: ReportFilter[];
        columns: ReportColumn[];
    };
    columns: ReportColumn[];
    rows: ReportRow[];
    filters: Record<string, unknown>;
    routes: { index: string; show: string; run: string };
}>();

const monthFilter = computed(() =>
    props.definition.filters.find((filter) => filter.key === 'month'),
);

const month = ref<number>(
    Number(props.filters?.month ?? new Date().getMonth() + 1),
);

const hasRun = computed(() => Boolean(props.filters?.month));

const headers = computed(() =>
    props.columns.map((column) => ({
        title: column.label,
        key: column.key,
        align: (column.type === 'number' ? 'end' : 'start') as 'start' | 'end',
        sortable: column.sortable,
    })),
);

function applyFilter(): void {
    if (props.report.id === undefined) return;

    router.get(
        props.routes.run.replace(':id', String(props.report.id)),
        { month: month.value },
        { preserveState: true, preserveScroll: true },
    );
}

function back(): void {
    if (props.report.id === undefined) return;

    router.get(props.routes.show.replace(':id', String(props.report.id)));
}
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between ga-4 my-4">
            <div>
                <h1 class="text-h5 font-weight-medium">
                    {{ definition.label }}
                </h1>
                <p class="text-body-2 text-medium-emphasis mb-0">
                    {{ definition.description }}
                </p>
            </div>
        </div>

        <div class="d-flex align-center justify-end ga-3 mb-4">
            <v-clipped-button
                color="primary"
                prepend-icon="ti ti-report-analytics"
                @click="applyFilter"
            >
                Executar
            </v-clipped-button>
        </div>

        <v-card class="mb-4">
            <v-card-text>
                <div class="d-flex flex-wrap align-center ga-3">
                    <v-select
                        v-if="monthFilter"
                        v-model="month"
                        :items="monthFilter?.options ?? []"
                        item-title="label"
                        item-value="value"
                        :label="monthFilter?.label ?? 'Mês'"
                        variant="solo-filled"
                        hide-details
                        density="comfortable"
                        style="max-width: 220px"
                    />
                </div>
            </v-card-text>
        </v-card>

        <template v-if="hasRun">
            <v-data-table
                :headers="headers"
                :items="rows"
                item-value="id"
                :items-per-page="-1"
                hide-default-footer
                class="elevation-1"
            >
                <template #item.birth_date="{ item }">
                    {{ formatDate(item.birth_date as string) }}
                </template>

                <template #no-data>
                    <div class="text-center pa-4">
                        <v-icon
                            icon="ti ti-cake-off"
                            size="large"
                            color="grey-lighten-1"
                        />
                        <p class="text-body-1 mt-2">
                            Nenhum aniversariante encontrado
                        </p>
                    </div>
                </template>
            </v-data-table>

            <div class="d-flex flex-row ga-2 pa-3 justify-start">
                <v-clipped-button
                    color="secondary"
                    prepend-icon="ti ti-arrow-left"
                    @click="back"
                >
                    Voltar
                </v-clipped-button>
                <div class="flex-grow-1"></div>
                <span class="text-caption text-medium-emphasis align-self-center">
                    Total: {{ rows.length }} cliente(s)
                </span>
            </div>
        </template>

        <v-card v-else class="elevation-1">
            <v-card-text>
                <div class="text-center pa-4">
                    <v-icon
                        icon="ti ti-report-off"
                        size="large"
                        color="grey-lighten-1"
                    />
                    <p class="text-body-1 mt-2">
                        Clique em Executar para gerar os resultados.
                    </p>
                </div>
            </v-card-text>
        </v-card>
    </div>
</template>
