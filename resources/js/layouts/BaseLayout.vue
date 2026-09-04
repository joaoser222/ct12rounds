<template>
    <div>
        <v-navigation-drawer
            :model-value="mdAndUp || drawer"
            :permanent="mdAndUp"
            :temporary="!mdAndUp"
            width="280"
            @update:model-value="drawer = $event"
        >
            <div class="d-flex justify-center align-center px-4">
                <v-img
                    :src="logo"
                    alt="Ct12rounds"
                    contain
                    height="64px"
                    max-width="132"
                />
            </div>

            <v-list nav density="compact" class="pa-3">
                <v-list-group
                    v-for="group in menu"
                    :key="group.name"
                    fluid
                >
                    <template #activator="{ props: groupProps }">
                        <v-list-item
                            v-bind="groupProps"
                            :prepend-icon="group.icon"
                            :title="group.title"
                        />
                    </template>

                    <v-list-item
                        v-if="group.name === 'start'"
                        :active="isApplicationsHome"
                        prepend-icon="ti ti-apps"
                        title="Todos"
                        @click="navigateTo('/')"
                        class="ml-4"
                    />

                    <v-list-item
                        v-for="item in group.items"
                        :key="item.href"
                        :active="isItemActive(item.href)"
                        :prepend-icon="item.icon"
                        :title="item.title"
                        @click="navigateTo(item.href)"
                        class="ml-4"
                    />
                </v-list-group>
            </v-list>
        </v-navigation-drawer>

        <v-app-bar
            flat
            density="compact"
            border="b"
            class="border-surface-variant"
        >
            <v-app-bar-nav-icon
                v-if="!mdAndUp"
                icon="ti ti-menu-2"
                @click="drawer = !drawer"
            />

            <v-app-bar-title>
                <div class="d-flex align-center ga-2">
                    <v-icon
                        v-if="currentMenuItem?.icon"
                        :icon="currentMenuItem.icon"
                        size="small"
                        color="primary"
                    />
                    <h3>{{ currentPageTitle }}</h3>
                </div>
            </v-app-bar-title>

            <v-menu>
                <template #activator="{ props: menuProps }">
                    <v-btn
                        icon="ti ti-user-circle"
                        variant="text"
                        size="small"
                        v-bind="menuProps"
                    />
                </template>

                <v-list density="comfortable" min-width="240">
                    <v-list-item>
                        <template #prepend>
                            <v-avatar
                                color="primary"
                                variant="tonal"
                                rounded="sm"
                                class="clipped-object"
                            >
                                <span class="text-subtitle-2">{{
                                    userInitials
                                }}</span>
                            </v-avatar>
                        </template>

                        <v-list-item-title>{{ user.name }}</v-list-item-title>
                        <v-list-item-subtitle>{{
                            user.email
                        }}</v-list-item-subtitle>
                    </v-list-item>
                    <v-divider />
                    <v-list-item
                        prepend-icon="ti ti-logout"
                        title="Sair"
                        @click="logout"
                    />
                </v-list>
            </v-menu>
        </v-app-bar>

        <v-main class="layout-transparent-main">
            <v-container
                fluid
                class="layout-transparent-container"
                :class="isFullBleedPage ? 'pa-0' : 'pa-4 pa-md-6'"
            >
                <div class="page-content-host">
                    <slot />
                </div>
            </v-container>
        </v-main>
    </div>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useDisplay } from 'vuetify';
import logo from '@/assets/logo.webp';
import { usePermissions } from '@/composables/usePermissions';
import type { MenuGroup } from '@/navigation';

type MenuItem = {
    title: string;
    icon: string;
    href: string;
};

type AuthUser = {
    id?: number;
    name: string;
    email: string;
    permissions_version?: string | null;
};

type SharedProps = {
    name?: string;
    auth?: {
        user?: AuthUser;
    };
};

const props = defineProps<{
    menu: MenuGroup[];
}>();

const page = usePage();
const { clearPermissionsCache } = usePermissions();
const { mdAndUp } = useDisplay();
const drawer = ref(false);

const sharedProps = computed(() => page.props as SharedProps);

const user = computed<AuthUser>(() => {
    return (
        sharedProps.value.auth?.user ?? {
            name: '',
            email: '',
        }
    );
});

const userInitials = computed(() => {
    const initials = user.value.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((namePart) => namePart[0])
        .join('')
        .toUpperCase();

    return initials || 'U';
});

const currentPath = computed(() => {
    const url = page.url || window.location.pathname;

    return new URL(url, window.location.origin).pathname;
});

const isApplicationsHome = computed(() => currentPath.value === '/');

const isFullBleedPage = computed(() => page.component === 'Chat');

const currentMenuItem = computed(() => {
    return props.menu
        .flatMap((group) => group.items)
        .find((item) => isItemActive(item.href));
});

const currentPageTitle = computed(
    () => currentMenuItem.value?.title ?? (isApplicationsHome.value ? 'Todos' : 'Dashboard'),
);

function normalizePath(href: string): string {
    return new URL(href, window.location.origin).pathname;
}

function isItemActive(href: string): boolean {
    const itemPath = normalizePath(href);

    if (itemPath === '/dashboard') {
        return currentPath.value === itemPath;
    }

    return (
        currentPath.value === itemPath ||
        currentPath.value.startsWith(`${itemPath}/`)
    );
}

function navigateTo(href: string): void {
    drawer.value = false;
    router.visit(href);
}

function logout(): void {
    clearPermissionsCache();
    router.post('/logout');
}
</script>