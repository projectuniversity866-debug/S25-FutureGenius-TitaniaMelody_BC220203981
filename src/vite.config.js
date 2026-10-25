import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    // Chunks loaded on demand (e.g. the syntax highlighter) are found relative
    // to app.js, so they also load when the app lives in a sub-folder (WAMP).
    experimental: {
        renderBuiltUrl(filename, { hostType }) {
            return hostType === 'js' ? { relative: true } : undefined;
        },
    },
    plugins: [
        laravel({
            input: ['resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
