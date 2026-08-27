<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { formatDateTime } from '@/plugins/formatters';

export type ReadOnlyField = {
    title: string;
    key: string;
    cols?: number;
    md?: number;
};

const props = withDefaults(
    defineProps<{
        title: string;
        item: Record<string, unknown>;
        fields: ReadOnlyField[];
        indexRoute: string;
        customSlots?: string[];
    }>(),
    {
        customSlots: () => [],
    },
);

function valueFor(key: string): string {
    const value = props.item[key];

    if (key === 'created_at' || key === 'updated_at') {
        return formatDateTime(value as string | number | Date | null | undefined);
    }

    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }

    return String(value);
}

function back(): void {
    router.visit(props.indexRoute);
}
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between ga-4 my-4">
            <div>
                <h1 class="text-h5 font-weight-medium">{{ title }}</h1>
            </div>
        </div>

        <v-card>
            <v-card-text>
                <v-row class="ma-0">
                    <v-col
                        v-for="field in fields"
                        :key="field.key"
                        :cols="field.cols ?? 12"
                        :md="field.md ?? 6"
                    >
                        <div class="text-caption text-medium-emphasis mb-1">
                            {{ field.title }}
                        </div>
                        <div class="text-body-1 text-pre-wrap">
                            <slot
                                v-if="customSlots.includes(field.key)"
                                :name="`field-${field.key}`"
                                :item="item"
                                :value="item[field.key]"
                            />
                            <template v-else>
                                {{ valueFor(field.key) }}
                            </template>
                        </div>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <div class="d-flex flex-row ga-2 pa-3 justify-start">
            <v-clipped-button
                color="secondary"
                prepend-icon="ti ti-arrow-left"
                @click="back"
            >
                Voltar
            </v-clipped-button>
            <div class="flex-grow-1"></div>
            <slot name="actions" :item="item" />
        </div>
    </div>
</template>
