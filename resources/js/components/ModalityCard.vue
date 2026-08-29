<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        color?: string | null;
        dense?: boolean;
    }>(),
    {
        color: null,
        dense: false,
    },
);

const accent = computed<string>(() =>
    /^#[0-9A-Fa-f]{6}$/.test(props.color ?? '') ? (props.color as string) : '#6750A4',
);

const cardStyle = computed<Record<string, string>>(() => ({
    '--modality-accent': accent.value,
}));
</script>

<template>
    <div
        class="modality-card rounded-lg"
        :class="{ 'pa-2': dense, 'pa-4': !dense }"
        :style="cardStyle"
    >
        <span class="modality-dot" aria-hidden="true" />
        <span class="modality-name" :class="dense ? 'text-body-1' : 'text-subtitle-1'">
            {{ name || 'Nome da modalidade' }}
        </span>
    </div>
</template>

<style scoped>
.modality-card {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    max-width: 100%;
    background: color-mix(in srgb, var(--modality-accent) 14%, transparent);
    border: 1px solid color-mix(in srgb, var(--modality-accent) 35%, transparent);
}

.modality-dot {
    flex: 0 0 auto;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: var(--modality-accent);
}

.modality-name {
    font-weight: 600;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: var(--modality-accent);
}
</style>