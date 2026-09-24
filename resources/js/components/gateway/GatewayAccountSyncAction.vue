<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useModulePermissions } from '@/composables/useModulePermissions';
import { useToast } from '@/composables/useToast';

const props = defineProps<{
    accountId: number;
}>();

const { show: showToast } = useToast();
const syncing = ref(false);
const canSync = ref(false);
const dialog = ref(false);

const { hasPermission, ensurePermissionsLoaded } = useModulePermissions<'sync'>(
    {
        module: () => undefined,
        permissions: () => undefined,
        permissionMap: () => ({ sync: 'gateway_accounts.update' }),
    },
);

onMounted(async () => {
    await ensurePermissionsLoaded();
    canSync.value = hasPermission('sync');
});

function openDialog(): void {
    if (syncing.value) {
        return;
    }

    dialog.value = true;
}

function abort(): void {
    dialog.value = false;
}

function xsrfToken(): string {
    return decodeURIComponent(
        document.cookie.match(/(^|; )XSRF-TOKEN=([^;]*)/)?.[2] ?? '',
    );
}

async function confirmSync(): Promise<void> {
    dialog.value = false;

    if (syncing.value) {
        return;
    }

    syncing.value = true;

    try {
        const response = await fetch(
            `/gateway-accounts/${props.accountId}/sync`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
            },
        );

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
    <div v-if="canSync">
        <v-btn-icon
            icon="ti ti-arrows-exchange"
            size="small"
            color="secondary"
            :loading="syncing"
            :disabled="syncing"
            title="Sincronizar conta"
            @click.stop="openDialog"
        />

        <v-dialog v-model="dialog" max-width="460" persistent>
            <v-card>
                <v-card-title class="d-flex align-center ga-2">
                    <v-icon icon="ti ti-arrows-exchange" color="warning" />
                    Sincronização completa
                </v-card-title>
                <v-card-text>
                    Isso irá sincronizar todos os objetos do gateway
                    selecionado. Deseja prosseguir?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="abort">Não</v-btn>
                    <v-btn color="warning" variant="flat" @click="confirmSync">
                        Sim
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>
