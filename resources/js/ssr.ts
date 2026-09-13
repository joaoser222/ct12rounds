import createServer from '@inertiajs/vue3/server';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { renderToString } from '@vue/server-renderer';
import { createSSRApp, h, type DefineComponent } from 'vue';
import RootApp from '@/App.vue';
import { createAppVuetify } from '@/plugins/vuetify';

type InertiaPageComponent = DefineComponent & {
    layout?: unknown;
};

type PageModule = {
    default: InertiaPageComponent;
};

createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        resolve: async (name) => {
            const mod = await resolvePageComponent<PageModule>(
                `./pages/${name}.vue`,
                import.meta.glob<PageModule>('./pages/**/*.vue'),
            );

            return mod.default;
        },
        setup({ App, props, plugin }) {
            return createSSRApp({
                render: () => h(RootApp, null, { default: () => h(App, props) }),
            })
                .use(plugin)
                .use(createAppVuetify({ ssr: true }));
        },
    }),
);