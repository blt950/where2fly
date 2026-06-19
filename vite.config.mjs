import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    server: {
        host: '127.0.0.1'
    },
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap's vendored SCSS still uses syntax deprecated in
                // Dart Sass (if-function, global-builtin, color-functions);
                // quietDeps mutes warnings originating in node_modules.
                quietDeps: true,
                // Silence the @import deprecation for our own stylesheets
                // until we migrate to the @use/@forward module system.
                silenceDeprecations: ['import'],
            },
        },
    },
    build: {
        rollupOptions: {
            output: {
                // Split large vendor libraries into separately-cached chunks
                // instead of one ~600 kB app bundle.
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('leaflet')) return 'leaflet';
                        if (id.includes('react') || id.includes('scheduler')) return 'react';
                        return 'vendor';
                    }
                },
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/nouislider.js',
                'resources/js/sortable.js',
                'resources/js/functions/searchResults.js',
                'resources/js/functions/searchForm.js',
                'resources/js/functions/tooltip.js',
                'resources/js/functions/taf.js',
                'resources/js/functions/combobox.js',
            ],
            refresh: true,
        }),
        react(),
    ],
});
