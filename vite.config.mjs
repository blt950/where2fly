import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { sentryVitePlugin } from '@sentry/vite-plugin';

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
        sourcemap: process.env.SENTRY_AUTH_TOKEN ? 'hidden' : false,
        chunkSizeWarningLimit: 1000,
        rolldownOptions: {
            output: {
                codeSplitting: {
                    groups: [
                        { name: 'maplibre', test: /node_modules\/(maplibre-gl|@maplibre|@mapbox|pbf|earcut|kdbush|potpack|gl-matrix|tinyqueue|quickselect|murmurhash-js)\// },
                        { name: 'react', test: /node_modules\/(react|react-dom|scheduler)\// },
                        { name: 'vendor', test: /node_modules/ },
                    ],
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
                'resources/js/functions/formSubmit.js',
            ],
            refresh: true,
        }),
        react(),
        sentryVitePlugin({
            org: 'blt950',
            project: 'where2fly',
            authToken: process.env.SENTRY_AUTH_TOKEN,
            disable: !process.env.SENTRY_AUTH_TOKEN,
            release: { name: process.env.SENTRY_RELEASE },
            sourcemaps: {
                filesToDeleteAfterUpload: ['public/build/**/*.map'],
            },
        }),
    ],
});
