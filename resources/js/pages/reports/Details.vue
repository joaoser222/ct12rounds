<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import ReadOnlyDetailsPage, {
    type ReadOnlyField,
} from '@/components/ReadOnlyDetailsPage.vue';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

type Report = {
    id?: number;
    name?: string;
    label?: string;
    description?: string;
};

const props = defineProps<{
    report: Report;
    routes: { index: string; show: string; run: string };
}>();

const fields: ReadOnlyField[] = [
    { title: 'ID', key: 'id' },
    { title: 'Nome', key: 'name' },
    { title: 'Rótulo', key: 'label' },
    { title: 'Descrição', key: 'description', cols: 12 },
];

function run(): void {
    if (!props.routes.run || props.report.id === undefined) {
        return;
    }

    router.get(props.routes.run.replace(':id', String(props.report.id)));
}
</script>

<template>
    <ReadOnlyDetailsPage
        title="Relatório"
        :item="report"
        :fields="fields"
        :index-route="routes.index"
    >
        <template #actions>
            <v-clipped-button
                color="primary"
                prepend-icon="ti ti-report-analytics"
                @click="run"
            >
                Executar
            </v-clipped-button>
        </template>
    </ReadOnlyDetailsPage>
</template>
