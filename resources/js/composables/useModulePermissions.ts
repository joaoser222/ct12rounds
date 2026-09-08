import { computed } from 'vue';
import { usePermissions } from '@/composables/usePermissions';

export type ModulePermissionMap<TAction extends string = string> = Partial<
    Record<TAction, string | false>
>;

type ModulePermissionsOptions<TAction extends string> = {
    module: () => string | undefined;
    permissions: () => string[] | undefined;
    permissionMap: () => ModulePermissionMap<TAction> | undefined;
};

// Gathers the `module.action` convention and the overrides used by generic components.
export function useModulePermissions<TAction extends string>(
    options: ModulePermissionsOptions<TAction>,
) {
    const { permissions: loadedPermissions, loadPermissions } =
        usePermissions();

    const permissionSource = computed(() => {
        return options.permissions() ?? loadedPermissions.value;
    });

    const usesModulePermissions = computed(() => {
        return (
            options.module() !== undefined ||
            options.permissionMap() !== undefined
        );
    });

    const resolvePermission = (action: TAction): string | null => {
        const override = options.permissionMap()?.[action];

        if (override === false) {
            return null;
        }

        if (typeof override === 'string') {
            return override;
        }

        const module = options.module();

        if (module === undefined) {
            return null;
        }

        return `${module}.${action}`;
    };

    const hasPermission = (action: TAction): boolean => {
        const override = options.permissionMap()?.[action];

        if (override === false) {
            return false;
        }

        const permission = resolvePermission(action);

        if (permission === null) {
            return true;
        }

        return permissionSource.value.includes(permission);
    };

    async function ensurePermissionsLoaded(): Promise<void> {
        if (
            options.permissions() === undefined &&
            usesModulePermissions.value
        ) {
            await loadPermissions();
        }
    }

    return {
        permissionSource,
        usesModulePermissions,
        resolvePermission,
        hasPermission,
        ensurePermissionsLoaded,
    };
}
