import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';
import { vMaska } from 'maska/vue';
import { vTextCase } from '@/directives/textCase';
import RootApp from '@/App.vue';
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue';
import viaCep from '@/plugins/viacep';
import { createAppVuetify } from '@/plugins/vuetify';

type InertiaPageComponent = DefineComponent & {
    layout?: unknown;
};

type PageModule = {
    default: InertiaPageComponent;
};

createInertiaApp({
    resolve: async (name) => {
        const page = await resolvePageComponent<PageModule>(
            `./pages/${name}.vue`,
            import.meta.glob<PageModule>('./pages/**/*.vue'),
        );
        const component = page.default;

        if (component.layout === undefined) {
            component.layout = AuthenticatedLayout;
        }

        return component;
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () =>
                h(RootApp, null, {
                    default: () => h(App, props),
                }),
        });

        app.use(plugin);
        app.use(createAppVuetify());
        app.use(viaCep);
        app.directive('maska', vMaska);
        app.directive('text-case', vTextCase);

        app.mount(el);
    },

    progress: {
        color: '#6750A4', // Material You primary — adjust to your color
    },
});
