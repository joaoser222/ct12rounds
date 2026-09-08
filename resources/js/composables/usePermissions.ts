import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

// Centralizes loading and local caching of the authenticated user's permissions.
type AuthUser = {
    id: number;
    permissions_version: string | null;
};

type SharedProps = {
    auth?: {
        user?: AuthUser | null;
    };
};

type PermissionsCache = {
    userId: number;
    version: string | null;
    permissions: string[];
};

type PermissionsResponse = {
    version: string | null;
    permissions: string[];
};

const CACHE_KEY = 'auth.permissions';

const permissions = ref<string[]>([]);
const ready = ref(false);
const loading = ref(false);

// Prevents concurrent requests and ensures a single hydration per permission version.
let pendingRequest: Promise<string[]> | null = null;
let cacheInitializedForUser: number | null = null;

function isBrowser(): boolean {
    return typeof window !== 'undefined';
}

function readCache(): PermissionsCache | null {
    if (!isBrowser()) {
        return null;
    }

    const cachedValue = window.localStorage.getItem(CACHE_KEY);

    if (cachedValue === null) {
        return null;
    }

    try {
        return JSON.parse(cachedValue) as PermissionsCache;
    } catch {
        window.localStorage.removeItem(CACHE_KEY);

        return null;
    }
}

function writeCache(payload: PermissionsCache): void {
    if (!isBrowser()) {
        return;
    }

    window.localStorage.setItem(CACHE_KEY, JSON.stringify(payload));
}

// Whenever the user changes or the session expires, the local cache also needs to be cleared.
function clearPermissionsCache(): void {
    permissions.value = [];
    ready.value = false;
    loading.value = false;
    pendingRequest = null;
    cacheInitializedForUser = null;

    if (!isBrowser()) {
        return;
    }

    window.localStorage.removeItem(CACHE_KEY);
}

async function fetchPermissions(): Promise<PermissionsResponse> {
    const response = await fetch('/auth/permissions', {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
        },
    });

    if (!response.ok) {
        throw new Error('Failed to load permissions.');
    }

    return (await response.json()) as PermissionsResponse;
}

export function usePermissions() {
    const page = usePage();

    const user = computed(() => {
        return ((page.props as SharedProps).auth?.user ??
            null) as AuthUser | null;
    });

    function hydrateFromCache(): boolean {
        if (!isBrowser() || user.value === null) {
            return false;
        }

        const cachedPermissions = readCache();

        if (
            cachedPermissions === null ||
            cachedPermissions.userId !== user.value.id ||
            cachedPermissions.version !== user.value.permissions_version
        ) {
            return false;
        }

        permissions.value = cachedPermissions.permissions;
        ready.value = true;
        cacheInitializedForUser = user.value.id;

        return true;
    }

    // Tries cache first and only falls back to the API when needed or when `force` is requested.
    async function loadPermissions(force = false): Promise<string[]> {
        if (user.value === null) {
            clearPermissionsCache();

            return [];
        }

        if (
            !force &&
            cacheInitializedForUser === user.value.id &&
            ready.value
        ) {
            return permissions.value;
        }

        if (!force && hydrateFromCache()) {
            return permissions.value;
        }

        if (pendingRequest !== null) {
            return pendingRequest;
        }

        loading.value = true;

        pendingRequest = fetchPermissions()
            .then((payload) => {
                permissions.value = payload.permissions;
                ready.value = true;
                cacheInitializedForUser = user.value?.id ?? null;

                if (user.value !== null) {
                    writeCache({
                        userId: user.value.id,
                        version: payload.version,
                        permissions: payload.permissions,
                    });
                }

                return payload.permissions;
            })
            .finally(() => {
                loading.value = false;
                pendingRequest = null;
            });

        return pendingRequest;
    }

    // Semantic helper for direct queries in templates and layouts.
    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    return {
        permissions,
        ready,
        loading,
        can,
        loadPermissions,
        clearPermissionsCache,
    };
}
