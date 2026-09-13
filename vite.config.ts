import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import vuetify from 'vite-plugin-vuetify';
import Components from 'unplugin-vue-components/vite';

const hmrHost = process.env.VITE_HMR_HOST;
const hmrClientPort = Number(process.env.VITE_HMR_CLIENT_PORT ?? 5173);

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
            hotFile: process.env.VITE_HOT_FILE,
            fonts: [
                google('Barlow', {
                    weights: [400, 500, 600],
                }),
                google('Barlow Condensed', {
                    weights: [400, 500, 600],
                }),
                google('Bebas Neue', {
                    weights: [400],
                }),
            ],
        }),
        vue(),
        vuetify({
            autoImport: true,
            styles: {
                configFile: 'resources/js/styles/app.scss',
            },
        }),
        Components({
            dirs: ['resources/js/components'],
            dts: 'storage/vite/components.d.ts',
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        host: '0.0.0.0',
        origin: hmrHost === undefined ? undefined : `http://${hmrHost}`,
        cors: hmrHost === undefined
            ? undefined
            : { origin: [`http://${hmrHost}`, `https://${hmrHost}`] },
        hmr: hmrHost === undefined
            ? undefined
            : { host: hmrHost, clientPort: hmrClientPort, path: '/vite-hmr' },
    },
    build: {
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
            },
        },
    },
    ssr: {
        noExternal: ['vuetify'],
    },
});
