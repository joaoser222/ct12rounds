<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import PasswordField from '@/components/inputs/PasswordField.vue';
import { email, required, same } from '@/plugins/validators';
import type { DetailsRoutes } from '@/shared/page';
import { useSharedOptions } from '@/shared/options';

defineOptions({ layout: AuthenticatedLayout });

type UserRecord = {
    id?: number;
    name?: string;
    email?: string;
    role_id?: number | null;
};

const props = defineProps<{
    user?: UserRecord | null;
    routes: DetailsRoutes;
    selectedPermissionIds?: number[];
    permissionsOverride?: boolean;
}>();

const sharedProps = usePage().props;
const { roles } = useSharedOptions({
    roles: sharedProps.options?.roles,
});
const permissionGroups = computed(
    () =>
        (sharedProps.options?.permissionGroups as Array<{
            id: string;
            title: string;
            permissions: Array<{
                id: number;
                name: string;
                label: string;
                description: string;
            }>;
        }>) ?? [],
);
const editablePermissionIdsByRole = computed<Record<number, number[]>>(
    () => (sharedProps.options?.editablePermissionIdsByRole as Record<number, number[]>) ?? {},
);

const activeTab = ref('general');

const defaults = {
    name: '',
    email: '',
    role_id: null,
    password: '',
    password_confirmation: '',
    permission_ids: props.selectedPermissionIds ?? [],
};

const getEditablePermissionIds = (roleId: number | null | undefined): number[] => {
    if (!roleId) {
        return [];
    }

    return editablePermissionIdsByRole.value[roleId] ?? [];
};

const togglePermission = (
    permissionIds: number[],
    permissionId: number,
    enabled: boolean | null,
): number[] => {
    if (enabled) {
        return permissionIds.includes(permissionId)
            ? permissionIds
            : [...permissionIds, permissionId];
    }

    return permissionIds.filter((id) => id !== permissionId);
};

const applyRole = (
    form: { role_id: number | null; permission_ids: number[] },
    roleId: number | null,
): void => {
    form.role_id = roleId;

    form.permission_ids = getEditablePermissionIds(roleId);
};

const visiblePermissionGroups = computed(() => {
    return (roleId: number | null | undefined) => {
        const editablePermissionIds = getEditablePermissionIds(roleId);

        return permissionGroups.value
            .map((group) => ({
                ...group,
                permissions: group.permissions.filter((permission) =>
                    editablePermissionIds.includes(permission.id),
                ),
            }))
            .filter((group) => group.permissions.length > 0);
    };
});
</script>

<template>
    <DetailsPage
        title="Usuário"
        :item="user"
        :defaults="defaults"
        :routes="routes"
        module="users"
    >
        <template #default="{ form, errors, isCreating }">
            <v-tabs v-model="activeTab" color="primary" class="mb-4">
                <v-tab value="general">Dados Gerais</v-tab>
                <v-tab value="permissions">Permissões</v-tab>
            </v-tabs>

            <v-window v-model="activeTab">
                <v-window-item value="general">
                    <v-row class="ma-0">
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.name"
                                v-text-case="'capitalize'"
                                label="Nome"
                                :rules="[required]"
                                :error-messages="errors.name"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.email"
                                label="E-mail"
                                :rules="[required, email]"
                                :error-messages="errors.email"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="form.role_id"
                                label="Perfil"
                                :items="roles"
                                item-title="title"
                                item-value="value"
                                clearable
                                :error-messages="errors.role_id"
                                @update:model-value="applyRole(form, $event)"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <PasswordField
                                v-model="form.password"
                                label="Senha"
                                :rules="isCreating ? [required] : []"
                                :hint="isCreating ? undefined : 'Preencha apenas para alterar a senha'"
                                persistent-hint
                                :error-messages="errors.password"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <PasswordField
                                v-model="form.password_confirmation"
                                label="Confirmar senha"
                                :rules="form.password ? [same(form.password)] : []"
                                :error-messages="errors.password_confirmation"
                            />
                        </v-col>
                    </v-row>
                </v-window-item>

                <v-window-item value="permissions">
                    <v-alert
                        type="info"
                        variant="tonal"
                        class="mb-4"
                        text="Esta aba permite editar exatamente as permissões disponíveis para o perfil selecionado. Se nenhuma alteração for feita, o usuário continua herdando integralmente o perfil."
                    />

                    <v-alert
                        v-if="!form.role_id"
                        type="warning"
                        variant="tonal"
                        class="mb-4"
                        text="Selecione um perfil para habilitar a edição das permissões do usuário."
                    />

                    <v-row v-else class="ma-0">
                        <v-col
                            v-for="group in visiblePermissionGroups(form.role_id)"
                            :key="group.id"
                            cols="12"
                            md="6"
                        >
                            <v-card variant="outlined" height="100%">
                                <v-card-title class="text-subtitle-1">
                                    {{ group.title }}
                                </v-card-title>
                                <v-card-text>
                                    <v-checkbox
                                        v-for="permission in group.permissions"
                                        :key="permission.id"
                                        :label="permission.label"
                                        :hint="permission.description"
                                        persistent-hint
                                        density="compact"
                                        hide-details="auto"
                                        :model-value="form.permission_ids.includes(permission.id)"
                                        @update:model-value="form.permission_ids = togglePermission(form.permission_ids, permission.id, $event)"
                                    />
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>
                </v-window-item>
            </v-window>
        </template>
    </DetailsPage>
</template>
