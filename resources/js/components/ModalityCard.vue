<script setup lang="ts">
import { computed } from 'vue';
import logo from '@/assets/logo.webp';

const props = withDefaults(
    defineProps<{
        name: string;
        color?: string | null;
        scale?: number;
    }>(),
    {
        color: null,
        scale: 1,
    },
);

const accent = computed<string>(() =>
    /^#[0-9A-Fa-f]{6}$/.test(props.color ?? '') ? (props.color as string) : '#6750A4',
);

const cardStyle = computed<Record<string, string>>(() => ({
    '--modality-accent': accent.value,
    '--modality-scale': String(props.scale),
}));
</script>

<template>
    <div class="modality-card rounded-lg w-100" :style="cardStyle">
        <div class="modality-card__inner">
            <img :src="logo" alt="" class="modality-logo" />
            <span class="modality-name">{{ name || 'Nome da modalidade' }}</span>
        </div>
    </div>
</template>

<style scoped>
/* Scale via transform (center origin): shrinks the entire card — text, logo,
 * padding and border — for compact use in documents. The height follows the scale. */
.modality-card {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    max-height: calc(300px * var(--modality-scale));
    padding: calc(24px * var(--modality-scale));
    background: #000000;
    border: calc(2px * var(--modality-scale)) solid rgb(var(--v-theme-primary));
    transform: scale(var(--modality-scale));
    transform-origin: center;
    overflow: hidden;
}

.modality-card__inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.modality-logo {
    height: 2vmax;
    width: auto;
    max-width: 100%;
    object-fit: contain;
}

.modality-name {
    font-family: 'Bebas Neue', 'Barlow Condensed', sans-serif;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.015em;
    font-size: clamp(2.5rem, 10vw, 6rem);
    line-height: 1;
    text-align: center;
    color: var(--modality-accent);
    overflow: hidden;
}
</style>