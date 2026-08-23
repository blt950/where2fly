import js from '@eslint/js';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';

export default [
    {
        ignores: ['node_modules/**', 'public/build/**', 'vendor/**', 'scripts/**'],
    },
    js.configs.recommended,
    react.configs.flat.recommended,
    {
        files: ['**/*.jsx'],
        ...reactHooks.configs['recommended-latest'],
        rules: {
            ...reactHooks.configs['recommended-latest'].rules,
            // Pre-existing effects rely on deliberate dep omissions documented inline.
            'react-hooks/exhaustive-deps': 'warn',
        },
    },
    {
        files: ['resources/js/**/*.{js,jsx}'],
        languageOptions: {
            globals: {
                ...globals.browser,
                // Ziggy route helper, injected globally by Blade's @routes directive.
                route: 'readonly',
                // Analytics script tag loaded in the page head, not an npm dependency.
                umami: 'readonly',
                // bootstrap.js assigns `window.bootstrap = bootstrap` for non-module scripts.
                bootstrap: 'readonly',
            },
        },
        settings: {
            react: { version: 'detect' },
        },
        rules: {
            // storage.js deliberately swallows blocked-localStorage errors via empty catch blocks.
            'no-empty': ['error', { allowEmptyCatch: true }],
            // No prop-types usage anywhere in this codebase.
            'react/prop-types': 'off',
            // React 19's JSX transform doesn't require React in scope.
            'react/react-in-jsx-scope': 'off',
            // searchForm.js reuses `var selected` across mutually-exclusive if-branches in one
            // function — legal (var is function-scoped) and spread across many existing lines.
            'no-redeclare': 'off',
            // Pre-existing target="_blank" links across many components; adding rel=noreferrer
            // to each is a production edit out of scope for this correctness-linting pass.
            'react/jsx-no-target-blank': 'off',
        },
    },
];
