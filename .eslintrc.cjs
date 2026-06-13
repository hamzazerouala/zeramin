module.exports = {
    root: true,
    env: { browser: true, es2021: true },
    parser: '@typescript-eslint/parser',
    parserOptions: { ecmaVersion: 'latest', sourceType: 'module', ecmaFeatures: { jsx: true } },
    settings: { react: { version: 'detect' } },
    extends: ['eslint:recommended'],
    ignorePatterns: ['public/spa', 'vendor', 'node_modules', 'dist'],
    rules: {
        'no-unused-vars': 'off',
        'no-undef': 'off',
    },
};
