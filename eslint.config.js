import js from '@eslint/js';
import globals from 'globals';

// Lint hanya kode milik project (resources/js). Aset tema di public/assets adalah vendor.
export default [
    { ignores: ['public/**', 'vendor/**', 'node_modules/**', 'storage/**', 'bootstrap/ssr/**'] },
    js.configs.recommended,
    {
        files: ['resources/js/**/*.js'],
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: { ...globals.browser },
        },
        rules: {
            'no-console': ['error', { allow: ['warn', 'error'] }],
        },
    },
];
