<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useModulePermissions } from '@/composables/useModulePermissions';
import { useToast } from '@/composables/useToast';

type GatewaySyncScope = 'payments' | 'transfers' | 'customers' | 'postbacks';

const props = defineProps<{
    scope: GatewaySyncScope;
}>();

const { show: showToast } = useToast();
const syncing = ref(false);
const canSync = ref(false);

const { hasPermission, ensurePermissionsLoaded } = useModulePermissions<'sync'>(
    {
        module: () => undefined,
        permissions: () => undefined,
        permissionMap: () => ({ sync: 'gateway_accounts.update' }),
    },
);

const label = computed(() => {
    if (syncing.value) {
        return 'Sincronizando...';
    }

    return 'Sincronizar';
});

onMounted(async () => {
    await ensurePermissionsLoaded();
    canSync.value = hasPermission('sync');
});

function xsrfToken(): string {
    return decodeURIComponent(
        document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/)?.[2] ?? '',
    );
}

async function sync(): Promise<void> {
    if (syncing.value) {
        return;
    }

    syncing.value = true;

    try {
        const response = await fetch(`/gateway/sync/${props.scope}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
            },
        });

        const body = await response.json().catch(() => ({}));

        if (response.status === 409) {
            showToast({
                type: 'warning',
                message:
                    body?.message ??
                    'Sincronização em andamento. Aguarde a finalização para realizar um novo procedimento',
            });
            return;
        }

        if (response.status === 403) {
            showToast({
                type: 'error',
                message: 'Você não tem permissão para sincronizar.',
            });
            return;
        }

        if (!response.ok) {
            showToast({
                type: 'error',
                message: body?.message ?? 'Falha ao iniciar a sincronização.',
            });
            return;
        }

        showToast({
            type: 'success',
            message: body?.message ?? 'Sincronização iniciada.',
        });
    } catch {
        showToast({
            type: 'error',
            message: 'Falha ao iniciar a sincronização.',
        });
    } finally {
        syncing.value = false;
    }
}
</script>

<template>
    <v-clipped-button
        v-if="canSync"
        color="secondary"
        prepend-icon="ti ti-refresh"
        class="ml-2"
        :loading="syncing"
        :disabled="syncing"
        @click="sync"
    >
        {{ label }}
    </v-clipped-button>
</template>
