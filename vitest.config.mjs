import { defineConfig } from 'vitest/config';

// Standalone on purpose: vite.config.mjs wires laravel-vite-plugin and Sentry, neither of
// which belongs in the test pipeline.
export default defineConfig({
    test: {
        environment: 'node',
        include: ['resources/js/**/*.test.{js,jsx}'],
    },
});
