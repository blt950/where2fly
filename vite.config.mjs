import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    server: {
        host: '127.0.0.1'
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
                'resources/js/functions/tags.js',
            ],
            refresh: true,
        }),
        react(),
    ],
});
