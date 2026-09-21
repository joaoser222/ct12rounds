<script setup lang="ts">
import type { DetailsRoutes } from '@/shared/page';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import { required } from '@/plugins/validators';

defineOptions({ layout: AuthenticatedLayout });

type ClassSchedule = {
    id?: number;
    modality_id?: number;
    week_day?: number;
    start_time?: string;
    end_time?: string;
};

defineProps<{
    classSchedule?: ClassSchedule | null;
    routes: DetailsRoutes;
    options?: {
        modalities?: Array<{ value: string; label: string }>;
    };
}>();

const defaults = {
    modality_id: null,
    week_day: null,
    start_time: '',
    end_time: '',
};

const weekDays = [
    { value: 1, label: 'Segunda' },
    { value: 2, label: 'Terça' },
    { value: 3, label: 'Quarta' },
    { value: 4, label: 'Quinta' },
    { value: 5, label: 'Sexta' },
    { value: 6, label: 'Sábado' },
];
</script>

<template>
    <DetailsPage
        title="Grade de Horários"
        :item="classSchedule"
        :defaults="defaults"
        :routes="routes"
        module="class_schedules"
    >
        <template #default="{ form, errors }">
            <v-row class="ma-0">
                <v-col cols="12" md="6">
                    <v-select
                        v-model="form.modality_id"
                        :items="options?.modalities ?? []"
                        item-title="label"
                        item-value="value"
                        label="Modalidade"
                        :rules="[required]"
                        :error-messages="errors.modality_id"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-select
                        v-model.number="form.week_day"
                        :items="weekDays"
                        item-title="label"
                        item-value="value"
                        label="Dia da Semana"
                        :rules="[required]"
                        :error-messages="errors.week_day"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.start_time"
                        label="Horário Início"
                        type="time"
                        :rules="[required]"
                        :error-messages="errors.start_time"
                    />
                </v-col>
                <v-col cols="12" md="6">
                    <v-text-field
                        v-model="form.end_time"
                        label="Horário Fim"
                        type="time"
                        :rules="[required]"
                        :error-messages="errors.end_time"
                    />
                </v-col>
            </v-row>
        </template>
    </DetailsPage>
</template>
