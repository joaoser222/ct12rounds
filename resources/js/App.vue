<template>
  <v-app class="app-transparent-shell">
    <slot />
    <GlobalDialog />
    <ConfirmDialog />
    <GlobalToast />
  </v-app>
</template>

<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import GlobalDialog from '@/components/GlobalDialog.vue';
import GlobalToast from '@/components/GlobalToast.vue';
</script>
