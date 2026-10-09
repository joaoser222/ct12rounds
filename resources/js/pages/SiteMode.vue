<script setup lang="ts">
import logo from '@/assets/logo.webp';

defineOptions({ layout: null });

defineProps<{
    title: string;
    message: string;
}>();
</script>

<template>
    <v-main
        theme="dark"
        class="d-flex align-center justify-center"
        style="min-height: 100vh"
    >
        <v-container
            fluid
            class="fill-height d-flex align-center justify-center pa-4"
        >
            <v-card width="420" color="secondary">
                <v-card-text class="pa-8 text-center">
                    <!-- Logo -->
                    <div class="d-flex justify-center mb-6">
                        <v-img :src="logo" height="48" />
                    </div>

                    <!-- Título -->
                    <h1 class="text-h5 font-weight-medium mb-1">{{ title }}</h1>
                    <p class="text-body-1 text-medium-emphasis">
                        {{ message }}
                    </p>
                </v-card-text>
            </v-card>
        </v-container>
    </v-main>
</template>
