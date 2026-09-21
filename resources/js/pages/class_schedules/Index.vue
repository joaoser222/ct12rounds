<!-- resources/js/pages/class_schedules/Index.vue -->
<script setup lang="ts">
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import type { TableHeader, TableRoutes } from '@/components/TablePage.vue';
import type { PaginatedResponse, IndexRoutes } from '@/shared/page';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps<{
    classSchedules: PaginatedResponse<any>;
    routes: IndexRoutes;
}>();

const weekDays: Record<number, string> = {
    1: 'Segunda',
    2: 'Terça',
    3: 'Quarta',
    4: 'Quinta',
    5: 'Sexta',
    6: 'Sábado',
};

const headers: TableHeader[] = [
    { title: 'ID', key: 'id', sortable: true, width: '80px' },
    { title: 'Modalidade', key: 'modality_name', sortable: true, searchable: true },
    { title: 'Dia', key: 'week_day', sortable: true },
    { title: 'Início', key: 'start_time', sortable: true },
    { title: 'Fim', key: 'end_time', sortable: true },
    { title: 'Criado em', key: 'created_at', sortable: true },
];

const routes: TableRoutes = {
    index: props.routes.index,
    create: props.routes.create,
    show: props.routes.show,
    changeVisibility: props.routes.changeVisibility,
    destroy: props.routes.destroy,
};
</script>

<template>
    <TablePage
        :items="classSchedules.data"
        :total="classSchedules.total"
        :current-page="classSchedules.current_page"
        :last-page="classSchedules.last_page"
        :per-page="classSchedules.per_page"
        :headers="headers"
        :routes="routes"
        module="class_schedules"
        title="Grade de Horários"
        :custom-slots="['week_day', 'created_at']"
    >
        <template #column-week_day="{ item }">
            {{ weekDays[item.week_day] ?? item.week_day }}
        </template>
        <template #column-created_at="{ item }">
            {{ new Date(item.created_at).toLocaleDateString('pt-BR') }}
        </template>
    </TablePage>
</template>
