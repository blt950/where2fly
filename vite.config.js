import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '127.0.0.1'
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/js/nouislider.js',
                'resources/js/multiselect.js',
                'resources/js/map.js',
                'resources/js/cards.js',
                'resources/js/leaflet.js',
                'resources/js/sortable.js',
            ],
            refresh: true,
        }),
    ],
});
